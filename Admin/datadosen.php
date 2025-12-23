<?php
// datadosen.php
define('DB_HOST','localhost'); define('DB_NAME','universitas');
define('DB_USER','root'); define('DB_PASS','');

try {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
  ]);
} catch (Exception $e) { die("Koneksi DB gagal: ".$e->getMessage()); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $stmt = $pdo->prepare("INSERT INTO dosen (nik,nama,email,no_hp) VALUES (:nik,:nama,:email,:no_hp)");
    $stmt->execute([':nik'=>$_POST['nik'], ':nama'=>$_POST['nama'], ':email'=>$_POST['email'], ':no_hp'=>$_POST['no_hp']]);
  } elseif ($action === 'edit') {
    $stmt = $pdo->prepare("UPDATE dosen SET nama=:nama, email=:email, no_hp=:no_hp WHERE nik=:nik");
    $stmt->execute([':nama'=>$_POST['nama'], ':email'=>$_POST['email'], ':no_hp'=>$_POST['no_hp'], ':nik'=>$_POST['nik']]);
  } elseif ($action === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM dosen WHERE nik=?");
    $stmt->execute([$_POST['nik']]);
  }
  header("Location: datadosen.php"); exit;
}

$dosen = $pdo->query("SELECT * FROM dosen ORDER BY nama")->fetchAll();
?>
<!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Data Dosen</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">
</head><body>
<?php include 'sidebar.php'; ?>
<div class="content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4>👩‍🏫 Data Dosen</h4>
    <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-circle"></i> Tambah Dosen</button>
  </div>

  <div class="table-responsive">
    <table class="table table-striped text-center align-middle">
      <thead><tr><th>NIK</th><th>Nama</th><th>Email</th><th>No HP</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach($dosen as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['nik']) ?></td>
          <td><?= htmlspecialchars($d['nama']) ?></td>
          <td><?= htmlspecialchars($d['email']) ?></td>
          <td><?= htmlspecialchars($d['no_hp']) ?></td>
          <td>
            <button class="btn btn-sm btn-warning btn-edit" data-nik="<?= $d['nik'] ?>" data-nama="<?= htmlspecialchars($d['nama']) ?>" data-email="<?= htmlspecialchars($d['email']) ?>" data-no="<?= htmlspecialchars($d['no_hp']) ?>" data-bs-toggle="modal" data-bs-target="#modalEdit"><i class="bi bi-pencil"></i></button>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus dosen?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="nik" value="<?= $d['nik'] ?>">
              <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Tambah Dosen</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="post" class="modal-body">
    <input type="hidden" name="action" value="add">
    <div class="mb-3"><label class="form-label">NIK</label><input name="nik" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Nama</label><input name="nama" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
    <div class="mb-3"><label class="form-label">No HP</label><input name="no_hp" class="form-control"></div>
    <button class="btn btn-primary w-100" type="submit">Simpan</button>
  </form>
</div></div></div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Edit Dosen</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="post" class="modal-body">
    <input type="hidden" name="action" value="edit">
    <div class="mb-3"><label class="form-label">NIK</label><input id="edit_nik" name="nik" class="form-control" readonly></div>
    <div class="mb-3"><label class="form-label">Nama</label><input id="edit_nama" name="nama" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input id="edit_email" name="email" type="email" class="form-control"></div>
    <div class="mb-3"><label class="form-label">No HP</label><input id="edit_no" name="no_hp" class="form-control"></div>
    <button class="btn btn-primary w-100" type="submit">Simpan Perubahan</button>
  </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.btn-edit').forEach(btn=>{
  btn.addEventListener('click', ()=> {
    document.getElementById('edit_nik').value = btn.dataset.nik;
    document.getElementById('edit_nama').value = btn.dataset.nama;
    document.getElementById('edit_email').value = btn.dataset.email;
    document.getElementById('edit_no').value = btn.dataset.no;
  });
});
</script>
</body></html>
