<?php
session_start();
// datakelas.php - Disesuaikan dengan struktur universitas (27).sql

define('DB_HOST','localhost'); 
define('DB_NAME','universitas');
define('DB_USER','root'); 
define('DB_PASS','');

try { 
  $pdo = new PDO(
    "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", 
    DB_USER, 
    DB_PASS, 
    [
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, 
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]
  ); 
} catch(Exception $e){ 
  die("DB error: ".$e->getMessage()); 
}

/* =========================
   PROSES ADD / EDIT / DELETE
   ========================= */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $a = $_POST['action'] ?? '';

  if ($a === 'add') {
    // Validasi maksimal 3 kelas per dosen
    if (!empty($_POST['id_dosen'])) {
      $cek = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE id_dosen = ?");
      $cek->execute([$_POST['id_dosen']]);
      if ($cek->fetchColumn() >= 3) {
        echo "<script>alert('Dosen ini sudah mengampu maksimal 3 kelas!'); history.back();</script>";
        exit;
      }
    }

    $pdo->prepare("
      INSERT INTO kelas (id_dosen, jam_mulai, jam_selesai, kapasitas, hari, ruangan) 
      VALUES (?,?,?,?,?,?)
    ")->execute([
      $_POST['id_dosen'] ?: null, 
      $_POST['jam_mulai'] ?: null, 
      $_POST['jam_selesai'] ?: null, 
      $_POST['kapasitas'] ?: 50,
      $_POST['hari'] ?: null,
      $_POST['ruangan'] ?: null
    ]);

  } elseif ($a === 'edit') {
    if (!empty($_POST['id_dosen'])) {
      $cek = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE id_dosen = ? AND id_kelas != ?");
      $cek->execute([$_POST['id_dosen'], $_POST['id_kelas']]);
      if ($cek->fetchColumn() >= 3) {
        echo "<script>alert('Dosen ini sudah mengampu maksimal 3 kelas!'); history.back();</script>";
        exit;
      }
    }

    $pdo->prepare("
      UPDATE kelas SET id_dosen=?, jam_mulai=?, jam_selesai=?, kapasitas=?, hari=?, ruangan=? 
      WHERE id_kelas=?
    ")->execute([
      $_POST['id_dosen'] ?: null, 
      $_POST['jam_mulai'] ?: null, 
      $_POST['jam_selesai'] ?: null, 
      $_POST['kapasitas'] ?: 50,
      $_POST['hari'] ?: null,
      $_POST['ruangan'] ?: null,
      $_POST['id_kelas']
    ]);

  } elseif ($a === 'delete') {
    $pdo->prepare("DELETE FROM kelas WHERE id_kelas=?")->execute([$_POST['id_kelas']]);
  }

  header("Location: datakelas.php"); 
  exit;
}

/* =========================
   DATA UNTUK TABEL
   ========================= */
