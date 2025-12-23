<?php
// dashboard.php - Dashboard Admin Dinamis

define('DB_HOST', 'localhost');
define('DB_NAME', 'universitas');
define('DB_USER', 'root');
define('DB_PASS', ''); // sesuaikan jika pakai password

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// === Statistik utama ===
$totalMahasiswa = $pdo->query("SELECT COUNT(*) FROM mahasiswa")->fetchColumn();
$totalDosen = $pdo->query("SELECT COUNT(*) FROM dosen")->fetchColumn();
$totalKelas = $pdo->query("SELECT COUNT(*) FROM kelas")->fetchColumn();
$totalMatkul = $pdo->query("SELECT COUNT(*) FROM mata_kuliah")->fetchColumn();

// === Data Mahasiswa ===
$sqlMahasiswa = "
    SELECT 
        m.nim, 
        m.nama_mahasiswa, 
        p.nama AS nama_prodi, 
        YEAR(CURDATE()) - m.angkatan + 1 AS semester, 
        m.status_akademik
    FROM mahasiswa m
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    ORDER BY m.id_mahasiswa ASC
";
$mahasiswa = $pdo->query($sqlMahasiswa)->fetchAll();

// === Data Dosen ===
// Query disesuaikan untuk mengambil 'jabatan' sebagai 'bidang_keahlian' dan 'prodi'
$sqlDosen = "
    SELECT 
        d.nik, 
        d.nama, 
        d.jabatan AS bidang_keahlian, 
        d.email,
        p.nama AS nama_prodi,
        COUNT(k.id_kelas) AS jumlah_kelas
    FROM dosen d
    LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
    LEFT JOIN kelas k ON d.nik = k.id_dosen
    GROUP BY d.nik, d.nama, d.jabatan, d.email, p.nama
    ORDER BY d.nama ASC
";
$dosen = $pdo->query($sqlDosen)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="Style.css">
   <style>
    h4 span{color:#1976d2;}
    .table th{background-color:#1976d2 !important;color:white;}
    .btn-add{background:linear-gradient(135deg,#1565c0,#1e88e5);color:white;}
    .btn-add:hover{background:linear-gradient(135deg,#0d47a1,#1565c0);}
    .modal-header{background:#1976d2;color:white;}
    @media (max-width:992px){.sidebar{position:relative;width:100%;}.content{margin-left:0;}}
  </style>
  <style>
    body {
      background-color: #f4f7fc;
      font-family: 'Poppins', sans-serif;
      color: #333;
    }

    .content {
      margin-left: 270px;
      padding: 30px;
      background-color: #f4f7fc;
      min-height: 100vh;
    }

    h4 span {
      color: #1976d2;
    }

    .card-stat {
      border: none;
      border-radius: 15px;
      padding: 20px;
      color: #fff;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: 0.3s;
    }

    .card-stat:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 14px rgba(0,0,0,0.15);
    }

    .card-stat i {
      font-size: 35px;
      opacity: 0.85;
    }

    .bg-blue { background: linear-gradient(135deg, #1565c0, #1e88e5); }
    .bg-green { background: linear-gradient(135deg, #2e7d32, #43a047); }
    .bg-orange { background: linear-gradient(135deg, #ef6c00, #fb8c00); }
    .bg-purple { background: linear-gradient(135deg, #6a1b9a, #8e24aa); }

    .chart-box {
      background-color: #fff;
      border-radius: 15px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
      padding: 20px;
    }

    .chart-box h5 {
      font-weight: 600;
      margin-bottom: 15px;
    }

    @media (max-width: 992px) {
      .sidebar { position: relative; width: 100%; height: auto; }
      .content { margin-left: 0; }
    }
  </style>
</head>
<body>
   <?php include 'sidebar.php'; ?>

  <div class="content">
    <h4 class="fw-semibold mb-4">Selamat Datang, <span>Admin</span> 👋</h4>

    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="card-stat bg-blue"><div><p>Total Mahasiswa</p><h3><?= $totalMahasiswa ?></h3></div><i class="bi bi-mortarboard"></i></div>
      </div>
      <div class="col-md-3">
        <div class="card-stat bg-green"><div><p>Total Dosen</p><h3><?= $totalDosen ?></h3></div><i class="bi bi-person-badge"></i></div>
      </div>
      <div class="col-md-3">
        <div class="card-stat bg-orange"><div><p>Total Kelas</p><h3><?= $totalKelas ?></h3></div><i class="bi bi-people"></i></div>
      </div>
      <div class="col-md-3">
        <div class="card-stat bg-purple"><div><p>Total Mata Kuliah</p><h3><?= $totalMatkul ?></h3></div><i class="bi bi-journal-code"></i></div>
      </div>
    </div>

    <div class="chart-box mb-4">
      <h5>🎓 Data Mahasiswa</h5>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead class="table-primary text-center">
            <tr><th>NIM</th><th>Nama</th><th>Program Studi</th><th>Semester</th><th>Status</th></tr>
          </thead>
          <tbody class="text-center">
            <?php foreach ($mahasiswa as $m): ?>
              <tr>
                <td><?= htmlspecialchars($m['nim']) ?></td>
                <td><?= htmlspecialchars($m['nama_mahasiswa']) ?></td>
                <td><?= htmlspecialchars($m['nama_prodi'] ?? '-') ?></td>
                <td><?= htmlspecialchars($m['semester'] ?? '-') ?></td>
                <td><span class="badge bg-<?= $m['status_akademik']=='aktif'?'success':($m['status_akademik']=='cuti'?'warning text-dark':'secondary') ?>">
                  <?= ucfirst($m['status_akademik']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="chart-box">
      <h5>👨‍🏫 Data Dosen</h5>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead class="table-primary text-center">
            <tr><th>NIK/NIP</th><th>Nama</th><th>Prodi</th><th>Jumlah Kelas</th><th>Email</th></tr>
          </thead>
          <tbody class="text-center">
            <?php foreach ($dosen as $d): ?>
              <tr>
                <td><?= htmlspecialchars($d['nik']) ?></td>
                <td><?= htmlspecialchars($d['nama']) ?></td>
                <td><?= htmlspecialchars($d['nama_prodi'] ?? '-') ?></td>
                <td><?= htmlspecialchars($d['jumlah_kelas']) ?></td>
                <td><?= htmlspecialchars($d['email']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>