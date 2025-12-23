<!-- sidebar.php -->
<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
  <img src="https://cdn-icons-png.flaticon.com/512/3135/3135789.png" class="logo" alt="Admin Logo">
  <h4>Dashboard Admin</h4>

  <a href="dashboard.php" class="nav-link <?= $current=='dashboard.php'?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="datafakultas.php" class="nav-link <?= $current=='datafakultas.php'?'active':'' ?>"><i class="bi bi-bank"></i> Fakultas</a>
  <a href="dataprodi.php" class="nav-link <?= $current=='dataprodi.php'?'active':'' ?>"><i class="bi bi-diagram-3"></i> Prodi</a>
  <a href="datadosen.php" class="nav-link <?= $current=='datadosen.php'?'active':'' ?>"><i class="bi bi-person-badge"></i> Dosen</a>
  <a href="datamahasiswa.php" class="nav-link <?= $current=='datamahasiswa.php'?'active':'' ?>"><i class="bi bi-people"></i> Mahasiswa</a>
  <a href="datakelas.php" class="nav-link <?= $current=='datakelas.php'?'active':'' ?>"><i class="bi bi-grid"></i> Kelas</a>
  <a href="datamatakuliah.php" class="nav-link <?= $current=='datamatakuliah.php'?'active':'' ?>"><i class="bi bi-book"></i> Mata Kuliah</a>
  <a href="penerimaMaba.php" class="nav-link <?= $current=='penerimaMaba.php'?'active':'' ?>"><i class="bi bi-book"></i> PMB</a>
  <a href="logout.php" class="nav-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>
