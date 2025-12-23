<?php
// dashboard_dosen.php - VERSI STABIL
session_start();

// --- Konfigurasi dan Koneksi DB ---
$loggedInDosenNIK = $_SESSION['nik'] ?? 1001; 
$userName = $_SESSION['nama'] ?? 'Dosen Pengampu';

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

// Inisialisasi statistik
$stats = [
    'total_kelas' => 0,
    'mhs_bimbingan' => 0,
    'mhs_aktif' => 0,
    'sks_mengajar' => 0,
];

$schedule = [];

try {
    // --- 1. Total Kelas & 4. SKS Mengajar ---
    // Menggunakan jadwal_kelas (sinkron dengan sistem absensi/nilai)
    $queryClasses = "
        SELECT 
            jk.id_kelas, mk.sks
        FROM jadwal_kelas jk
        JOIN mata_kuliah mk ON jk.kode_matkul = mk.kode_matkul
        WHERE jk.id_dosen = ?
    ";
    $stmt = $conn->prepare($queryClasses);
    $stmt->bind_param("i", $loggedInDosenNIK);
    $stmt->execute();
    $resultClasses = $stmt->get_result();
    
    $totalSKS = 0;
    $kelasIds = [];
    while ($row = $resultClasses->fetch_assoc()) {
        $kelasIds[] = $row['id_kelas'];
        $totalSKS += (int)$row['sks'];
    }
    $stats['total_kelas'] = count($kelasIds);
    $stats['sks_mengajar'] = $totalSKS;
    $stmt->close();

    // --- 2. Mahasiswa Bimbingan ---
    $queryBimbingan = "SELECT COUNT(*) AS count FROM mahasiswa WHERE id_dosen_pembimbing = ?";
    $stmt = $conn->prepare($queryBimbingan);
    $stmt->bind_param("i", $loggedInDosenNIK);
    $stmt->execute();
    $stats['mhs_bimbingan'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();

    // --- 3. Total Mahasiswa Aktif (Yang Mengambil Kelas Dosen Ini) ---
    if (!empty($kelasIds)) {
        $inClause = implode(',', array_fill(0, count($kelasIds), '?'));
        $queryMhsAktif = "
            SELECT COUNT(DISTINCT id_mahasiswa) AS count
            FROM krs 
            WHERE id_kelas IN ($inClause) AND status = 'Disetujui'
        ";
        $stmt = $conn->prepare($queryMhsAktif);
        $stmt->bind_param(str_repeat('i', count($kelasIds)), ...$kelasIds);
        $stmt->execute();
        $stats['mhs_aktif'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    }

    // --- Jadwal Mengajar ---
    $querySchedule = "
        SELECT 
            jk.id_kelas, jk.hari, jk.jam_mulai, jk.jam_selesai, jk.ruangan,
            mk.nama AS nama_matkul, mk.semester,
            (SELECT COUNT(*) FROM krs WHERE krs.id_kelas = jk.id_kelas AND krs.status = 'Disetujui') AS jumlah_mahasiswa
        FROM jadwal_kelas jk
        JOIN mata_kuliah mk ON jk.kode_matkul = mk.kode_matkul
        WHERE jk.id_dosen = ?
        ORDER BY FIELD(jk.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jk.jam_mulai
    ";
    $stmt = $conn->prepare($querySchedule);
    $stmt->bind_param("i", $loggedInDosenNIK);
    $stmt->execute();
    $schedule = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    error_log($e->getMessage());
} finally {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
 <meta charset="UTF-8" />
 <title>Dashboard Dosen</title>
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
 <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
      body { background-color: #f4f7fc; font-family: 'Poppins', sans-serif; color: #333; margin: 0; }
      .sidebar { width: 250px; min-height: 100vh; background: linear-gradient(180deg, #1565c0, #1e88e5); box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1); position: fixed; left: 0; top: 0; padding: 25px 0; z-index: 100; text-align: center; }
      .sidebar .logo { width: 60px; height: 60px; border-radius: 50%; margin-bottom: 10px; }
      .sidebar h4 { color: #fff; font-weight: 600; margin-bottom: 30px; }
      .sidebar .nav-link { color: #dfe9ff; font-weight: 500; padding: 10px 20px; margin: 6px 15px; border-radius: 8px; display: flex; align-items: center; transition: 0.3s; text-decoration: none; }
      .sidebar .nav-link i { margin-right: 10px; font-size: 18px; }
      .sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: rgba(255, 255, 255, 0.2); color: #fff; }
      .content { margin-left: 270px; padding: 30px; background-color: #f4f7fc; min-height: 100vh; }
      h4 span { color: #1976d2; }
      .card-stat { border: none; border-radius: 15px; padding: 20px; color: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: space-between; transition: 0.3s; }
      .card-stat:hover { transform: translateY(-3px); box-shadow: 0 6px 14px rgba(0,0,0,0.15); }
      .card-stat i { font-size: 35px; opacity: 0.85; }
      .bg-blue { background: linear-gradient(135deg, #1565c0, #1e88e5); }
      .bg-purple { background: linear-gradient(135deg, #7b1fa2, #9c27b0); }
      .bg-teal { background: linear-gradient(135deg, #00897b, #26a69a); }
      .bg-orange { background: linear-gradient(135deg, #f57c00, #fb8c00); }
      .chart-box { background-color: #fff; border-radius: 15px; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08); padding: 20px; }
      .chart-box h5 { font-weight: 600; margin-bottom: 15px; }
      @media (max-width: 992px) { .sidebar { position: relative; width: 100%; height: auto; } .content { margin-left: 0; padding: 16px; } }
  </style>
</head>
<body>

 <?php if (file_exists('sidebar.php')) { include 'sidebar.php'; } ?>

 <div class="content">
   <h4 class="fw-semibold mb-4">Selamat Datang, <span><?= h($userName); ?></span> 👋</h4>

   <div class="row g-4 mb-4">
     <div class="col-md-3">
       <div class="card-stat bg-blue">
         <div>
           <p class="mb-1">Total Kelas Diampu</p>
           <h3><?= h($stats['total_kelas']); ?></h3>
         </div>
         <i class="bi bi-people"></i>
       </div>
     </div>
     <div class="col-md-3">
       <div class="card-stat bg-purple">
         <div>
           <p class="mb-1">Mahasiswa Bimbingan</p>
           <h3><?= h($stats['mhs_bimbingan']); ?></h3>
         </div>
         <i class="bi bi-person-workspace"></i>
       </div>
     </div>
     <div class="col-md-3">
       <div class="card-stat bg-teal">
         <div>
           <p class="mb-1">Total Mahasiswa Aktif</p>
           <h3><?= h($stats['mhs_aktif']); ?></h3>
         </div>
         <i class="bi bi-person-check"></i>
       </div>
     </div>
     <div class="col-md-3">
       <div class="card-stat bg-orange">
         <div>
           <p class="mb-1">Total SKS Mengajar</p>
           <h3><?= h($stats['sks_mengajar']); ?></h3>
         </div>
         <i class="bi bi-book-half"></i>
       </div>
     </div>
   </div>

   <div class="row g-4">
     <div class="col-12">
       <div class="chart-box">
         <h5>📅 Jadwal Mengajar Semester Ini</h5>
         <div class="table-responsive">
           <table class="table table-striped align-middle">
             <thead class="table-primary text-center">
               <tr>
                 <th>Hari</th>
                 <th>Mata Kuliah</th>
                 <th>ID Kelas</th>
                 <th>Smt</th>
                 <th>Mhs</th>
                 <th>Waktu</th>
                 <th>Ruang</th>
               </tr>
             </thead>
             <tbody class="text-center">
               <?php if (!empty($schedule)): ?>
                 <?php foreach ($schedule as $row): ?>
                   <tr>
                     <td class="fw-bold"><?= h($row['hari']); ?></td>
                     <td class="text-start"><?= h($row['nama_matkul']); ?></td>
                     <td><?= h($row['id_kelas']); ?></td>
                     <td><?= h($row['semester']); ?></td>
                     <td><span class="badge bg-secondary"><?= h($row['jumlah_mahasiswa']); ?></span></td>
                     <td><?= date("H:i", strtotime($row['jam_mulai'])) . ' - ' . date("H:i", strtotime($row['jam_selesai'])); ?></td>
                     <td><span class="badge bg-info text-dark"><?= h($row['ruangan']); ?></span></td>
                   </tr>
                 <?php endforeach; ?>
               <?php else: ?>
                 <tr>
                   <td colspan="7" class="py-4 text-muted small">Belum ada jadwal yang diinput untuk NIK ini.</td>
                 </tr>
               <?php endif; ?>
             </tbody>
           </table>
         </div>
       </div>
     </div>
   </div>

 </div> 
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>