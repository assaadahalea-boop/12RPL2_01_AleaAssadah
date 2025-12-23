<?php
// dataprodi.php — CRUD Prodi
define('DB_HOST','localhost');
define('DB_NAME','universitas');
define('DB_USER','root');
define('DB_PASS','');

try {
  $pdo = new PDO(
    "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
  );
} catch (Exception $e) {
  die("Koneksi gagal: " . $e->getMessage());
}

// --- CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add' && !empty($_POST['nama'])) {
    $stmt = $pdo->prepare("INSERT INTO prodi (nama, id_fakultas, jenjang, akreditasi, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
      $_POST['nama'],
      $_POST['id_fakultas'],
      $_POST['jenjang'],
      $_POST['akreditasi'],
      $_POST['status']
    ]);
  }

  if ($action === 'edit' && isset($_POST['id_prodi'])) {
    $stmt = $pdo->prepare("UPDATE prodi SET nama=?, id_fakultas=?, jenjang=?, akreditasi=?, status=? WHERE id_prodi=?");
    $stmt->execute([
      $_POST['nama'],
      $_POST['id_fakultas'],
      $_POST['jenjang'],
      $_POST['akreditasi'],
      $_POST['status'],
      $_POST['id_prodi']
    ]);
  }

  if ($action === 'delete' && isset($_POST['id_prodi'])) {
    $pdo->prepare("DELETE FROM prodi WHERE id_prodi=?")->execute([$_POST['id_prodi']]);
  }

  header("Location: dataprodi.php");
  exit;
}

// Ambil data
$prodi = $pdo->query("SELECT p.*, f.nama AS nama_fakultas FROM prodi p 
                      JOIN fakultas f ON p.id_fakultas = f.id_fakultas
                      ORDER BY p.id_prodi ASC")->fetchAll();
$fakultas = $pdo->query("SELECT * FROM fakultas")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Prodi - Admin Panel</title>
  <link rel="stylesheet" href="Style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  
  <style>

    h4 span{color:#1976d2;}
    .table th{color:white;}
    .btn-add{background:linear-gradient(135deg,#1565c0,#1e88e5);color:white;}
    .btn-add:hover{background:linear-gradient(135deg,#0d47a1,#1565c0);}
   
    @media (max-width:992px){.sidebar{position:relative;width:100%;}.content{margin-left:0;}}
  </style>
</head>
<body>
  <div class="sidebar">
    <?php include 'sidebar.php' ?>
  </div>

  <div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4><i class="bi bi-diagram-3 me-2"></i>Data Program Studi</h4>
      <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-circle me-1"></i> Tambah Prodi
      </button>
    </div>

    <div class="table-responsive">
      <table class="table table-striped align-middle text-center">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama Prodi</th>
            <th>Fakultas</th>
            <th>Jenjang</th>
            <th>Akreditasi</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($prodi as $p): ?>
            <tr>
              <td><?= $p['id_prodi'] ?></td>
              <td><?= htmlspecialchars($p['nama']) ?></td>
              <td><?= htmlspecialchars($p['nama_fakultas']) ?></td>
              <td><?= htmlspecialchars($p['jenjang']) ?></td>
              <td><?= htmlspecialchars($p['akreditasi']) ?></td>
              <td><span class="badge bg-<?= $p['status']=='Aktif'?'success':'secondary' ?>"><?= $p['status'] ?></span></td>
              <td>
                <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $p['id_prodi'] ?>"><i class="bi bi-pencil"></i></button>
                <form method="post" style="display:inline;" onsubmit="return confirm('Hapus prodi ini?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id_prodi" value="<?= $p['id_prodi'] ?>">
                  <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>

            <!-- Modal Edit -->
            <div class="modal fade" id="modalEdit<?= $p['id_prodi'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title">Edit Prodi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <form method="post">
                      <input type="hidden" name="action" value="edit">
                      <input type="hidden" name="id_prodi" value="<?= $p['id_prodi'] ?>">
                      <div class="mb-3"><label>Nama Prodi</label><input name="nama" type="text" class="form-control" value="<?= htmlspecialchars($p['nama']) ?>" required></div>
                      <div class="mb-3"><label>Fakultas</label>
                        <select name="id_fakultas" class="form-select">
                          <?php foreach($fakultas as $f): ?>
                            <option value="<?= $f['id_fakultas'] ?>" <?= $p['id_fakultas']==$f['id_fakultas']?'selected':'' ?>>
                              <?= htmlspecialchars($f['nama']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mb-3"><label>Jenjang</label><input name="jenjang" type="text" class="form-control" value="<?= htmlspecialchars($p['jenjang']) ?>"></div>
                      <div class="mb-3"><label>Akreditasi</label><input name="akreditasi" type="text" class="form-control" value="<?= htmlspecialchars($p['akreditasi']) ?>"></div>
                      <div class="mb-3"><label>Status</label>
                        <select name="status" class="form-select">
                          <option <?= $p['status']=='Aktif'?'selected':'' ?>>Aktif</option>
                          <option <?= $p['status']=='Tidak Aktif'?'selected':'' ?>>Tidak Aktif</option>
                        </select>
                      </div>
                      <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Tambah Prodi</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="mb-3"><label>Nama Prodi</label><input name="nama" type="text" class="form-control" required></div>
            <div class="mb-3"><label>Fakultas</label>
              <select name="id_fakultas" class="form-select" required>
                <option value="">-- Pilih Fakultas --</option>
                <?php foreach($fakultas as $f): ?>
                  <option value="<?= $f['id_fakultas'] ?>"><?= htmlspecialchars($f['nama']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3"><label>Jenjang</label><input name="jenjang" type="text" class="form-control"></div>
            <div class="mb-3"><label>Akreditasi</label><input name="akreditasi" type="text" class="form-control"></div>
            <div class="mb-3"><label>Status</label>
              <select name="status" class="form-select">
                <option value="Aktif">Aktif</option>
                <option value="Tidak Aktif">Tidak Aktif</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Simpan</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
