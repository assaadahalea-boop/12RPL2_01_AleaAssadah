<?php
session_start();

// --- 1. SINKRONISASI SESI ---
// Pastikan menggunakan key yang konsisten. Di sini kita gunakan 'nik'.
$loggedInDosenNIK = $_SESSION['nik'] ?? 1001; 
$userName = $_SESSION['nama'] ?? null; 

// --- 2. KONFIGURASI DATABASE ---
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
    die("Koneksi database gagal: " . htmlspecialchars($e->getMessage()));
}

// Helper sanitasi
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES); }

// --- 3. LOGIKA UPDATE STATUS KRS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_krs'])) {
    $id_krs = (int)$_POST['id_krs'];
    $action = $_POST['action_krs'] === 'approve' ? 'Disetujui' : 'Ditolak';

    if ($loggedInDosenNIK && $id_krs) {
        // Validasi: Pastikan mahasiswa tersebut adalah bimbingan dosen yang sedang login
        $stmt = $pdo->prepare("
            UPDATE krs k
            JOIN mahasiswa m ON k.id_mahasiswa = m.id_mahasiswa
            SET k.status = ?
            WHERE k.id_krs = ? AND m.id_dosen_pembimbing = ? AND k.status = 'Menunggu'
        ");
        
        $stmt->execute([$action, $id_krs, $loggedInDosenNIK]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['msg'] = "KRS berhasil diperbarui menjadi '{$action}'.";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Gagal memperbarui KRS. Pastikan data benar dan berstatus 'Menunggu'.";
            $_SESSION['msg_type'] = "danger";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// --- 4. AMBIL DATA DOSEN & MAHASISWA ---
$mahasiswaList = [];
$krsWaiting = [];

if ($loggedInDosenNIK) {
    // Ambil Nama Dosen jika belum ada di sesi
    if (!$userName) {
        $stmtD = $pdo->prepare("SELECT nama FROM dosen WHERE nik = ?");
        $stmtD->execute([$loggedInDosenNIK]);
        $userName = $stmtD->fetchColumn();
    }

    // Ambil Daftar Mahasiswa Bimbingan
    $stmtMhs = $pdo->prepare("
        SELECT m.nim, m.nama_mahasiswa, p.nama AS prodi, m.angkatan, m.status_akademik
        FROM mahasiswa m
        LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
        WHERE m.id_dosen_pembimbing = ?
        ORDER BY m.nama_mahasiswa ASC
    ");
    $stmtMhs->execute([$loggedInDosenNIK]);
    $mahasiswaList = $stmtMhs->fetchAll();

    // --- PERBAIKAN QUERY DI SINI (Menghapus kl.kode_matkul yang error) ---
    $sqlKRS = "
        SELECT 
            k.id_krs, m.nim, m.nama_mahasiswa, 
            mk.nama AS nama_matkul, k.id_kelas, k.tanggal_pengajuan
        FROM krs k
        JOIN mahasiswa m ON k.id_mahasiswa = m.id_mahasiswa
        LEFT JOIN mata_kuliah mk ON k.kode_matkul = mk.kode_matkul
        WHERE m.id_dosen_pembimbing = ? 
          AND k.status = 'Menunggu'
        ORDER BY k.tanggal_pengajuan ASC
    ";
    $stmtKRS = $pdo->prepare($sqlKRS);
    $stmtKRS->execute([$loggedInDosenNIK]);
    $krsWaiting = $stmtKRS->fetchAll();
}

$msg = $_SESSION['msg'] ?? '';
$msgType = $_SESSION['msg_type'] ?? 'info';
unset($_SESSION['msg'], $_SESSION['msg_type']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bimbingan & KRS - Dashboard Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7fc; font-family: 'Poppins', sans-serif; }
        .sidebar { width: 250px; min-height: 100vh; background: linear-gradient(180deg, #1565c0, #1e88e5); position: fixed; padding: 25px 0; color: white; }
        .content { margin-left: 270px; padding: 30px; }
        .section-box { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 30px; }
        @media (max-width: 992px) { .sidebar { position: relative; width: 100%; height: auto; } .content { margin-left: 0; } }
    </style>
</head>
<body>

<?php if (file_exists('sidebar.php')) include 'sidebar.php'; ?>

<div class="content">
    <h4 class="fw-bold text-primary mb-4">
        <i class="bi bi-people"></i> Mahasiswa Bimbingan & Persetujuan KRS
        <br><small class="text-muted fs-6">Dosen: <?= h($userName ?? 'Tidak Diketahui'); ?> (NIK: <?= h($loggedInDosenNIK); ?>)</small>
    </h4>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType; ?> alert-dismissible fade show">
            <?= h($msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <h5 class="fw-bold mb-3 text-danger"><i class="bi bi-file-earmark-check"></i> Persetujuan KRS (Menunggu)</h5>
    <div class="section-box">
        <?php if (!empty($krsWaiting)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-danger">
                        <tr>
                            <th>NIM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Mata Kuliah</th>
                            <th>ID Kelas</th>
                            <th>Tgl. Pengajuan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($krsWaiting as $krs): ?>
                            <tr>
                                <td><?= h($krs['nim']); ?></td>
                                <td><?= h($krs['nama_mahasiswa']); ?></td>
                                <td><?= h($krs['nama_matkul'] ?? 'Mata Kuliah Tidak Ditemukan'); ?></td>
                                <td><?= $krs['id_kelas'] ?: '<span class="text-muted"><i>Belum Pilih Kelas</i></span>'; ?></td>
                                <td><?= $krs['tanggal_pengajuan'] ? date("d M Y", strtotime($krs['tanggal_pengajuan'])) : '-'; ?></td>
                                <td class="text-center">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_krs" value="<?= $krs['id_krs']; ?>">
                                        <button type="submit" name="action_krs" value="approve" class="btn btn-sm btn-success" onclick="return confirm('Setujui?')">
                                            <i class="bi bi-check-lg"></i> Setujui
                                        </button>
                                        <button type="submit" name="action_krs" value="reject" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tolak?')">
                                            <i class="bi bi-x-lg"></i> Tolak
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-3">
                <i class="bi bi-check2-circle text-success fs-1"></i>
                <p class="text-muted mt-2">Tidak ada pengajuan KRS yang menunggu persetujuan.</p>
            </div>
        <?php endif; ?>
    </div>

    <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-person-lines-fill"></i> Daftar Mahasiswa Bimbingan</h5>
    <div class="section-box">
        <?php if (!empty($mahasiswaList)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Prodi</th>
                            <th>Angkatan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mahasiswaList as $i => $m): ?>
                            <tr>
                                <td><?= $i + 1; ?></td>
                                <td><?= h($m['nim']); ?></td>
                                <td><?= h($m['nama_mahasiswa']); ?></td>
                                <td><?= h($m['prodi']); ?></td>
                                <td><?= h($m['angkatan']); ?></td>
                                <td>
                                    <?php 
                                    $status = strtolower($m['status_akademik']);
                                    $badge = ($status == 'aktif') ? 'bg-success' : 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $badge; ?>"><?= ucfirst($status); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="alert alert-info">Belum ada mahasiswa bimbingan yang terdaftar.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>