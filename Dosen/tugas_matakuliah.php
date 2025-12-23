<?php
// tugas_matakuliah.php
session_start();

// --- 1. Konfigurasi Database ---
$dbHost = '127.0.0.1';
$dbName = 'universitas';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("Koneksi DB gagal: " . htmlspecialchars($e->getMessage()));
}

// --- 2. Identitas Dosen ---
$loggedInDosenNIK = $_SESSION['nik'] ?? 1001; 
$userName = $_SESSION['nama'] ?? 'Dosen Pengampu';

// Helper
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES); }
function redirect_self(){ header('Location: ' . $_SERVER['PHP_SELF']); exit; }

// --- 3. Ambil Data Mata Kuliah & Kelas Dosen ---
$stmtMatkul = $pdo->prepare("
    SELECT jk.id_kelas, mk.kode_matkul, mk.nama AS nama_matkul 
    FROM jadwal_kelas jk
    JOIN mata_kuliah mk ON jk.kode_matkul = mk.kode_matkul
    WHERE jk.id_dosen = ?
    ORDER BY mk.nama ASC
");
$stmtMatkul->execute([$loggedInDosenNIK]);
$dosenMatkuls = $stmtMatkul->fetchAll();

$dosenKelasIds = array_column($dosenMatkuls, 'id_kelas');

// Inisialisasi GroupedByKelas
$groupedByKelas = [];
foreach ($dosenMatkuls as $dm) {
    $groupedByKelas[$dm['id_kelas']] = [
        'nama_matkul' => $dm['nama_matkul'],
        'tugas' => []
    ];
}

// --- 4. Ambil Data Pertemuan untuk Dropdown ---
$pertemuanGroups = [];
$allPertemuans = [];
if (!empty($dosenKelasIds)) {
    $placeholders = implode(',', array_fill(0, count($dosenKelasIds), '?'));
    $stmtP = $pdo->prepare("
        SELECT p.*, mk.nama as nama_matkul 
        FROM pertemuan p
        JOIN jadwal_kelas jk ON p.id_kelas = jk.id_kelas
        JOIN mata_kuliah mk ON jk.kode_matkul = mk.kode_matkul
        WHERE p.id_kelas IN ($placeholders) AND jk.id_dosen = ?
        ORDER BY mk.nama ASC, p.id_kelas ASC, p.minggu_ke ASC
    ");
    
    $params = array_merge($dosenKelasIds, [$loggedInDosenNIK]);
    $stmtP->execute($params);
    $allPertemuans = $stmtP->fetchAll();

    foreach ($allPertemuans as $p) {
        $groupKey = $p['nama_matkul'] . " (Kelas " . $p['id_kelas'] . ")";
        $pertemuanGroups[$groupKey][] = [
            'id_kelas' => $p['id_kelas'],
            'id_pertemuan' => $p['id_pertemuan'],
            'label' => "Pertemuan Ke-" . $p['minggu_ke']
        ];
    }
}
$allowedPertemuanIds = array_column($allPertemuans, 'id_pertemuan');

// --- 5. Handle POST (Create/Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $deadline = $_POST['deadline'] ?? '';
    $target = $_POST['pertemuan_target'] ?? ''; 

    $parts = explode('-', $target);
    $selPertemuan = isset($parts[1]) ? (int)$parts[1] : 0;

    if ($judul && $deadline && in_array($selPertemuan, $allowedPertemuanIds)) {
        if ($action === 'create') {
            $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM tugas WHERE id_pertemuan = ?");
            $stmtCek->execute([$selPertemuan]);
            if ($stmtCek->fetchColumn() > 0) {
                $_SESSION['flash'] = "Gagal! Pertemuan ini sudah memiliki tugas.";
                $_SESSION['flash_type'] = "danger";
            } else {
                $stmt = $pdo->prepare("INSERT INTO tugas (id_pertemuan, nama_tugas, deskripsi, tanggal_deadline) VALUES (?,?,?,?)");
                $stmt->execute([$selPertemuan, $judul, $deskripsi, $deadline]);
                $_SESSION['flash'] = "Tugas berhasil ditambahkan.";
                $_SESSION['flash_type'] = "success";
            }
        } elseif ($action === 'update') {
            $idTugas = (int)$_POST['id_tugas'];
            $stmt = $pdo->prepare("UPDATE tugas SET id_pertemuan=?, nama_tugas=?, deskripsi=?, tanggal_deadline=? WHERE id_tugas=?");
            $stmt->execute([$selPertemuan, $judul, $deskripsi, $deadline, $idTugas]);
            $_SESSION['flash'] = "Tugas berhasil diperbarui.";
            $_SESSION['flash_type'] = "success";
        }
    }
    redirect_self();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $idDel = (int)$_GET['delete'];
    $stmtDel = $pdo->prepare("DELETE t FROM tugas t 
        JOIN pertemuan p ON t.id_pertemuan = p.id_pertemuan 
        JOIN jadwal_kelas jk ON p.id_kelas = jk.id_kelas 
        WHERE t.id_tugas = ? AND jk.id_dosen = ?");
    $stmtDel->execute([$idDel, $loggedInDosenNIK]);
    $_SESSION['flash'] = "Tugas berhasil dihapus.";
    $_SESSION['flash_type'] = "info";
    redirect_self();
}

// --- 6. Ambil Data Tugas untuk List ---
if (!empty($allowedPertemuanIds)) {
    $pIds = implode(',', array_fill(0, count($allowedPertemuanIds), '?'));
    $stmtT = $pdo->prepare("
        SELECT t.*, p.minggu_ke, p.id_kelas 
        FROM tugas t
        JOIN pertemuan p ON t.id_pertemuan = p.id_pertemuan
        WHERE t.id_pertemuan IN ($pIds)
        ORDER BY p.minggu_ke ASC
    ");
    $stmtT->execute($allowedPertemuanIds);
    $allTugas = $stmtT->fetchAll();

    foreach ($allTugas as $t) {
        if (isset($groupedByKelas[$t['id_kelas']])) {
            $groupedByKelas[$t['id_kelas']]['tugas'][] = $t;
        }
    }
}

$flash = $_SESSION['flash'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash'], $_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Tugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7fc; font-family: 'Poppins', sans-serif; }
        .sidebar { width: 250px; min-height: 100vh; background: linear-gradient(180deg, #1565c0, #1e88e5); position: fixed; left: 0; top: 0; padding: 25px 0; text-align: center; color: white; }
        .content { margin-left: 270px; padding: 30px; }
        .tugas-box { background: #fff; border-radius: 15px; box-shadow: 0 3px 10px rgba(0,0,0,0.08); padding: 25px; }
        .card-tugas { border-left: 5px solid #1e88e5; background: #f8f9fa; border-radius: 10px; margin-bottom: 15px; padding: 15px; }
        .pertemuan-list { display: none; }
        @media (max-width: 992px) { .sidebar { position: relative; width: 100%; height: auto; } .content { margin-left: 0; } }
    </style>
</head>
<body>

<?php if (file_exists('sidebar.php')) include 'sidebar.php'; ?>

<div class="content">
    <h4 class="fw-semibold mb-3">Tugas Mata Kuliah <small class="text-muted">| <?= h($userName); ?></small></h4>

    <?php if($flash): ?>
        <div class="alert alert-<?= $flashType; ?> alert-dismissible fade show">
            <?= h($flash); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="tugas-box">
        <div class="d-flex justify-content-between mb-4 gap-2">
            <div class="flex-grow-1">
                <label class="small fw-bold">Pilih Mata Kuliah:</label>
                <select id="selectMatkul" class="form-select">
                    <?php foreach($groupedByKelas as $idKelas => $data): ?>
                        <option value="<?= $idKelas; ?>"><?= h($data['nama_matkul']); ?> (Kelas <?= $idKelas; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-flex align-items-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-lg"></i> Tambah Tugas
                </button>
            </div>
        </div>

        <div id="listTugasContainer">
            <?php foreach ($groupedByKelas as $idKelas => $kelasData): ?>
                <div class="pertemuan-list" data-kelas="<?= $idKelas; ?>">
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        Daftar Tugas: <?= h($kelasData['nama_matkul']); ?>
                    </h6>

                    <?php if (empty($kelasData['tugas'])): ?>
                        <div class="text-center py-4 text-muted fst-italic">Belum ada tugas untuk mata kuliah ini.</div>
                    <?php else: ?>
                        <?php foreach ($kelasData['tugas'] as $t): ?>
                            <div class="card-tugas">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge bg-primary mb-2">Pertemuan <?= $t['minggu_ke']; ?></span>
                                        <h5 class="fw-bold mb-1"><?= h($t['nama_tugas']); ?></h5>
                                        <small class="text-danger fw-bold"><i class="bi bi-clock"></i> Deadline: <?= date('d M Y', strtotime($t['tanggal_deadline'])); ?></small>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                        <ul class="dropdown-menu shadow">
                                            <li><a class="dropdown-item btn-edit" href="javascript:void(0)" 
                                                data-id="<?= $t['id_tugas']; ?>" 
                                                data-nama="<?= h($t['nama_tugas']); ?>" 
                                                data-desc="<?= h($t['deskripsi']); ?>" 
                                                data-deadline="<?= h($t['tanggal_deadline']); ?>" 
                                                data-pertemuan-id="<?= $t['id_pertemuan']; ?>" 
                                                data-kelas-id="<?= $t['id_kelas']; ?>"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="?delete=<?= $t['id_tugas']; ?>" onclick="return confirm('Hapus tugas ini?')"><i class="bi bi-trash me-2"></i>Hapus</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <p class="mt-2 text-secondary"><?= nl2br(h($t['deskripsi'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form id="formTugas" method="post" class="modal-content">
            <input type="hidden" name="action" id="form_action" value="create">
            <input type="hidden" name="id_tugas" id="form_id" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Tugas Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Pertemuan Ke-</label>
                    <select name="pertemuan_target" id="pertemuan_target" class="form-select" required>
                        <option value="">-- Pilih Pertemuan --</option>
                        <?php foreach($pertemuanGroups as $groupName => $items): ?>
                            <optgroup label="<?= h($groupName); ?>">
                                <?php foreach($items as $p): ?>
                                    <option value="<?= h($p['id_kelas'] . '-' . $p['id_pertemuan']); ?>"><?= h($p['label']); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Judul Tugas</label>
                    <input name="judul" id="f_judul" type="text" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Instruksi/Deskripsi</label>
                    <textarea name="deskripsi" id="f_deskripsi" class="form-control" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Deadline</label>
                    <input name="deadline" id="f_deadline" type="date" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Tugas</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // 1. Logika Filter Mata Kuliah
    const selectMatkul = document.getElementById('selectMatkul');
    const sections = document.querySelectorAll('.pertemuan-list');

    function filterDisplay() {
        const val = selectMatkul.value;
        sections.forEach(sec => {
            sec.style.display = (sec.getAttribute('data-kelas') === val) ? 'block' : 'none';
        });
    }

    selectMatkul.addEventListener('change', filterDisplay);
    filterDisplay(); // Jalankan saat halaman load

    // 2. Logika Edit Tugas
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modalTitle').innerText = "Edit Tugas";
            document.getElementById('form_action').value = "update";
            document.getElementById('form_id').value = this.dataset.id;
            document.getElementById('f_judul').value = this.dataset.nama;
            document.getElementById('f_deskripsi').value = this.dataset.desc;
            document.getElementById('f_deadline').value = this.dataset.deadline;
            document.getElementById('pertemuan_target').value = this.dataset.kelasId + '-' + this.dataset.pertemuanId;
            
            let modal = new bootstrap.Modal(document.getElementById('modalTambah'));
            modal.show();
        });
    });

    // 3. Reset Modal saat ditutup
    document.getElementById('modalTambah').addEventListener('hidden.bs.modal', function () {
        document.getElementById('formTugas').reset();
        document.getElementById('modalTitle').innerText = "Tambah Tugas Baru";
        document.getElementById('form_action').value = "create";
    });
</script>
</body>
</html>