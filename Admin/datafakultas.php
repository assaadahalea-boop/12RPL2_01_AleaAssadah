<?php
// datafakultas.php — CRUD Fakultas
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

// ---- Aksi CRUD ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add' && !empty($_POST['nama'])) {
    $stmt = $pdo->prepare("INSERT INTO fakultas (nama, email, no_telp, alamat, status, tanggal_berdiri, deskripsi)
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
      $_POST['nama'],
      $_POST['email'] ?? null,
      $_POST['no_telp'] ?? null,
      $_POST['alamat'] ?? null,
      $_POST['status'] ?? 'Aktif',
      $_POST['tanggal_berdiri'] ?? null,
      $_POST['deskripsi'] ?? null
    ]);
  }

  if ($action === 'edit' && isset($_POST['id_fakultas'])) {
    $stmt = $pdo->prepare("UPDATE fakultas SET nama=?, email=?, no_telp=?, alamat=?, status=?, tanggal_berdiri=?, deskripsi=? WHERE id_fakultas=?");
    $stmt->execute([
      $_POST['nama'],
      $_POST['email'] ?? null,
      $_POST['no_telp'] ?? null,
      $_POST['alamat'] ?? null,
      $_POST['status'] ?? 'Aktif',
      $_POST['tanggal_berdiri'] ?? null,
      $_POST['deskripsi'] ?? null,
      $_POST['id_fakultas']
    ]);
  }

  if ($action === 'delete' && isset($_POST['id_fakultas'])) {
    $pdo->prepare("DELETE FROM fakultas WHERE id_fakultas=?")->execute([$_POST['id_fakultas']]);
  }

  header("Location: datafakultas.php");
  exit;
}

// Ambil data fakultas
$fakultas = $pdo->query("SELECT * FROM fakultas ORDER BY id_fakultas ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Fakultas - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="Style.css">
  <style>
   
    h4 span{color:#1976d2;}
    .table th{ color:white;}
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
      <h4><i class="bi bi-bank me-2"></i>Data Fakultas</h4>
      <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-circle me-1"></i> Tambah Fakultas
      </button>
    </div>

    <div class="table-responsive">
      <table class="table table-striped align-middle text-center">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama Fakultas</th>
            <th>Email</th>
            <th>No Telp</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($fakultas as $f): ?>
            <tr>
              <td><?= htmlspecialchars($f['id_fakultas']) ?></td>
              <td><?= htmlspecialchars($f['nama']) ?></td>
              <td><?= htmlspecialchars($f['email'] ?? '-') ?></td>
              <td><?= htmlspecialchars($f['no_telp'] ?? '-') ?></td>
              <td><span class="badge bg-<?= $f['status'] == 'Aktif' ? 'success' : 'secondary' ?>"><?= $f['status'] ?></span></td>
              <td>
                <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $f['id_fakultas'] ?>"><i class="bi bi-pencil"></i></button>
                <form method="post" style="display:inline;" onsubmit="return confirm('Hapus fakultas ini?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id_fakultas" value="<?= $f['id_fakultas'] ?>">
                  <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>

            <!-- Modal Edit -->
            <div class="modal fade" id="modalEdit<?= $f['id_fakultas'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title">Edit Fakultas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <form method="post">
                      <input type="hidden" name="action" value="edit">
                      <input type="hidden" name="id_fakultas" value="<?= $f['id_fakultas'] ?>">
                      <div class="mb-3"><label>Nama Fakultas</label><input name="nama" type="text" class="form-control" value="<?= htmlspecialchars($f['nama']) ?>" required></div>
                      <div class="mb-3"><label>Email</label><input name="email" type="email" class="form-control" value="<?= htmlspecialchars($f['email'] ?? '') ?>"></div>
                      <div class="mb-3"><label>No Telp</label><input name="no_telp" type="text" class="form-control" value="<?= htmlspecialchars($f['no_telp'] ?? '') ?>"></div>
                      <div class="mb-3"><label>Alamat</label><textarea name="alamat" class="form-control"><?= htmlspecialchars($f['alamat'] ?? '') ?></textarea></div>
                      <div class="mb-3"><label>Status</label>
                        <select name="status" class="form-select">
                          <option <?= $f['status']=='Aktif'?'selected':'' ?>>Aktif</option>
                          <option <?= $f['status']=='Tidak Aktif'?'selected':'' ?>>Tidak Aktif</option>
                        </select>
                      </div>
                      <div class="mb-3"><label>Tanggal Berdiri</label><input name="tanggal_berdiri" type="date" class="form-control" value="<?= htmlspecialchars($f['tanggal_berdiri'] ?? '') ?>"></div>
                      <div class="mb-3"><label>Deskripsi</label><textarea name="deskripsi" class="form-control"><?= htmlspecialchars($f['deskripsi'] ?? '') ?></textarea></div>
                      <button type="submit" class="btn btn-primary w-100">Simpan</button>
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
        <div class="modal-header"><h5 class="modal-title">Tambah Fakultas</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="mb-3"><label>Nama Fakultas</label><input name="nama" type="text" class="form-control" required></div>
            <div class="mb-3"><label>Email</label><input name="email" type="email" class="form-control"></div>
            <div class="mb-3"><label>No Telp</label><input name="no_telp" type="text" class="form-control"></div>
            <div class="mb-3"><label>Alamat</label><textarea name="alamat" class="form-control"></textarea></div>
            <div class="mb-3"><label>Status</label>
              <select name="status" class="form-select">
                <option value="Aktif">Aktif</option>
                <option value="Tidak Aktif">Tidak Aktif</option>
              </select>
            </div>
            <div class="mb-3"><label>Tanggal Berdiri</label><input name="tanggal_berdiri" type="date" class="form-control"></div>
            <div class="mb-3"><label>Deskripsi</label><textarea name="deskripsi" class="form-control"></textarea></div>
            <button type="submit" class="btn btn-primary w-100">Simpan</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
