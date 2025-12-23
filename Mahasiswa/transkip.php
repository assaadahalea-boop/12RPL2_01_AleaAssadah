<?php
// transkrip.php — Versi Sinkron dengan Struktur Database universitas (22).sql

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
if (!isset($_SESSION['id_mahasiswa'])) {
    $_SESSION['id_mahasiswa'] = 201; // Andi Wijaya
}
$id_mahasiswa = (int) $_SESSION['id_mahasiswa'];

// 1️⃣ Ambil ringkasan per semester
$semQuery = "
SELECT
  mk.semester AS semester_label,
  COUNT(*) AS jumlah_matkul,
  COALESCE(SUM(mk.sks),0) AS sks_semester,
  COALESCE(SUM(
    (CASE
      WHEN UPPER(TRIM(na.grade)) = 'A'  THEN 4.0
      WHEN UPPER(TRIM(na.grade)) = 'A-' THEN 3.7
      WHEN UPPER(TRIM(na.grade)) = 'B+' THEN 3.3
      WHEN UPPER(TRIM(na.grade)) = 'B'  THEN 3.0
      WHEN UPPER(TRIM(na.grade)) = 'C'  THEN 2.0
      WHEN UPPER(TRIM(na.grade)) = 'D'  THEN 1.0
      ELSE (na.nilai_akhir * 4.0 / 100.0)
    END) * COALESCE(mk.sks,0)
  ),0) AS total_bobot_semester
FROM krs k
JOIN mata_kuliah mk ON mk.kode_matkul = k.kode_matkul
LEFT JOIN nilai_akhir na ON na.id_krs = k.id_krs
WHERE k.id_mahasiswa = :id_mahasiswa
GROUP BY mk.semester
ORDER BY mk.semester
";
$sstmt = $pdo->prepare($semQuery);
$sstmt->execute(['id_mahasiswa' => $id_mahasiswa]);
$semRows = $sstmt->fetchAll();

// 2️⃣ Hitung IP semester dan IP kumulatif
$rows = [];
foreach ($semRows as $r) {
    $sks = (int)$r['sks_semester'];
    $total_bobot = (float)$r['total_bobot_semester'];
    $ip_sem = ($sks > 0) ? round($total_bobot / $sks, 2) : 0.00;
    $rows[] = [
        'semester' => $r['semester_label'],
        'jumlah_matkul' => (int)$r['jumlah_matkul'],
        'sks' => $sks,
        'total_bobot' => $total_bobot,
        'ip_semester' => $ip_sem
    ];
}

$maxSemester = !empty($rows) ? max(array_column($rows, 'semester')) : null;
$cum_weighted = 0.0;
$cum_sks = 0;

foreach ($rows as &$r) {
    $cum_weighted += $r['total_bobot'];
    $cum_sks += $r['sks'];
    $r['ip_kumulatif'] = ($cum_sks > 0) ? round($cum_weighted / $cum_sks, 2) : 0.00;
    $r['status'] = ($r['semester'] == $maxSemester) ? 'Aktif' : 'Lulus';
}
unset($r);

// 3️⃣ Query detail per semester (Menggunakan jadwal_kelas untuk Dosen)
$detailSql = "
SELECT
  mk.kode_matkul,
  mk.nama AS nama_matkul,
  mk.sks,
  d.nama AS dosen_pengampu,
  na.nilai_tugas,
  na.nilai_uts,
  na.nilai_uas,
  na.nilai_akhir,
  COALESCE(NULLIF(na.grade,''), '-') AS grade
