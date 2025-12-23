<?php
session_start();

// Default session untuk testing (Mahasiswa ID 201 sesuai data SQL kita)
$id_mahasiswa = $_SESSION['id_mahasiswa'] ?? 201; 

// Koneksi database
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=universitas;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// Ambil data mahasiswa + prodi
$q = $pdo->prepare("SELECT m.*, p.nama AS prodi 
                    FROM mahasiswa m 
                    JOIN prodi p ON m.id_prodi = p.id_prodi 
                    WHERE m.id_mahasiswa = ?");
$q->execute([$id_mahasiswa]);
$mhs = $q->fetch();

// ===============================
// 1️⃣ Ambil Mata Kuliah & Jadwal (DIPERBAIKI GROUP BY-NYA)
// ===============================
$sqlAll = "SELECT mk.kode_matkul, mk.nama AS nama_matkul, mk.sks, mk.semester, 
                  kl.id_kelas, jk.hari, jk.ruangan, d.nama AS dosen
           FROM mata_kuliah mk
           JOIN jadwal_kelas jk ON mk.kode_matkul = jk.kode_matkul
           JOIN kelas kl ON jk.id_kelas = kl.id_kelas
           LEFT JOIN dosen d ON jk.id_dosen = d.nik
           GROUP BY kl.id_kelas, mk.kode_matkul, mk.nama, mk.sks, mk.semester, jk.hari, jk.ruangan, d.nama
           ORDER BY mk.semester, mk.nama";
$stmtAll = $pdo->prepare($sqlAll);
$stmtAll->execute();
$allMatkul = $stmtAll->fetchAll();

// ===============================
// 2️⃣ Ambil KRS yang sudah diambil (DIPERBAIKI GROUP BY-NYA)
// ===============================
$sqlKrs = "SELECT k.id_krs, mk.kode_matkul, mk.nama, mk.sks, k.status, 
                  d.nama AS dosen, jk.hari, jk.ruangan, kl.id_kelas
           FROM krs k
           JOIN kelas kl ON k.id_kelas = kl.id_kelas
           JOIN jadwal_kelas jk ON kl.id_kelas = jk.id_kelas
           JOIN mata_kuliah mk ON k.kode_matkul = mk.kode_matkul
           LEFT JOIN dosen d ON jk.id_dosen = d.nik
           WHERE k.id_mahasiswa = :idm
           GROUP BY k.id_krs, mk.kode_matkul, mk.nama, mk.sks, k.status, d.nama, jk.hari, jk.ruangan, kl.id_kelas
           ORDER BY k.id_krs DESC";
$stmtKrs = $pdo->prepare($sqlKrs);
$stmtKrs->execute([':idm' => $id_mahasiswa]);
$ambil = $stmtKrs->fetchAll(PDO::FETCH_ASSOC);

// Daftar Kode Matkul yang sudah diambil
$sudahAmbilMatkul = array_column($ambil, 'kode_matkul');

// ===============================
// 3️⃣ Proses submit KRS
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_kelas'])) {
    $selected_kelas = $_POST['id_kelas']; 
    
    foreach ($selected_kelas as $id_kelas) {
        $stmtCek = $pdo->prepare("SELECT kode_matkul FROM jadwal_kelas WHERE id_kelas = ? LIMIT 1");
        $stmtCek->execute([$id_kelas]);
        $res = $stmtCek->fetch();
        
        if ($res) {
            $kode_matkul = $res['kode_matkul'];

            $stmtDouble = $pdo->prepare("SELECT COUNT(*) FROM krs WHERE id_mahasiswa = ? AND kode_matkul = ?");
            $stmtDouble->execute([$id_mahasiswa, $kode_matkul]);
            
            if ($stmtDouble->fetchColumn() == 0) {
                $ins = $pdo->prepare("INSERT INTO krs (id_mahasiswa, id_kelas, kode_matkul, tanggal_pengajuan, status) 
                                      VALUES (?, ?, ?, CURDATE(), 'Menunggu')");
                $ins->execute([$id_mahasiswa, $id_kelas, $kode_matkul]);
            }
        }
    }
    header("Location: krs.php?msg=success");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>KRS Online - Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .sidebar-space { margin-left: 260px; padding: 30px; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); border-radius: 12px; }
        .table thead { background-color: #f8f9fa; }
    </style>
</head>
<body>

<?php if (file_exists('sidebar.php')) include 'sidebar.php'; ?>

<div class="sidebar-space">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Kartu Rencana Studi (KRS)</h3>
            <p class="text-secondary">Selamat datang, <?= htmlspecialchars($mhs['nama_mahasiswa'] ?? 'Mahasiswa') ?></p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary p-2"><?= htmlspecialchars($mhs['prodi'] ?? '-') ?></span>
            <div class="small text-muted mt-1">NIM: <?= htmlspecialchars($mhs['nim'] ?? '-') ?></div>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> KRS Berhasil diajukan!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Daftar Kelas Tersedia</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th width="50">Pilih</th>
                                <th>Mata Kuliah</th>
                                <th>SKS</th>
                                <th>Smt</th>
                                <th>Jadwal & Ruangan</th>
                                <th>Dosen Pengajar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allMatkul as $row): ?>
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="id_kelas[]" value="<?= $row['id_kelas'] ?>" 
                                        <?= in_array($row['kode_matkul'], $sudahAmbilMatkul) ? 'disabled checked' : '' ?>>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary"><?= $row['nama_matkul'] ?></div>
                                    <div class="small text-muted"><?= $row['kode_matkul'] ?></div>
                                </td>
                                <td><?= $row['sks'] ?></td>
                                <td><?= $row['semester'] ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-calendar3 me-1"></i> <?= $row['hari'] ?>
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-geo-alt me-1"></i> <?= $row['ruangan'] ?>
                                    </span>
                                </td>
                                <td><small><?= $row['dosen'] ?: '<i class="text-muted">Belum ditentukan</i>' ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-send me-2"></i>Ajukan KRS Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Status Pengajuan</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Kelas</th>
                        <th>Jadwal</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ambil)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada KRS yang diajukan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($ambil as $k): ?>
                    <tr>
                        <td class="ps-3"><?= $k['nama'] ?></td>
                        <td><?= $k['sks'] ?></td>
                        <td><strong><?= $k['id_kelas'] ?></strong></td>
                        <td><small><?= $k['hari'] ?>, <?= $k['ruangan'] ?></small></td>
                        <td class="text-center">
                            <?php 
                            $badge = ['Menunggu' => 'bg-warning text-dark', 'Disetujui' => 'bg-success', 'Ditolak' => 'bg-danger'];
                            $status = $k['status'] ?: 'Menunggu';
                            ?>
                            <span class="badge <?= $badge[$status] ?>"><?= $status ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>