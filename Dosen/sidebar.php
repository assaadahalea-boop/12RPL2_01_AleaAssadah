<?php
// sidebar.php
$current = basename($_SERVER['PHP_SELF']);
// Pastikan variabel $userName mengambil dari sesi atau menggunakan default
$userName = $_SESSION['nama'] ?? 'Dr. Ahmad Saputra'; 
?>
<aside class="sidebar">
  <div class="sidebar-top">
    <div class="avatar-wrap">
      <img src="https://cdn-icons-png.flaticon.com/512/3135/3135789.png" alt="Avatar" class="avatar">
      <link rel="stylesheet" href="style.css">
    </div>

    <div class="sidebar-title">
      <div class="title-main">Dashboard Dosen</div>
      <div class="title-sub"><?= htmlspecialchars($userName); ?></div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <a href="dashboard.php" class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a>
    <a href="tugas_matakuliah.php" class="nav-link <?= $current === 'tugas_matakuliah.php' ? 'active' : '' ?>"><i class="bi bi-journal-bookmark"></i> <span>Mata Kuliah</span></a>
    
        <a href="bimbingan.php" class="nav-link <?= $current === 'bimbingan.php' ? 'active' : '' ?>"><i class="bi bi-people"></i> <span>Persetujuan KRS & Bimbingan</span></a>
    
    <a href="nilai.php" class="nav-link <?= $current === 'nilai.php' ? 'active' : '' ?>"><i class="bi bi-card-checklist"></i> <span>Penilaian Tugas</span></a>
    <a href="nilai_akhir_form.php" class="nav-link <?= $current === 'nilai_akhir_form.php' ? 'active' : '' ?>"><i class="bi bi-bar-chart"></i> <span>Nilai Akhir</span></a>
    <a href="absensi.php" class="nav-link <?= $current === 'absensi.php' ? 'active' : '' ?>"><i class="bi bi-calendar-check"></i> <span>Absensi</span></a>
    <a href="logout.php" class="nav-link logout"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a>
  </nav>
</aside>