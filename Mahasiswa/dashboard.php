<?php
// dashboard.php
// Konfigurasi DB (sesuaikan jika perlu)
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
// untuk testing jika belum ada login
if (!isset($_SESSION['id_mahasiswa'])) {
    $_SESSION['id_mahasiswa'] = 1;
}
$id_mahasiswa = $_SESSION['id_mahasiswa'];

// 1) IPK kumulatif (mapping grade -> 4.0 dan fallback nilai_akhir -> linear)
$query = "
SELECT m.nama_mahasiswa,
       ROUND(
         SUM(
           (CASE
             WHEN UPPER(TRIM(na.grade)) = 'A'  THEN 4.0
             WHEN UPPER(TRIM(na.grade)) = 'A-' THEN 3.7
             WHEN UPPER(TRIM(na.grade)) = 'B+' THEN 3.3
             WHEN UPPER(TRIM(na.grade)) = 'B'  THEN 3.0
             WHEN UPPER(TRIM(na.grade)) = 'C'  THEN 2.0
             WHEN UPPER(TRIM(na.grade)) = 'D'  THEN 1.0
             ELSE (na.nilai_akhir * 4.0 / 100.0)
           END) * mk.sks
         ) / NULLIF(SUM(mk.sks),0)
       , 2) AS ipk,
       COALESCE(SUM(mk.sks),0) AS total_sks
FROM nilai_akhir na
JOIN krs k ON na.id_krs = k.id_krs
JOIN mahasiswa m ON k.id_mahasiswa = m.id_mahasiswa
JOIN mata_kuliah mk ON k.kode_matkul = mk.kode_matkul
WHERE m.id_mahasiswa = :id_mahasiswa
GROUP BY m.id_mahasiswa
";
$stmt = $pdo->prepare($query);
$stmt->execute(['id_mahasiswa' => $id_mahasiswa]);
$data = $stmt->fetch();

$nama = $data['nama_mahasiswa'] ?? 'Mahasiswa Tidak Ditemukan';
$ipk_raw = $data['ipk'] ?? 0;
$ipk = number_format($ipk_raw, 2);
$total_sks = $data['total_sks'] ?? 0;

// 2) IP per semester (trennya)
$semQuery = "
SELECT mk.semester AS semester_label,
       ROUND(
         SUM(
           (CASE
             WHEN UPPER(TRIM(na.grade)) = 'A'  THEN 4.0
             WHEN UPPER(TRIM(na.grade)) = 'A-' THEN 3.7
             WHEN UPPER(TRIM(na.grade)) = 'B+' THEN 3.3
             WHEN UPPER(TRIM(na.grade)) = 'B'  THEN 3.0
             WHEN UPPER(TRIM(na.grade)) = 'C'  THEN 2.0
             WHEN UPPER(TRIM(na.grade)) = 'D'  THEN 1.0
             ELSE (na.nilai_akhir * 4.0 / 100.0)
           END) * mk.sks
         ) / NULLIF(SUM(mk.sks),0)
       ,2) AS ip_semester,
       COALESCE(SUM(mk.sks),0) AS sks_semester
FROM nilai_akhir na
JOIN krs k ON na.id_krs = k.id_krs
JOIN mata_kuliah mk ON k.kode_matkul = mk.kode_matkul
WHERE k.id_mahasiswa = :id_mahasiswa
GROUP BY mk.semester
ORDER BY mk.semester
";
$sstmt = $pdo->prepare($semQuery);
$sstmt->execute(['id_mahasiswa' => $id_mahasiswa]);
$semRows = $sstmt->fetchAll();

$sem_labels = [];
$sem_ip = [];
foreach ($semRows as $r) {
    $sem_labels[] = $r['semester_label'] ?? '0';
    $sem_ip[] = is_null($r['ip_semester']) ? 0.0 : (float)$r['ip_semester'];
}

// 3) Distribusi grade untuk grafik bar
$gradeQuery = "
SELECT UPPER(TRIM(na.grade)) AS grade_label, COUNT(*) AS cnt
FROM nilai_akhir na
JOIN krs k ON na.id_krs = k.id_krs
WHERE k.id_mahasiswa = :id_mahasiswa
GROUP BY UPPER(TRIM(na.grade))
";
$gstmt = $pdo->prepare($gradeQuery);
$gstmt->execute(['id_mahasiswa' => $id_mahasiswa]);
$gradeRows = $gstmt->fetchAll();

