<?php
// absensi_mahasiswa.php
session_start();

// --- Konfigurasi dan Koneksi DB ---
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'universitas';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Koneksi gagal: ' . $conn->connect_error);
}

// Helper function
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES); }

// --- NIK Dosen yang Sedang Login ---
$loggedInDosenNIK = $_SESSION['nik'] ?? 1001; 
$userName = $_SESSION['nama'] ?? 'Dosen Pengampu';

// ===========================================
// === 1. LOGIKA SIMPAN (POST REQUEST) =======
// ===========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPertemuan = $_POST['id_pertemuan'] ?? null;
    $statusData = $_POST['status'] ?? []; 
    $idKelas = $_POST['id_kelas'] ?? '';

    if (!empty($idPertemuan) && !empty($statusData)) {
        $successCount = 0;
        // ON DUPLICATE KEY UPDATE memastikan data tidak dobel di tabel absensi
        $query = "INSERT INTO absensi (id_pertemuan, nim_mahasiswa, status_absensi, waktu_absensi)
                  VALUES (?, ?, ?, NOW())
                  ON DUPLICATE KEY UPDATE status_absensi = VALUES(status_absensi), waktu_absensi = NOW()";

        $stmt = $conn->prepare($query);
        if ($stmt) {
            foreach ($statusData as $nim => $statusAbsensi) {
                $stmt->bind_param("iss", $idPertemuan, $nim, $statusAbsensi);
                if ($stmt->execute()) $successCount++;
            }
            $stmt->close();
        }
        
        $pesan = "Absensi berhasil disimpan untuk $successCount mahasiswa.";
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=success&pesan=" . urlencode($pesan) . "&pertemuan=$idPertemuan&kelas=$idKelas");
        exit;
    } 
}

// ===========================================
// === 2. LOGIKA TAMPIL (GET REQUEST) ========
// ===========================================

