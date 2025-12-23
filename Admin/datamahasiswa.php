<?php
// datamahasiswa.php - Admin Panel Data Mahasiswa
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'universitas');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (Exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// -----------------
// CRUD LOGIC
// -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Tambah Mahasiswa
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO mahasiswa 
            (nim, nama_mahasiswa, id_prodi, id_kelas, angkatan, email, no_hp, id_dosen_pembimbing, alamat, status_akademik)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['nim'], $_POST['nama'], $_POST['id_prodi'] ?: null, 
            $_POST['id_kelas'] ?: null, $_POST['angkatan'], $_POST['email'], 
            $_POST['no_hp'], $_POST['id_dosen_pembimbing'] ?: null, 
            $_POST['alamat'], $_POST['status_akademik'] ?: 'aktif'
        ]);
    } 
    
    // Edit Mahasiswa
    elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE mahasiswa SET 
            nim=?, nama_mahasiswa=?, id_prodi=?, id_kelas=?, angkatan=?, 
            email=?, no_hp=?, id_dosen_pembimbing=?, alamat=?, status_akademik=?
            WHERE id_mahasiswa=?");
        $stmt->execute([
            $_POST['nim'], $_POST['nama'], $_POST['id_prodi'] ?: null, 
            $_POST['id_kelas'] ?: null, $_POST['angkatan'], $_POST['email'], 
            $_POST['no_hp'], $_POST['id_dosen_pembimbing'] ?: null, 
            $_POST['alamat'], $_POST['status_akademik'], $_POST['id_mahasiswa']
        ]);
    }

    // Hapus Mahasiswa
    elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM mahasiswa WHERE id_mahasiswa=?")->execute([$_POST['id_mahasiswa']]);
    }

    header("Location: datamahasiswa.php");
    exit;
}

// -----------------
// DATA FETCHING
// -----------------
// FIX: k.id_kelas digunakan karena k.kode_matkul TIDAK ADA di tabel kelas
$sql = "SELECT m.*, p.nama AS nama_prodi, k.id_kelas AS nama_kelas
        FROM mahasiswa m
        LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
        LEFT JOIN kelas k ON m.id_kelas = k.id_kelas
        ORDER BY m.id_mahasiswa ASC";
$mahasiswa = $pdo->query($sql)->fetchAll();

// Data untuk dropdown
$prodis = $pdo->query("SELECT * FROM prodi ORDER BY nama")->fetchAll();
$kelass = $pdo->query("SELECT id_kelas FROM kelas ORDER BY id_kelas")->fetchAll();
$dosens = $pdo->query("SELECT nik, nama FROM dosen ORDER BY nama")->fetchAll();

function hitungSemester($angkatan) {
    if (!$angkatan) return '-';
    $selisih = date('Y') - (int)$angkatan;
    if ($selisih < 0) return '1';
    return ($selisih * 2) + (date('n') < 7 ? 2 : 1);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Master Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="Style.css" rel="stylesheet"> </head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>🎓 Data Master Mahasiswa</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-circle"></i> Tambah Mahasiswa
        </button>
    </div>

    <div class="card shadow-sm p-3">
        <table class="table table-hover align-middle text-center">
            <thead class="table-dark"> <tr>
                    <th>NIM</th>
                    <th class="text-start">Nama Mahasiswa</th>
                    <th>Prodi</th>
                    <th>Kelas</th>
                    <th>Semester</th>
                    <th>Angkatan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mahasiswa as $m): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($m['nim']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($m['nama_mahasiswa']) ?></td>
                    <td><small><?= htmlspecialchars($m['nama_prodi'] ?? '-') ?></small></td>
                    <td><span class="badge bg-info text-dark">Kls <?= htmlspecialchars($m['nama_kelas'] ?? '-') ?></span></td>
                    <td><?= hitungSemester($m['angkatan']) ?></td>
                    <td><?= htmlspecialchars($m['angkatan'] ?? '-') ?></td>
                    <td>
                        <span class="badge bg-<?= $m['status_akademik']=='aktif'?'success':'secondary' ?>">
                            <?= ucfirst($m['status_akademik']) ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-warning btn-edit" 
                                data-id="<?= $m['id_mahasiswa'] ?>"
                                data-nim="<?= $m['nim'] ?>"
                                data-nama="<?= $m['nama_mahasiswa'] ?>"
                                data-prodi="<?= $m['id_prodi'] ?>"
                                data-kelas="<?= $m['id_kelas'] ?>"
                                data-angkatan="<?= $m['angkatan'] ?>"
                                data-email="<?= $m['email'] ?>"
                                data-hp="<?= $m['no_hp'] ?>"
                                data-dosen="<?= $m['id_dosen_pembimbing'] ?>"
                                data-alamat="<?= $m['alamat'] ?>"
                                data-status="<?= $m['status_akademik'] ?>"
                                data-bs-toggle="modal" data-bs-target="#modalEdit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        
                        <form method="post" class="d-inline" onsubmit="return confirm('Hapus data mahasiswa ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_mahasiswa" value="<?= $m['id_mahasiswa'] ?>">
                            <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <div class="modal-header"><h5>Tambah Mahasiswa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">NIM</label><input name="nim" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Nama Lengkap</label><input name="nama" class="form-control" required></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Program Studi</label>
                        <select name="id_prodi" class="form-select">
                            <?php foreach($prodis as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= $p['nama'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kelas</label>
                        <select name="id_kelas" class="form-select">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach($kelass as $k): ?><option value="<?= $k['id_kelas'] ?>">Kelas <?= $k['id_kelas'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Angkatan</label><input name="angkatan" type="number" class="form-control" value="<?= date('Y') ?>"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary w-100">Simpan Data Mahasiswa</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <div class="modal-header"><h5>Edit Data Mahasiswa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_mahasiswa" id="edit_id">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">NIM</label><input name="nim" id="edit_nim" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Nama</label><input name="nama" id="edit_nama" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status Akademik</label>
                        <select name="status_akademik" id="edit_status" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="cuti">Cuti</option>
                            <option value="lulus">Lulus</option>
                            <option value="non-aktif">Non-Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Angkatan</label><input name="angkatan" id="edit_angkatan" class="form-control"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Simpan Perubahan</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Logika pengambilan data ke Modal (Persis seperti datakelas.php)
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
        const d = btn.dataset;
        document.getElementById('edit_id').value = d.id;
        document.getElementById('edit_nim').value = d.nim;
        document.getElementById('edit_nama').value = d.nama;
        document.getElementById('edit_angkatan').value = d.angkatan;
        document.getElementById('edit_status').value = d.status;
    });
});
</script>
</body>
</html>