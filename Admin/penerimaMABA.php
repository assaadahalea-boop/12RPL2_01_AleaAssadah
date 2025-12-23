<?php
// pmb_admin.php
// Ganti konfigurasi DB sesuai lingkungan Anda:
$dbHost = '127.0.0.1';
$dbName = 'universitas';   // pastikan sudah import universitas (1).sql
$dbUser = 'root';
$dbPass = ''; // isi password jika ada

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("Koneksi DB gagal: " . htmlspecialchars($e->getMessage()));
}

// Helper: ambil data berdasarkan jalur, opsi filter jurusan & search
function fetchPMB(PDO $pdo, $jalur, $jurusan = '', $search = '') {
    $sql = "SELECT id_pmb, nama_peserta, prodi, skor, status
            FROM pmb
            WHERE jalur = :jalur";
    $params = [':jalur' => $jalur];

    if ($jurusan !== '') {
        $sql .= " AND prodi = :prodi";
        $params[':prodi'] = $jurusan;
    }
    if ($search !== '') {
        $sql .= " AND nama_peserta LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }
    $sql .= " ORDER BY nama_peserta ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Helper: ambil opsi prodi unik untuk sebuah jalur (untuk select filter)
function fetchProdiOptions(PDO $pdo, $jalur) {
    $stmt = $pdo->prepare("SELECT DISTINCT prodi FROM pmb WHERE jalur = :jalur ORDER BY prodi");
    $stmt->execute([':jalur' => $jalur]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Baca parameter GET untuk ketiga tab (agar UI tetap menggunakan same layout)
$filters = [
    'SNBP'   => ['jurusan' => $_GET['jurusanSNBP'] ?? '', 'search' => $_GET['searchSNBP'] ?? ''],
    'SNBT'   => ['jurusan' => $_GET['jurusanSNBT'] ?? '', 'search' => $_GET['searchSNBT'] ?? ''],
    'Mandiri'=> ['jurusan' => $_GET['jurusanMandiri'] ?? '', 'search' => $_GET['searchMandiri'] ?? ''],
];

// Ambil data untuk tiap jalur
$dataSNBP = fetchPMB($pdo, 'SNBP', $filters['SNBP']['jurusan'], $filters['SNBP']['search']);
$dataSNBT = fetchPMB($pdo, 'SNBT', $filters['SNBT']['jurusan'], $filters['SNBT']['search']);
$dataMandiri = fetchPMB($pdo, 'Mandiri', $filters['Mandiri']['jurusan'], $filters['Mandiri']['search']);

// Ambil opsi prodi untuk masing-masing jalur (dipakai di select)
$optsSNBP = fetchProdiOptions($pdo, 'SNBP');
$optsSNBT = fetchProdiOptions($pdo, 'SNBT');
$optsMandiri = fetchProdiOptions($pdo, 'Mandiri');

function computeSummary($rows) {
    $totalDiterima = 0;
    $sumSkor = 0;
    $countSkor = 0;
    foreach ($rows as $r) {
        if ($r['status'] === 'Diterima') $totalDiterima++;
        if (is_numeric($r['skor'])) {
            $sumSkor += (int)$r['skor'];
            $countSkor++;
        }
    }
    $avg = $countSkor ? round($sumSkor / $countSkor, 2) : '-';
    return ['count' => count($rows), 'totalDiterima' => $totalDiterima, 'avg' => $avg];
}

$sumSNBP = computeSummary($dataSNBP);
$sumSNBT = computeSummary($dataSNBT);
$sumMandiri = computeSummary($dataMandiri);

// Untuk menjaga tab yang aktif ketika submit, baca param 'activeTab'
$activeTab = $_GET['activeTab'] ?? 'snbp';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Administrasi Akademik - PMB</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="Style.css">
  <style>
    /* <-- SALIN CSS PERSIS SEPERTI ASAL (tidak diubah) --> */
    body {
      background-color: #f4f7fc;
      font-family: 'Poppins', "Helvetica Neue", Arial, sans-serif;
      color: #fff;
      margin: 0;
    }
    /* restore utama untuk sidebar (posisi, lebar, warna background, shadow) */
    
    .main-container { margin-left: 270px; padding: 36px 48px; min-height: 100vh; }
    .pmb-tabs { display:flex; justify-content:center; gap:12px; margin-bottom: 28px; }
    .nav-tabs .nav-link { border: none; padding: 10px 22px; border-radius: 999px; color: #1976d2; background: transparent; transition: all .18s ease; font-weight: 600; }
    .nav-tabs .nav-link.active { background: linear-gradient(90deg,#1976d2,#0d47a1); color:#fff !important; box-shadow: 0 6px 18px rgba(13,71,161,0.16); }
    .card-modern { border: none; border-radius: 14px; box-shadow: 0 10px 30px rgba(16,24,40,0.06); background: #fff; padding: 22px; }
    .controls-row { display:flex; gap:12px; flex-wrap: wrap; align-items:center; margin-bottom: 12px; }
    .controls-row .form-select { min-width: 240px; flex: 1 1 360px; }
    .controls-row .form-control { flex: 0 0 360px; }
    .summary-line { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom: 12px; }
    .summary-line .total-label { font-weight:700; color:#243b66; }
    .table-responsive { border-radius: 10px; overflow: hidden; }
    table.table { margin-bottom: 0; border-collapse: collapse; width: 100%; table-layout: fixed; }
    table.table thead th { border-bottom: none; background: #fbfdff; color: #ffffff; font-weight:700; font-size: 0.95rem; padding: 12px 12px; text-align: left; } /* <--- DIUBAH DI SINI */
    table.table tbody td { vertical-align: middle; padding: 12px 12px; border-top: 1px solid #eef2f7; font-size: 0.95rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    table.table tbody tr:hover { background: rgba(25,118,210,0.03); }
    th.col-nama { width: 40%; min-width: 180px; }
    th.col-prodi { width: 30%; min-width: 160px; }
    th.col-skor { width: 10%; min-width: 110px;  }
    th.col-status { width: 15%; min-width: 110px;  }
    td.col-skor {
      color: #0b5ed7;
      font-weight: 700;
      font-size: 0.98rem;
    }
    td.col-status { padding: 0; vertical-align: middle; }
    td.col-status .status-wrap {
      display: flex;
      height: 100%;
      padding: 12px;
      box-sizing: border-box;
    }
    td.col-status .status-wrap .badge { display: inline-flex; padding: .34rem .64rem; font-size: .85rem; border-radius: 999px; line-height: 1; box-shadow: none; }
    .badge-compact { padding: 0.4em 0.7em; font-size: .82rem; border-radius: 999px; font-weight:600; }
    .muted-small { color:#6b7280; font-size:0.92rem; }
    table.table thead th {
      background: #fbfdff;
      border-bottom: none;
      color: #ffffff; /* <--- DIUBAH DI SINI */
      font-weight: 700;
      padding: 14px 12px;
    }
    table.table tbody tr:hover {
      background: rgba(9, 115, 199, 0.03);
    }
    td.col-skor a, td.col-skor a:visited {
      color: inherit;
      text-decoration: none;
    }
    @media (max-width: 992px) {
      .main-container { margin-left: 0; padding: 20px; }
      .controls-row { flex-direction: column; align-items: stretch; }
      .controls-row .form-control, .controls-row .form-select { width:100%; flex: unset; }
      table.table thead th, table.table tbody td { font-size: 0.90rem; }
      th.col-nama, th.col-prodi, th.col-skor, th.col-status 
      {
          text-align: center;
          vertical-align: middle;
          padding: 14px 12px;
          font-size: 0.95rem;
        }
      td { white-space: normal; }
    }
  </style>
</head>
<body>

    <?php include 'sidebar.php';?>

    <div class="main-container">

    <ul class="nav nav-tabs pmb-tabs" id="pmbTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'snbp' ? 'active' : ''; ?>" id="snbp-tab" data-bs-toggle="tab" data-bs-target="#snbp" type="button" role="tab">SNBP</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'snbt' ? 'active' : ''; ?>" id="snbt-tab" data-bs-toggle="tab" data-bs-target="#snbt" type="button" role="tab">SNBT</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'mandiri' ? 'active' : ''; ?>" id="mandiri-tab" data-bs-toggle="tab" data-bs-target="#mandiri" type="button" role="tab">Mandiri</button>
      </li>
    </ul>

    <div class="tab-content">

            <div class="tab-pane fade <?php echo $activeTab === 'snbp' ? 'show active' : ''; ?>" id="snbp" role="tabpanel">
        <div class="card-modern mb-4">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <h5 class="mb-0">Daftar Peserta Jalur SNBP</h5>
            <div>
              <a class="btn btn-outline-secondary btn-sm me-2" href="?export=SNBP"><i class="bi bi-download"></i> Export</a>
            </div>
          </div>

                    <form id="formSNBP" method="get" class="controls-row" onsubmit="document.getElementById('activeTab').value='snbp'">
            <input type="hidden" name="activeTab" id="activeTab" value="<?php echo htmlspecialchars($activeTab); ?>">
            <select id="jurusanSNBP" name="jurusanSNBP" class="form-select" onchange="document.getElementById('activeTab').value='snbp'; this.form.submit();">
              <option value="">-- Semua Jurusan --</option>
              <?php foreach ($optsSNBP as $p): ?>
                <option value="<?php echo htmlspecialchars($p); ?>" <?php if ($filters['SNBP']['jurusan'] === $p) echo 'selected'; ?>><?php echo htmlspecialchars($p); ?></option>
              <?php endforeach; ?>
            </select>

            <input id="searchSNBP" name="searchSNBP" type="search" class="form-control" placeholder="Cari nama peserta..." value="<?php echo htmlspecialchars($filters['SNBP']['search']); ?>" oninput="document.getElementById('activeTab').value='snbp';">
            <div class="muted-small">Tip: gunakan pencarian & filter jurusan</div>
            <button type="submit" class="d-none">submit</button>
          </form>

          <div class="summary-line">
            <div class="muted-small">Menampilkan <span id="showCountSNBP"><?php echo $sumSNBP['count']; ?></span> hasil</div>
            <div class="text-end">
              <div class="total-label">Total Diterima: <span id="totalSNBP" class="text-primary"><?php echo $sumSNBP['totalDiterima']; ?></span></div>
              <div class="muted-small">Rata-rata Skor: <span id="avgSNBP"><?php echo $sumSNBP['avg']; ?></span></div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th class="col-nama">Nama Peserta</th>
                  <th class="col-prodi">Program Studi</th>
                  <th class="col-skor">Skor</th>
                  <th class="col-status">Status</th>
                </tr>
              </thead>
              <tbody id="tableSNBP">
                <?php foreach ($dataSNBP as $row): ?>
                <tr>
                  <td class="col-nama"><?php echo htmlspecialchars($row['nama_peserta']); ?></td>
                  <td class="col-prodi"><?php echo htmlspecialchars($row['prodi']); ?></td>
                  <td class="col-skor"><?php echo is_null($row['skor']) ? '-' : htmlspecialchars($row['skor']); ?></td>
                  <td class="col-status">
                    <div class="status-wrap">
                      <?php if ($row['status'] === 'Diterima'): ?>
                        <span class="badge bg-success badge-compact">Diterima</span>
                      <?php else: ?>
                        <span class="badge bg-danger badge-compact">Tidak Diterima</span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($dataSNBP) === 0): ?>
                <tr><td colspan="4" class="text-center muted-small">Tidak ada data</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

            <div class="tab-pane fade <?php echo $activeTab === 'snbt' ? 'show active' : ''; ?>" id="snbt" role="tabpanel">
        <div class="card-modern mb-4">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <h5 class="mb-0">Daftar Peserta Jalur SNBT</h5>
            <div>
              <a class="btn btn-outline-secondary btn-sm me-2" href="?export=SNBT"><i class="bi bi-download"></i> Export</a>
            </div>
          </div>

          <form id="formSNBT" method="get" class="controls-row" onsubmit="document.getElementById('activeTab').value='snbt'">
            <input type="hidden" name="activeTab" value="snbt">
            <select id="jurusanSNBT" name="jurusanSNBT" class="form-select" onchange="this.form.submit();">
              <option value="">-- Semua Jurusan --</option>
              <?php foreach ($optsSNBT as $p): ?>
                <option value="<?php echo htmlspecialchars($p); ?>" <?php if ($filters['SNBT']['jurusan'] === $p) echo 'selected'; ?>><?php echo htmlspecialchars($p); ?></option>
              <?php endforeach; ?>
            </select>

            <input id="searchSNBT" name="searchSNBT" type="search" class="form-control" placeholder="Cari nama peserta..." value="<?php echo htmlspecialchars($filters['SNBT']['search']); ?>" oninput="document.getElementsByName('activeTab')[0].value='snbt';">
            <div class="muted-small">Tip: klik export untuk mengunduh (fitur contoh)</div>
            <button type="submit" class="d-none">submit</button>
          </form>

          <div class="summary-line">
            <div class="muted-small">Menampilkan <span id="showCountSNBT"><?php echo $sumSNBT['count']; ?></span> hasil</div>
            <div class="text-end">
              <div class="total-label">Total Diterima: <span id="totalSNBT" class="text-primary"><?php echo $sumSNBT['totalDiterima']; ?></span></div>
              <div class="muted-small">Rata-rata Skor: <span id="avgSNBT"><?php echo $sumSNBT['avg']; ?></span></div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th class="col-nama">Nama Peserta</th>
                  <th class="col-prodi">Program Studi</th>
                  <th class="col-skor">Skor</th>
                  <th class="col-status">Status</th>
                </tr>
              </thead>
              <tbody id="tableSNBT">
                <?php foreach ($dataSNBT as $row): ?>
                <tr>
                  <td class="col-nama"><?php echo htmlspecialchars($row['nama_peserta']); ?></td>
                  <td class="col-prodi"><?php echo htmlspecialchars($row['prodi']); ?></td>
                  <td class="col-skor"><?php echo is_null($row['skor']) ? '-' : htmlspecialchars($row['skor']); ?></td>
                  <td class="col-status">
                    <div class="status-wrap">
                      <?php if ($row['status'] === 'Diterima'): ?>
                        <span class="badge bg-success badge-compact">Diterima</span>
                      <?php else: ?>
                        <span class="badge bg-danger badge-compact">Tidak Diterima</span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($dataSNBT) === 0): ?>
                <tr><td colspan="4" class="text-center muted-small">Tidak ada data</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

            <div class="tab-pane fade <?php echo $activeTab === 'mandiri' ? 'show active' : ''; ?>" id="mandiri" role="tabpanel">
        <div class="card-modern mb-4">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <h5 class="mb-0">Daftar Peserta Jalur Mandiri</h5>
            <div>
              <a class="btn btn-outline-secondary btn-sm me-2" href="?export=Mandiri"><i class="bi bi-download"></i> Export</a>
            </div>
          </div>

          <form id="formMandiri" method="get" class="controls-row" onsubmit="document.getElementById('activeTab').value='mandiri'">
            <input type="hidden" name="activeTab" value="mandiri">
            <select id="jurusanMandiri" name="jurusanMandiri" class="form-select" onchange="this.form.submit();">
              <option value="">-- Semua Jurusan --</option>
              <?php foreach ($optsMandiri as $p): ?>
                <option value="<?php echo htmlspecialchars($p); ?>" <?php if ($filters['Mandiri']['jurusan'] === $p) echo 'selected'; ?>><?php echo htmlspecialchars($p); ?></option>
              <?php endforeach; ?>
            </select>

            <input id="searchMandiri" name="searchMandiri" type="search" class="form-control" placeholder="Cari nama peserta..." value="<?php echo htmlspecialchars($filters['Mandiri']['search']); ?>" oninput="document.getElementsByName('activeTab')[0].value='mandiri';">
            <div class="muted-small">Hasil disaring sesuai jurusan yang dipilih</div>
            <button type="submit" class="d-none">submit</button>
          </form>

          <div class="summary-line">
            <div class="muted-small">Menampilkan <span id="showCountMandiri"><?php echo $sumMandiri['count']; ?></span> hasil</div>
            <div class="text-end">
              <div class="total-label">Total Diterima: <span id="totalMandiri" class="text-primary"><?php echo $sumMandiri['totalDiterima']; ?></span></div>
              <div class="muted-small">Rata-rata Skor: <span id="avgMandiri"><?php echo $sumMandiri['avg']; ?></span></div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th class="col-nama">Nama Peserta</th>
                  <th class="col-prodi">Program Studi</th>
                  <th class="col-skor">Skor</th>
                  <th class="col-status">Status</th>
                </tr>
              </thead>
              <tbody id="tableMandiri">
                <?php foreach ($dataMandiri as $row): ?>
                <tr>
                  <td class="col-nama"><?php echo htmlspecialchars($row['nama_peserta']); ?></td>
                  <td class="col-prodi"><?php echo htmlspecialchars($row['prodi']); ?></td>
                  <td class="col-skor"><?php echo is_null($row['skor']) ? '-' : htmlspecialchars($row['skor']); ?></td>
                  <td class="col-status">
                    <div class="status-wrap">
                      <?php if ($row['status'] === 'Diterima'): ?>
                        <span class="badge bg-success badge-compact">Diterima</span>
                      <?php else: ?>
                        <span class="badge bg-danger badge-compact">Tidak Diterima</span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($dataMandiri) === 0): ?>
                <tr><td colspan="4" class="text-center muted-small">Tidak ada data</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>   </div>   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Supaya tab tetap aktif sesuai parameter GET ketika halaman dimuat
    (function(){
      const active = "<?php echo $activeTab; ?>";
      if (active) {
        const triggerEl = document.querySelector(#${active}-tab);
        if (triggerEl) {
          const tab = new bootstrap.Tab(triggerEl);
          tab.show();
        }
      }
    })();
  </script>
</body>
</html>