// FIX: Menambahkan GROUP BY yang lengkap untuk daftar pertemuan agar lolos validasi Laragon
$pertemuan = [];
$stmtPertemuan = $conn->prepare("
    SELECT p.id_pertemuan, p.minggu_ke, mk.nama as nama_matkul, jk.id_kelas
    FROM pertemuan p
    JOIN jadwal_kelas jk ON p.id_kelas = jk.id_kelas
    JOIN mata_kuliah mk ON jk.kode_matkul = mk.kode_matkul
    WHERE jk.id_dosen = ?
    GROUP BY p.id_pertemuan, p.minggu_ke, mk.nama, jk.id_kelas
    ORDER BY mk.nama ASC, p.minggu_ke ASC
");
$stmtPertemuan->bind_param("i", $loggedInDosenNIK);
$stmtPertemuan->execute();
$pertemuan = $stmtPertemuan->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtPertemuan->close();

$selected_pertemuan = $_GET['pertemuan'] ?? '';
$selected_kelas = $_GET['kelas'] ?? ''; 

$mahasiswa = [];
$absensiTercatat = []; 

if ($selected_kelas && $selected_pertemuan) {
    // FIX LARAGON: GROUP BY harus menyertakan m.nim DAN m.nama_mahasiswa
    $queryMhs = "
        SELECT m.nim, m.nama_mahasiswa 
        FROM mahasiswa m
        JOIN krs k ON m.id_mahasiswa = k.id_mahasiswa
        WHERE k.id_kelas = ? AND k.status = 'Disetujui'
        GROUP BY m.nim, m.nama_mahasiswa
        ORDER BY m.nama_mahasiswa ASC
    ";
    $stmtMhs = $conn->prepare($queryMhs);
    $stmtMhs->bind_param("i", $selected_kelas); 
    $stmtMhs->execute();
    $mahasiswa = $stmtMhs->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtMhs->close();

    // Ambil data absensi yang sudah tersimpan
    $queryAbsensi = "SELECT nim_mahasiswa, status_absensi FROM absensi WHERE id_pertemuan = ?";
    $stmtAbsensi = $conn->prepare($queryAbsensi);
    $stmtAbsensi->bind_param("i", $selected_pertemuan);
    $stmtAbsensi->execute();
    $resAbs = $stmtAbsensi->get_result();
    while ($row = $resAbs->fetch_assoc()) {
        $absensiTercatat[$row['nim_mahasiswa']] = $row['status_absensi'];
    }
    $stmtAbsensi->close();
}

$conn->close();
$listStatus = ['Hadir', 'Izin', 'Sakit', 'Tidak Hadir'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SIAKAD - Input Presensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7fc; font-family: 'Poppins', sans-serif; }
        .content { margin-left: 270px; padding: 30px; }
        .absensi-box { background: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 25px; }
        .status-select-Hadir { border-left: 5px solid #198754; }
        .status-select-Izin { border-left: 5px solid #0dcaf0; }
        .status-select-Sakit { border-left: 5px solid #ffc107; }
        .status-select-Tidak-Hadir { border-left: 5px solid #dc3545; }
        @media (max-width: 992px) { .content { margin-left: 0; } }
    </style>
</head>
<body>

<?php if (file_exists('sidebar.php')) include 'sidebar.php'; ?>

<div class="content">
    <h4 class="fw-bold mb-4"><i class="bi bi-person-check me-2"></i>Presensi Mahasiswa</h4>
    
    <?php if(isset($_GET['pesan'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i> <?= h($_GET['pesan']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="absensi-box">
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-7">
                <label class="form-label fw-bold">Pilih Pertemuan Kuliah</label>
                <div class="input-group">
                    <select name="pertemuan" id="select_pertemuan" class="form-select" required>
                        <option value="">-- Pilih Jadwal Pertemuan --</option>
                        <?php foreach ($pertemuan as $p): ?>
                            <option value="<?= $p['id_pertemuan'] ?>" 
                                    data-kelas="<?= $p['id_kelas'] ?>" 
                                    <?= $selected_pertemuan == $p['id_pertemuan'] ? 'selected' : '' ?>>
                                <?= h($p['nama_matkul']) ?> - Minggu ke-<?= $p['minggu_ke'] ?> (Kelas <?= $p['id_kelas'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="kelas" id="input_kelas" value="<?= h($selected_kelas) ?>">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
            </div>
        </form>

        <?php if ($selected_pertemuan && !empty($mahasiswa)): ?>
            <form method="POST">
                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" width="60">No</th>
                                <th width="180">NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th width="220">Status Presensi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach ($mahasiswa as $mhs): 
                                $statusSekarang = $absensiTercatat[$mhs['nim']] ?? 'Hadir';
                                $classColor = str_replace(' ', '-', $statusSekarang);
                            ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td class="fw-bold text-primary"><?= h($mhs['nim']); ?></td>
                                    <td><?= h($mhs['nama_mahasiswa']); ?></td>
                                    <td>
                                        <select name="status[<?= h($mhs['nim']); ?>]" 
                                                class="form-select form-select-sm status-select-<?= $classColor ?> status-changer">
                                            <?php foreach ($listStatus as $st): ?>
                                                <option value="<?= $st ?>" <?= $statusSekarang == $st ? 'selected' : '' ?>><?= $st ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <input type="hidden" name="id_pertemuan" value="<?= h($selected_pertemuan) ?>">
                <input type="hidden" name="id_kelas" value="<?= h($selected_kelas) ?>">
                
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-success px-5 py-2 shadow">
                        <i class="bi bi-cloud-upload me-2"></i>Simpan Perubahan Presensi
                    </button>
                </div>
            </form>
        <?php elseif ($selected_pertemuan): ?>
            <div class="text-center py-5">
                <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">Daftar mahasiswa kosong untuk kelas ini.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const selPertemuan = document.getElementById('select_pertemuan');
    const inpKelas = document.getElementById('input_kelas');

    selPertemuan.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        inpKelas.value = opt.getAttribute('data-kelas') || '';
    });

    document.querySelectorAll('.status-changer').forEach(el => {
        el.addEventListener('change', function() {
            this.className = 'form-select form-select-sm status-changer status-select-' + this.value.replace(' ', '-');
        });
    });
</script>
</body>
</html>