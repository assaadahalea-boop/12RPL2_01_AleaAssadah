<?php
// datamatakuliah.php
define('DB_HOST','localhost'); 
define('DB_NAME','universitas');
define('DB_USER','root'); 
define('DB_PASS','');

try { 
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]); 
} catch(Exception $e){ 
    die("DB error: ".$e->getMessage()); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    if ($a==='add') {
        // Menambahkan id_dosen sesuai struktur tabel SQL
        $pdo->prepare("INSERT INTO mata_kuliah (kode_matkul, nama, sks, semester, id_dosen) VALUES (?,?,?,?,?)")
            ->execute([$_POST['kode_matkul'], $_POST['nama_matkul'], $_POST['sks']?:null, $_POST['semester']?:null, $_POST['id_dosen']?:null]);
    } elseif ($a==='edit') {
        $pdo->prepare("UPDATE mata_kuliah SET nama=?, sks=?, semester=?, id_dosen=? WHERE kode_matkul=?")
            ->execute([$_POST['nama_matkul'], $_POST['sks']?:null, $_POST['semester']?:null, $_POST['id_dosen']?:null, $_POST['kode_matkul']]);
    } elseif ($a==='delete') {
        $pdo->prepare("DELETE FROM mata_kuliah WHERE kode_matkul=?")->execute([$_POST['kode_matkul']]);
    }
    header("Location: datamatakuliah.php"); exit;
}

// Mengambil data matkul beserta nama dosen pengampu (menggunakan LEFT JOIN)
$query = "SELECT mk.*, d.nama as nama_dosen 
          FROM mata_kuliah mk 
          LEFT JOIN dosen d ON mk.id_dosen = d.nik 
          ORDER BY mk.semester, mk.kode_matkul";
$matkul = $pdo->query($query)->fetchAll();

// Mengambil daftar dosen untuk pilihan di modal
$dosen_list = $pdo->query("SELECT nik, nama FROM dosen ORDER BY nama ASC")->fetchAll();
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Data Mata Kuliah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>📚 Data Mata Kuliah</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-circle"></i> Tambah Matkul</button>
    </div>

    <table class="table table-striped table-hover text-center align-middle">
        <thead class="table-dark">
            <tr>
                <th>Kode</th>
                <th>Nama Matakuliah</th>
                <th>SKS</th>
                <th>Semester</th>
                <th>Dosen Pengampu</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($matkul as $m): ?>
            <tr>
                <td><strong><?= htmlspecialchars($m['kode_matkul']) ?></strong></td>
                <td><?= htmlspecialchars($m['nama']) ?></td>
                <td><?= htmlspecialchars($m['sks']) ?></td>
                <td><?= htmlspecialchars($m['semester']) ?></td>
                <td><?= htmlspecialchars($m['nama_dosen'] ?? '-') ?></td>
                <td>
                    <button class="btn btn-sm btn-warning btn-edit" 
                            data-kode="<?= htmlspecialchars($m['kode_matkul']) ?>" 
                            data-nama="<?= htmlspecialchars($m['nama']) ?>" 
                            data-sks="<?= $m['sks'] ?>" 
                            data-sem="<?= $m['semester'] ?>" 
                            data-dosen="<?= $m['id_dosen'] ?>"
                            data-bs-toggle="modal" data-bs-target="#modalEdit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="post" class="d-inline" onsubmit="return confirm('Hapus matkul ini?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="kode_matkul" value="<?= $m['kode_matkul'] ?>">
                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Mata Kuliah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="mb-3"><label class="form-label">Kode Matkul</label><input name="kode_matkul" class="form-control" placeholder="Contoh: MK-001" required></div>
                <div class="mb-3"><label class="form-label">Nama Mata Kuliah</label><input name="nama_matkul" class="form-control" required></div>
                <div class="mb-2 row">
                    <div class="col"><label class="form-label">SKS</label><input name="sks" type="number" class="form-control" min="1" max="6"></div>
                    <div class="col"><label class="form-label">Semester</label><input name="semester" type="number" class="form-control" min="1" max="8"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Dosen Pengampu</label>
                    <select name="id_dosen" class="form-select">
                        <option value="">-- Pilih Dosen --</option>
                        <?php foreach($dosen_list as $d): ?>
                            <option value="<?= $d['nik'] ?>"><?= htmlspecialchars($d['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary w-100" type="submit">Simpan Data</button>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Mata Kuliah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="modal-body">
                <input type="hidden" name="action" value="edit">
                <div class="mb-3"><label class="form-label">Kode Matkul</label><input id="edit_kode" name="kode_matkul" class="form-control" readonly></div>
                <div class="mb-3"><label class="form-label">Nama Mata Kuliah</label><input id="edit_nama" name="nama_matkul" class="form-control" required></div>
                <div class="mb-2 row">
                    <div class="col"><label class="form-label">SKS</label><input id="edit_sks" name="sks" type="number" class="form-control"></div>
                    <div class="col"><label class="form-label">Semester</label><input id="edit_sem" name="semester" type="number" class="form-control"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Dosen Pengampu</label>
                    <select id="edit_dosen" name="id_dosen" class="form-select">
                        <option value="">-- Pilih Dosen --</option>
                        <?php foreach($dosen_list as $d): ?>
                            <option value="<?= $d['nik'] ?>"><?= htmlspecialchars($d['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary w-100" type="submit">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.btn-edit').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        document.getElementById('edit_kode').value = btn.dataset.kode || '';
        document.getElementById('edit_nama').value = btn.dataset.nama || '';
        document.getElementById('edit_sks').value = btn.dataset.sks || '';
        document.getElementById('edit_sem').value = btn.dataset.sem || '';
        document.getElementById('edit_dosen').value = btn.dataset.dosen || '';
    });
});
</script>
</body>
</html>