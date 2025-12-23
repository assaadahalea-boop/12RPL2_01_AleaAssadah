<?php
// jadwal.php — Versi Perbaikan JOIN Tabel

$dbHost = '127.0.0.1'; 
$dbName = 'universitas';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

session_start();
// Gunakan ID Mahasiswa 201 (Andi Wijaya) sebagai default session
if (!isset($_SESSION['id_mahasiswa'])) {
    $_SESSION['id_mahasiswa'] = 201; 
}
$id_mahasiswa = (int) $_SESSION['id_mahasiswa'];

// 1. Ambil ID Kelas milik mahasiswa tersebut dari tabel mahasiswa
$stmtMhs = $pdo->prepare("SELECT id_kelas FROM mahasiswa WHERE id_mahasiswa = ?");
$stmtMhs->execute([$id_mahasiswa]);
$mhs = $stmtMhs->fetch();
$id_kelas_mhs = $mhs['id_kelas'] ?? 0;

// 2. Query Jadwal: Mengambil waktu dari tabel 'kelas' (alias c)
// dan menghubungkannya dengan 'jadwal_kelas' (alias jk)
$sql = "
SELECT 
    mk.kode_matkul,
    mk.nama AS mata_kuliah,
    d.nama AS dosen_pengampu,
    jk.id_kelas,            
    c.jam_mulai,    
    c.jam_selesai,  
    c.hari,         
    c.ruangan AS ruang        
FROM jadwal_kelas jk
JOIN mata_kuliah mk ON jk.kode_matkul = mk.kode_matkul
JOIN kelas c ON jk.id_kelas = c.id_kelas
LEFT JOIN dosen d ON d.nik = jk.id_dosen
WHERE jk.id_kelas = :id_kelas
ORDER BY FIELD(c.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), c.jam_mulai
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id_kelas' => $id_kelas_mhs]);
$rows = $stmt->fetchAll();

function formatWaktu($jamMulai, $jamSelesai) {
    if (!$jamMulai || $jamMulai == '00:00:00') return 'TBD';
    return date('H:i', strtotime($jamMulai)) . ' - ' . date('H:i', strtotime($jamSelesai));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Jadwal Kuliah Saya - Kelas <?= htmlspecialchars($id_kelas_mhs) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 260px; padding: 40px; }
        .jadwal-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: none; overflow: hidden; }
        .day-indicator { padding: 6px 14px; border-radius: 10px; background: #eef2ff; color: #4338ca; font-weight: 700; font-size: 13px; }
        .avatar-sm { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>

    <div class="d-flex">
        <?php if (file_exists('sidebar.php')) include 'sidebar.php'; ?>

        <div class="main-content flex-grow-1">
            <header class="mb-5">
                <h2 class="fw-bold text-dark">Jadwal Perkuliahan</h2>
                <p class="text-secondary">Menampilkan jadwal untuk <strong>Kelas <?= htmlspecialchars($id_kelas_mhs) ?></strong></p>
            </header>

            <div class="card jadwal-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3">Waktu & Hari</th>
                                <th class="py-3">Mata Kuliah</th>
                                <th class="py-3">Dosen Pengampu</th>
                                <th class="py-3">Ruangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <i class="bi bi-calendar-x display-4 text-muted"></i>
                                        <p class="mt-3 text-muted">Tidak ada jadwal ditemukan untuk kelas Anda.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td class="ps-4 py-4">
                                        <div class="day-indicator mb-2 d-inline-block"><?= htmlspecialchars($r['hari'] ?? 'TBD') ?></div>
                                        <div class="small fw-bold text-muted">
                                            <i class="bi bi-clock me-1"></i><?= formatWaktu($r['jam_mulai'], $r['jam_selesai']) ?>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($r['mata_kuliah']) ?></div>
                                        <div class="text-primary small fw-medium"><?= htmlspecialchars($r['kode_matkul']) ?></div>
                                    </td>
                                    <td class="py-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light rounded-circle me-2">
                                                <i class="bi bi-person-badge text-secondary"></i>
                                            </div>
                                            <span class="small text-muted"><?= htmlspecialchars($r['dosen_pengampu'] ?? 'Staff Pengajar') ?></span>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        <span class="badge bg-light text-dark border fw-normal px-3 py-2">
                                            <i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($r['ruang'] ?? '-') ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>