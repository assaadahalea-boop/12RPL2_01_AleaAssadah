<?php
// sidebar.php — Sidebar Mahasiswa TelesandiKu (Updated)
$current = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="SD.css">

<nav class="sidebar d-flex flex-column p-3">

  <!-- Avatar -->
  <div class="avatar-wrap mb-3">
    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135789.png" 
         alt="Avatar" 
         class="avatar">
  </div>

  <ul class="nav flex-column">

    <li>
      <a href="dashboard.php" 
         class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
    </li>

    <li>
      <a href="jadwal.php" 
         class="nav-link <?= $current === 'jadwal.php' ? 'active' : '' ?>">
        <i class="bi bi-calendar-week"></i> Jadwal
      </a>
    </li>

    <li>
      <a href="krs.php" 
         class="nav-link <?= $current === 'krs.php' ? 'active' : '' ?>">
        <i class="bi bi-journal-check"></i> KRS
      </a>
    </li>

    <!-- 🌟 MENU BARU: TUGAS -->
    <li>
      <a href="tugas.php" 
         class="nav-link <?= $current === 'tugas.php' ? 'active' : '' ?>">
        <i class="bi bi-clipboard-check"></i> Tugas
      </a>
    </li>

    <li>
      <a href="nilai.php" 
         class="nav-link <?= $current === 'nilai.php' ? 'active' : '' ?>">
        <i class="bi bi-book"></i> Nilai
      </a>
    </li>

    <li>
      <a href="transkip.php" 
         class="nav-link <?= $current === 'transkip.php' ? 'active' : '' ?>">
        <i class="bi bi-book-half"></i> Transkip Nilai
      </a>
    </li>
  </ul>

  <a href="logout.php" class="logout mt-auto">
    <i class="bi bi-box-arrow-right"></i> Logout
  </a>
</nav>