// Mengambil data kelas dan join ke dosen (Sesuai tabel kelas di SQL)
$kelas = $pdo->query("
  SELECT k.*, d.nama AS nama_dosen
  FROM kelas k 
  LEFT JOIN dosen d ON k.id_dosen = d.nik 
  ORDER BY k.id_kelas
")->fetchAll();

$dosen = $pdo->query("SELECT nik, nama FROM dosen ORDER BY nama")->fetchAll();
$hari_list = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Master Kelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="Style.css" rel="stylesheet">
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4>🏫 Data Master Kelas</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
      <i class="bi bi-plus-circle"></i> Tambah Kelas
    </button>
  </div>

  <div class="card shadow-sm p-3">
    <table class="table table-hover align-middle text-center">
      <thead class="table-dark">
        <tr>
          <th>ID Kelas</th>
          <th>Dosen Pengampu</th>
          <th>Hari</th>
          <th>Jam</th>
          <th>Ruangan</th>
          <th>Kapasitas</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($kelas as $k): ?>
        <tr>
          <td><?= $k['id_kelas'] ?></td>
          <td><?= htmlspecialchars($k['nama_dosen'] ?? '-') ?></td>
          <td><?= htmlspecialchars($k['hari'] ?? '-') ?></td>
          <td>
            <?= ($k['jam_mulai']) ? substr($k['jam_mulai'],0,5) . ' - ' . substr($k['jam_selesai'],0,5) : '-' ?>
          </td>
          <td><?= htmlspecialchars($k['ruangan'] ?? '-') ?></td>
          <td><?= $k['kapasitas'] ?></td>
          <td>
            <button class="btn btn-sm btn-warning btn-edit" 
              data-id="<?= $k['id_kelas'] ?>"
              data-dosen="<?= $k['id_dosen'] ?>"
              data-hari="<?= $k['hari'] ?>"
              data-ruangan="<?= $k['ruangan'] ?>"
              data-jammulai="<?= $k['jam_mulai'] ?>"
              data-jamsel="<?= $k['jam_selesai'] ?>"
              data-kapasitas="<?= $k['kapasitas'] ?>"
              data-bs-toggle="modal" data-bs-target="#modalEdit">
              <i class="bi bi-pencil"></i>
            </button>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus kelas ini?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id_kelas" value="<?= $k['id_kelas'] ?>">
              <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5>Tambah Kelas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="mb-3">
          <label class="form-label">Dosen Pengampu</label>
          <select name="id_dosen" class="form-select">
            <option value="">-- Pilih Dosen --</option>
            <?php foreach($dosen as $d): ?>
              <option value="<?= $d['nik'] ?>"><?= htmlspecialchars($d['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row">
            <div class="col-6 mb-3"><label class="form-label">Hari</label><select name="hari" class="form-select"><?php foreach($hari_list as $h) echo "<option value='$h'>$h</option>"; ?></select></div>
            <div class="col-6 mb-3"><label class="form-label">Ruangan</label><input name="ruangan" class="form-control"></div>
        </div>
        <div class="row">
            <div class="col-6 mb-3"><label class="form-label">Jam Mulai</label><input name="jam_mulai" type="time" class="form-control"></div>
            <div class="col-6 mb-3"><label class="form-label">Jam Selesai</label><input name="jam_selesai" type="time" class="form-control"></div>
        </div>
        <div class="mb-3"><label class="form-label">Kapasitas</label><input name="kapasitas" type="number" class="form-control" value="50"></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-primary w-100">Simpan Kelas</button></div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5>Edit Kelas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" id="edit_id_kelas" name="id_kelas">
        <div class="mb-3">
          <label class="form-label">Dosen Pengampu</label>
          <select name="id_dosen" id="edit_dosen" class="form-select">
            <option value="">-- Pilih Dosen --</option>
            <?php foreach($dosen as $d): ?>
              <option value="<?= $d['nik'] ?>"><?= htmlspecialchars($d['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row">
            <div class="col-6 mb-3"><label class="form-label">Hari</label><select name="hari" id="edit_hari" class="form-select"><?php foreach($hari_list as $h) echo "<option value='$h'>$h</option>"; ?></select></div>
            <div class="col-6 mb-3"><label class="form-label">Ruangan</label><input name="ruangan" id="edit_ruangan" class="form-control"></div>
        </div>
        <div class="row">
            <div class="col-6 mb-3"><label class="form-label">Jam Mulai</label><input name="jam_mulai" id="edit_jammulai" type="time" class="form-control"></div>
            <div class="col-6 mb-3"><label class="form-label">Jam Selesai</label><input name="jam_selesai" id="edit_jamsel" type="time" class="form-control"></div>
        </div>
        <div class="mb-3"><label class="form-label">Kapasitas</label><input name="kapasitas" id="edit_kapasitas" type="number" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Simpan Perubahan</button></div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    const d = btn.dataset;
    document.getElementById('edit_id_kelas').value = d.id;
    document.getElementById('edit_dosen').value = d.dosen;
    document.getElementById('edit_hari').value = d.hari;
    document.getElementById('edit_ruangan').value = d.ruangan;
    document.getElementById('edit_jammulai').value = d.jammulai;
    document.getElementById('edit_jamsel').value = d.jamsel;
    document.getElementById('edit_kapasitas').value = d.kapasitas;
  });
});
</script>
</body>
</html>