FROM krs k
JOIN mata_kuliah mk ON mk.kode_matkul = k.kode_matkul
LEFT JOIN jadwal_kelas jk ON jk.kode_matkul = mk.kode_matkul AND jk.id_kelas = k.id_kelas
LEFT JOIN dosen d ON d.nik = jk.id_dosen
LEFT JOIN nilai_akhir na ON na.id_krs = k.id_krs
WHERE k.id_mahasiswa = :id_mahasiswa AND mk.semester = :semester
ORDER BY mk.nama
";
$detailStmt = $pdo->prepare($detailSql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Transkrip Nilai - TelesandiKu</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background:#f6f8fb; font-family:'Poppins',sans-serif; }
    .main-content { flex:1; padding:2rem; margin-left:260px; }
    .transkrip-card { background:white; border-radius:14px; box-shadow:0 4px 16px rgba(0,0,0,0.08); padding:1.5rem; }
    table.transkrip { width:100%; border-collapse:collapse; font-size:15px; }
    table.transkrip thead { background:#f0f6ff; color:#0055cc; }
    table.transkrip th, table.transkrip td { padding:12px 16px; border-bottom:1px solid #eee; }
    .badge-lulus { background:#dcfce7; color:#15803d; border-radius:12px; padding:6px 12px; font-weight:600; font-size:12px; }
    .badge-aktif { background:#e0f2fe; color:#0369a1; border-radius:12px; padding:6px 12px; font-weight:600; font-size:12px; }
    .detail-table { margin-top:10px; width:100%; border-collapse:collapse; font-size:13px; }
    .detail-table th { background:#f8f9fa; color:#333; padding:10px; border: 1px solid #dee2e6; }
    .detail-table td { padding:10px; border: 1px solid #dee2e6; }
    @media (max-width: 992px) { .main-content { margin-left: 0; } }
  </style>
</head>
<body>
  <div class="d-flex">
    <?php if (file_exists('sidebar.php')) include 'sidebar.php'; ?>

    <div class="main-content">
      <header class="mb-4">
          <h3 class="fw-bold">Transkrip Nilai Akademik</h3>
          <p class="text-muted">Pantau perkembangan indeks prestasi Anda setiap semester.</p>
      </header>

      <div class="transkrip-card">
        <?php if (empty($rows)): ?>
          <div class="text-center py-5">
              <i class="bi bi-file-earmark-x display-1 text-muted opacity-25"></i>
              <p class="text-muted mt-3">Belum ada data nilai akademik yang tercatat.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="transkrip">
              <thead>
                <tr>
                  <th>Semester</th>
                  <th>Jumlah MK</th>
                  <th>Total SKS</th>
                  <th>IP Semester</th>
                  <th>IP Kumulatif</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): 
                  $collapseId = "detail" . $r['semester'];
                ?>
                  <tr>
                    <td class="fw-bold">Semester <?= $r['semester'] ?></td>
                    <td><?= $r['jumlah_matkul'] ?></td>
                    <td><?= $r['sks'] ?> SKS</td>
                    <td><span class="text-primary fw-bold"><?= number_format($r['ip_semester'],2) ?></span></td>
                    <td><span class="text-success fw-bold"><?= number_format($r['ip_kumulatif'],2) ?></span></td>
                    <td>
                      <?php if ($r['status'] === 'Aktif'): ?>
                        <span class="badge-aktif">Aktif</span>
                      <?php else: ?>
                        <span class="badge-lulus">Selesai</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="btn btn-light btn-sm border" 
                              type="button"
                              data-bs-toggle="collapse"
                              data-bs-target="#<?= $collapseId ?>">
                        <i class="bi bi-chevron-down me-1"></i>Detail
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td colspan="7" class="p-0 border-0">
                      <div class="collapse" id="<?= $collapseId ?>">
                        <div class="p-4 bg-light border-start border-end">
                          <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2"></i>Rincian Mata Kuliah Semester <?= $r['semester'] ?></h6>
                          <?php
                            $detailStmt->execute(['id_mahasiswa' => $id_mahasiswa, 'semester' => $r['semester']]);
                            $details = $detailStmt->fetchAll();
                          ?>
                          <table class="detail-table bg-white">
                            <thead>
                              <tr>
                                <th>Kode</th>
                                <th>Mata Kuliah</th>
                                <th>SKS</th>
                                <th>Dosen</th>
                                <th>Tugas</th>
                                <th>UTS</th>
                                <th>UAS</th>
                                <th>Akhir</th>
                                <th>Grade</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($details as $d): ?>
                                <tr>
                                  <td class="text-muted"><?= $d['kode_matkul'] ?></td>
                                  <td class="fw-bold"><?= $d['nama_matkul'] ?></td>
                                  <td><?= $d['sks'] ?></td>
                                  <td><small><?= $d['dosen_pengampu'] ?? '-' ?></small></td>
                                  <td><?= $d['nilai_tugas'] ?? '0' ?></td>
                                  <td><?= $d['nilai_uts'] ?? '0' ?></td>
                                  <td><?= $d['nilai_uas'] ?? '0' ?></td>
                                  <td class="fw-bold"><?= $d['nilai_akhir'] ?? '0' ?></td>
                                  <td><span class="badge bg-dark"><?= $d['grade'] ?></span></td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>