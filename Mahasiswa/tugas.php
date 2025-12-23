<?php
session_start();

// Konfigurasi Database
$dbHost = '127.0.0.1';
$dbName = 'universitas';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die("DB Error: " . $e->getMessage());
}

// Simulasi Login (Andi Wijaya)
if (!isset($_SESSION['id_mahasiswa'])) {
    $_SESSION['id_mahasiswa'] = 201; 
}
$id_mahasiswa = $_SESSION['id_mahasiswa'];

/* -------------------------------------------------------
    1. PROSES UPLOAD TUGAS
---------------------------------------------------------*/
if (isset($_POST['upload'])) {
    $id_tugas = $_POST['id_tugas'];
    
    // Ambil Deadline
    $stDeadline = $pdo->prepare("SELECT tanggal_deadline FROM tugas WHERE id_tugas = ?");
    $stDeadline->execute([$id_tugas]);
    $deadline = $stDeadline->fetchColumn();

    // Validasi Waktu
    if ($deadline && date('Y-m-d') > $deadline) {
        echo "<script>alert('Batas waktu pengumpulan sudah berakhir!'); window.location='tugas.php';</script>";
        exit;
    }

    if (!empty($_FILES['berkas']['name'])) {
        $filename = time() . "_" . preg_replace('/[^A-Za-z0-9.]/', '', $_FILES['berkas']['name']);
        if (!is_dir("uploads")) mkdir("uploads", 0777, true);
        
        if (move_uploaded_file($_FILES['berkas']['tmp_name'], "uploads/" . $filename)) {
            // Cek apakah sudah pernah mengumpulkan
            $stmtCek = $pdo->prepare("SELECT id_pengumpulan FROM tugas_pengumpulan WHERE id_tugas = ? AND id_mahasiswa = ?");
            $stmtCek->execute([$id_tugas, $id_mahasiswa]);
            
            if ($stmtCek->rowCount() > 0) {
                $pdo->prepare("UPDATE tugas_pengumpulan SET file_upload = ?, tanggal_submit = NOW() WHERE id_tugas = ? AND id_mahasiswa = ?")
                    ->execute([$filename, $id_tugas, $id_mahasiswa]);
            } else {
                $pdo->prepare("INSERT INTO tugas_pengumpulan (id_tugas, id_mahasiswa, file_upload, tanggal_submit) VALUES (?,?,?, NOW())")
                    ->execute([$id_tugas, $id_mahasiswa, $filename]);
            }
        }
    }
    header("Location: tugas.php?status=success");
    exit;
}

/* -------------------------------------------------------
    2. QUERY AMBIL TUGAS (VERSI FIX DUPLIKASI)
---------------------------------------------------------*/
// Kita menggunakan DISTINCT atau GROUP BY untuk mencegah duplikasi akibat join jadwal_kelas
$query = "
SELECT 
    t.id_tugas, 
    t.nama_tugas, 
    t.deskripsi, 
    t.tanggal_deadline,
    mk.nama AS nama_matkul,
    p.minggu_ke,
    tp.file_upload, 
    tp.tanggal_submit
FROM tugas t
JOIN pertemuan p ON t.id_pertemuan = p.id_pertemuan
JOIN kelas c ON p.id_kelas = c.id_kelas
JOIN mahasiswa m ON c.id_kelas = m.id_kelas
-- JOIN ke jadwal_kelas dan mata_kuliah secara spesifik
JOIN jadwal_kelas jk ON jk.id_kelas = c.id_kelas
JOIN mata_kuliah mk ON jk.kode_matkul = mk.kode_matkul
LEFT JOIN tugas_pengumpulan tp ON t.id_tugas = tp.id_tugas AND tp.id_mahasiswa = m.id_mahasiswa
WHERE m.id_mahasiswa = ? 
  AND mk.nama = 'Pemrograman WEB' -- Filter spesifik agar tidak bentrok dengan Matematika
ORDER BY t.tanggal_deadline ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$id_mahasiswa]);
$data = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>E-Learning - Tugas Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI', sans-serif; }
        .main-content { margin-left: 260px; padding: 30px; }
        .task-card { border: none; border-radius: 15px; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .task-card:hover { transform: translateY(-5px); }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>

<div class="d-flex">
    <?php if (file_exists('sidebar.php')) include 'sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <header class="mb-4">
            <h2 class="fw-bold">📘 Tugas Aktif</h2>
            <p class="text-muted">Kelola pengumpulan tugas mata kuliah Anda di sini.</p>
        </header>

        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4">Tugas Anda berhasil diunggah!</div>
        <?php endif; ?>

        <div class="row">
            <?php if (empty($data)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted opacity-25"></i>
                    <p class="mt-3 text-muted">Belum ada tugas untuk mata kuliah ini.</p>
                </div>
            <?php else: ?>
                <?php foreach ($data as $t): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card task-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-primary-subtle text-primary mb-2">Minggu <?= $t['minggu_ke'] ?></span>
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($t['nama_tugas']) ?></h5>
                                <p class="text-primary small fw-bold text-uppercase mb-0"><?= htmlspecialchars($t['nama_matkul']) ?></p>
                            </div>
                            <?php if ($t['file_upload']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sudah Dikirim</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Belum Kirim</span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="text-muted small mb-4"><?= nl2br(htmlspecialchars($t['deskripsi'])) ?></p>
                        
                        <div class="p-3 bg-light rounded-3 mb-4">
                            <div class="text-danger small fw-bold">
                                <i class="bi bi-calendar-event me-1"></i> Deadline: <?= date("d F Y", strtotime($t['tanggal_deadline'])) ?>
                            </div>
                        </div>

                        <form method="post" enctype="multipart/form-data" class="mt-auto">
                            <input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>">
                            <div class="input-group">
                                <input type="file" name="berkas" class="form-control" <?= $t['file_upload'] ? '' : 'required' ?>>
                                <button name="upload" class="btn btn-dark" type="submit">
                                    <i class="bi bi-upload me-1"></i> <?= $t['file_upload'] ? 'Ganti' : 'Kirim' ?>
                                </button>
                            </div>
                            <?php if ($t['file_upload']): ?>
                                <div class="mt-2 small text-muted">
                                    <i class="bi bi-file-earmark-check text-success"></i> File: <a href="uploads/<?= $t['file_upload'] ?>" target="_blank" class="text-decoration-none"><?= $t['file_upload'] ?></a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>