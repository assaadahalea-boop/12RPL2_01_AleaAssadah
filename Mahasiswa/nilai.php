<?php
// nilai_semester3.php
// Menampilkan nilai mahasiswa untuk semester 3

// Konfigurasi DB
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
// Gunakan ID Mahasiswa 201 (Andi Wijaya) sesuai data di universitas (22).sql
if (!isset($_SESSION['id_mahasiswa'])) {
    $_SESSION['id_mahasiswa'] = 201; 
}
$id_mahasiswa = (int) $_SESSION['id_mahasiswa'];

/* QUERY PENYESUAIAN:
  Di DB Anda, kelas tidak punya kode_matkul. 
  Kita harus join: krs -> mata_kuliah -> jadwal_kelas -> dosen
*/
$sql = "
SELECT
  mk.kode_matkul,
  COALESCE(mk.nama, '-') AS mata_kuliah,
  COALESCE(d.nama, '-') AS dosen_pengampu,
  na.nilai_akhir,
  COALESCE(NULLIF(na.grade, ''), '-') AS grade
FROM krs k
JOIN mata_kuliah mk ON mk.kode_matkul = k.kode_matkul
LEFT JOIN jadwal_kelas jk ON jk.kode_matkul = mk.kode_matkul AND jk.id_kelas = k.id_kelas
LEFT JOIN dosen d ON d.nik = jk.id_dosen
LEFT JOIN nilai_akhir na ON na.id_krs = k.id_krs
WHERE k.id_mahasiswa = :id_mahasiswa
  AND mk.semester = 3
ORDER BY mk.nama
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id_mahasiswa' => $id_mahasiswa]);
$rows = $stmt->fetchAll();

// Helper status
function keteranganStatus($grade, $nilai_akhir) {
    if ($grade && $grade !== '-' && $nilai_akhir !== null && floatval($nilai_akhir) > 0) {
        return 'Lulus';
    }
    if ($nilai_akhir !== null && floatval($nilai_akhir) > 0) return 'Lulus';
    return 'Belum';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TelesandiKu - Nilai Semester 3</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background:#f4f7fe; font-family:'Poppins', sans-serif; }
    .main-content { flex:1; padding:30px; margin-left:260px; }
    .nilai-container { background:#fff; border-radius:15px; padding:20px; box-shadow:0 10px 30px rgba(0,0,0,0.05); }
    .table-nilai { width:100%; border-collapse:collapse; }
    .table-nilai th { background:#f8f9fa; padding:15px; text-align:left; border-bottom:2px solid #eee; }
    .table-nilai td { padding:15px; border-bottom:1px solid #eee; }
    .badge-lulus { background:#dcfce7; color:#15803d; padding:5px 12px; border-radius:20px; font-weight:600; font-size:12px; }
    .badge-belum { background:#fee2e2; color:#b91c1c; padding:5px 12px; border-radius:20px; font-weight:600; font-size:12px; }
    .btn-semester { background:#2c3e50; color:#fff; border:none; padding:8px 20px; border-radius:10px; }
    @media (max-width: 992px) { .main-content { margin-left: 0; } }
  </style>
</head>
<body>
  <div class="d-flex">
    <?php if (file_exists('sidebar.php')) include 'sidebar.php'; ?>

    <div class="main-content">
      <header class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h3 class="fw-bold">Nilai - Semester 3</h3>
          <small class="text-muted">Tahun Akademik 2025</small>
        </div>
        <button class="btn-semester"><i class="bi bi-calendar-week me-2"></i>Semester 3</button>
      </header>

      <div class="nilai-container">
        <?php if (count($rows) === 0): ?>
          <div class="text-center py-5">
            <i class="bi bi-folder2-open display-1 text-muted opacity-25"></i>
            <p class="text-muted mt-3">Belum ada data nilai semester 3 untuk mahasiswa ini.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-nilai">
              <thead>
                <tr>
                  <th style="width:6%;">No</th>
                  <th style="width:36%;">Mata Kuliah</th>
                  <th style="width:34%;">Dosen Pengampu</th>
                  <th style="width:12%;">Grade</th>
                  <th style="width:12%;">Keterangan</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; foreach ($rows as $r): 
                  $mata = htmlspecialchars($r['mata_kuliah']);
                  $dosen = htmlspecialchars($r['dosen_pengampu']);
                  $grade = htmlspecialchars($r['grade'] ?? '-');
                  $nilai_akhir = $r['nilai_akhir'] !== null ? (float)$r['nilai_akhir'] : null;
                  $ket = keteranganStatus($grade, $nilai_akhir);
                ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= $mata ?></strong><br><small class="text-muted"><?= $r['kode_matkul'] ?></small></td>
                    <td><?= $dosen ?></td>
                    <td class="fw-bold"><?= $grade ?></td>
                    <td>
                      <?php if ($ket === 'Lulus'): ?>
                        <span class="badge-lulus">Lulus</span>
                      <?php else: ?>
                        <span class="badge-belum">Belum</span>
                      <?php endif; ?>
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
</body>
</html>