$expected = ['A','A-','B+','B','C','D','-'];
$grade_map = array_fill_keys($expected, 0);
foreach ($gradeRows as $gr) {
    $g = $gr['grade_label'] ?: '-';
    if (!in_array($g, $expected)) $g = '-';
    $grade_map[$g] = (int)$gr['cnt'];
}

$js_sem_labels = json_encode($sem_labels);
$js_sem_data   = json_encode($sem_ip);
$js_grade_labels = json_encode(array_keys($grade_map));
$js_grade_data   = json_encode(array_values($grade_map));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>TelesandiKu - Dashboard (Hanif)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="Sidebar.css">
  <style>
    body { background-color: #f6f8fb; font-family: 'Poppins', sans-serif; }
    .content { background-color: #f9fbfd; margin-left: 250px; padding: 2rem; }
    .card { border-radius: 14px; }
    .stat { border-radius: 14px; }
    .bg-cyan { background: linear-gradient(135deg, #00acc1, #26c6da); }
    .bg-purple { background: linear-gradient(135deg, #7b1fa2, #9c27b0); }
    .bg-blue { background: linear-gradient(135deg, #1976d2, #42a5f5); }
    .card h3 { margin: 0; }
    .user-info { font-size: 15px; }
    /* jika tidak ada sidebar, margin-left bisa diubah/dihapus */
  </style>
</head>
<body>
  <div class="d-flex">
    <!-- include sidebar jika ada -->
    <?php if (file_exists('sidebar.php')) include 'sidebar.php'; ?>

    <div class="content flex-grow-1">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Dashboard</h4>
        <div class="user-info">
          <span class="me-2 fw-semibold"><?= htmlspecialchars($nama) ?></span>
          <i class="bi bi-circle-fill text-success small"></i>
        </div>
      </div>

      <!-- Stats -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card stat shadow-sm border-0 bg-cyan text-white">
            <div class="card-body d-flex align-items-center">
              <i class="bi bi-journal-bookmark-fill fs-2 me-3"></i>
              <div>
                <p class="mb-1">Perolehan SKS</p>
                <h3 class="fw-bold"><?= htmlspecialchars($total_sks) ?></h3>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card stat shadow-sm border-0 bg-purple text-white">
            <div class="card-body d-flex align-items-center">
              <i class="bi bi-award-fill fs-2 me-3"></i>
              <div>
                <p class="mb-1">IPK</p>
                <h3 class="fw-bold"><?= htmlspecialchars($ipk) ?></h3>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card stat shadow-sm border-0 bg-blue text-white">
            <div class="card-body d-flex align-items-center">
              <i class="bi bi-clock-history fs-2 me-3"></i>
              <div>
                <p class="mb-1">Lama Studi</p>
                <h3 class="fw-bold">3 Semester</h3>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="row g-3">
        <div class="col-md-6">
          <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body">
              <h6 class="fw-semibold mb-3">Tren IP Per Semester</h6>
              <canvas id="ipChart"></canvas>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body">
              <h6 class="fw-semibold mb-3">Jumlah Perolehan Nilai</h6>
              <canvas id="gradeChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const semLabels = <?= $js_sem_labels ?>;
    const semData   = <?= $js_sem_data ?>;
    const gradeLabels = <?= $js_grade_labels ?>;
    const gradeData   = <?= $js_grade_data ?>;

    const ipChart = new Chart(document.getElementById('ipChart'), {
      type: 'line',
      data: {
        labels: semLabels.length ? semLabels : ['1','2','3'],
        datasets: [{
          label: 'IP Semester',
          data: semData.length ? semData : [4.0, 3.3, 3.7],
          borderColor: '#007bff',
          fill: false,
          tension: 0.3,
          pointRadius: 4
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: false, suggestedMin: 0, suggestedMax: 4.0 } }
      }
    });

    const gradeChart = new Chart(document.getElementById('gradeChart'), {
      type: 'bar',
      data: {
        labels: gradeLabels,
        datasets: [{
          data: gradeData,
          backgroundColor: '#6a1b9a',
          borderRadius: 6
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, precision: 0 } }
      }
    });
  </script>
</body>
</html>
