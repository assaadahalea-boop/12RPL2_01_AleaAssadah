<?php
session_start();

/* ================= KONEKSI DB ================= */
$conn = new mysqli("localhost","root","","universitas");
if ($conn->connect_error) die("Koneksi gagal");

/* ================= HELPER ================= */
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES); }

/* ================= SIMULASI LOGIN DOSEN ================= */
$id_dosen = $_SESSION['nik'] ?? 1001; 

/* ================= TANGKAP PILIHAN UNIK ================= */
$pilihan_unik = $_GET['pilihan'] ?? '';
$selected_kelas = '';
$selected_matkul = '';

if ($pilihan_unik) {
    $parts = explode('|', $pilihan_unik);
    if (count($parts) == 2) {
        $selected_kelas = $parts[0];
        $selected_matkul = $parts[1];
    }
}
$selected_tugas = $_GET['tugas'] ?? '';

/* ================= SIMPAN NILAI ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_nilai'])) {
    $id_tugas = (int)$_POST['id_tugas'];
    $id_kelas = (int)$_POST['id_kelas'];
    $kode_matkul = $_POST['kode_matkul']; 

    $stmtDeadline = $conn->prepare("SELECT tanggal_deadline FROM tugas WHERE id_tugas = ?");
    $stmtDeadline->bind_param("i", $id_tugas);
    $stmtDeadline->execute();
    $deadline_res = $stmtDeadline->get_result()->fetch_assoc();
    $deadline_raw = $deadline_res['tanggal_deadline'] ?? date('Y-m-d H:i:s');
    $is_past_deadline = (strtotime(date('Y-m-d H:i:s')) > strtotime($deadline_raw));

    $stmtMhs = $conn->prepare("SELECT DISTINCT id_mahasiswa FROM krs WHERE id_kelas = ? AND kode_matkul = ? AND status = 'Disetujui'");
    $stmtMhs->bind_param("is", $id_kelas, $kode_matkul);
    $stmtMhs->execute();
    $resMhs = $stmtMhs->get_result();

    $stmtUpdate = $conn->prepare("INSERT INTO nilai_tugas (id_tugas, id_mahasiswa, nilai) VALUES (?,?,?) 
                                 ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");

    while ($mhs = $resMhs->fetch_assoc()) {
        $id_mahasiswa = (int)$mhs['id_mahasiswa'];
        $nilai_input = $_POST['nilai'][$id_mahasiswa] ?? '';
        
        if ($nilai_input === '' && $is_past_deadline) {
            $nilai = 0;
        } else {
            $nilai = ($nilai_input !== '') ? (float)$nilai_input : 0;
        }
        $stmtUpdate->bind_param("iid", $id_tugas, $id_mahasiswa, $nilai);
        $stmtUpdate->execute();
    }
    echo "<script>alert('Nilai berhasil disimpan'); location='nilai.php?pilihan=$pilihan_unik&tugas=$id_tugas';</script>";
    exit;
}

/* ================= DATA DROPDOWN ================= */
$queryKelas = "SELECT DISTINCT jk.id_kelas, jk.kode_matkul, mk.nama AS nama_matkul 
               FROM jadwal_kelas jk 
               JOIN mata_kuliah mk ON jk.kode_matkul = mk.kode_matkul 
               WHERE jk.id_dosen = ?
               ORDER BY mk.nama ASC";
$stmtK = $conn->prepare($queryKelas);
$stmtK->bind_param("i", $id_dosen);
$stmtK->execute();
$list_kelas = $stmtK->get_result();

$list_tugas = null;
if ($selected_kelas && $selected_matkul) {
    $queryT = "SELECT t.id_tugas, t.nama_tugas FROM tugas t 
               JOIN pertemuan p ON t.id_pertemuan = p.id_pertemuan 
               WHERE p.id_kelas = ?";
    $stmtT = $conn->prepare($queryT);
    $stmtT->bind_param("i", $selected_kelas);
    $stmtT->execute();
    $list_tugas = $stmtT->get_result();
}

/* ================= QUERY UTAMA MAHASISWA (FIX LARAGON) ================= */
$mahasiswa = null;
if ($selected_tugas && $selected_kelas && $selected_matkul) {
    $stmt = $conn->prepare("
        SELECT m.id_mahasiswa, m.nim, m.nama_mahasiswa,
               MAX(nt.nilai) AS nilai_db,
               MAX(tp.file_upload) AS file_upload, 
               MAX(tp.tanggal_submit) AS tanggal_submit, 
               MAX(t.tanggal_deadline) AS tanggal_deadline
        FROM mahasiswa m
        JOIN krs k ON m.id_mahasiswa = k.id_mahasiswa
        JOIN tugas t ON t.id_tugas = ?
        LEFT JOIN nilai_tugas nt ON nt.id_mahasiswa = m.id_mahasiswa AND nt.id_tugas = t.id_tugas
        LEFT JOIN tugas_pengumpulan tp ON tp.id_mahasiswa = m.id_mahasiswa AND tp.id_tugas = t.id_tugas
        WHERE k.id_kelas = ? AND k.kode_matkul = ? AND k.status = 'Disetujui'
        GROUP BY m.id_mahasiswa, m.nim, m.nama_mahasiswa 
        ORDER BY m.nama_mahasiswa ASC
    ");
    $stmt->bind_param("iis", $selected_tugas, $selected_kelas, $selected_matkul);
    $stmt->execute();
    $mahasiswa = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penilaian Tugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7fc; font-family: 'Poppins', sans-serif; }
        .content { margin-left: 270px; padding: 30px; }
        .section-box { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        @media (max-width: 992px) { .content { margin-left: 0; } }
    </style>
</head>
<body>

<?php if (file_exists('sidebar.php')) include 'sidebar.php'; ?>

<div class="content">
    <h4 class="fw-bold mb-4">Penilaian Tugas Mahasiswa</h4>
    
    <div class="section-box mb-4">
        <form method="GET" id="filterForm" class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-bold">1. Pilih Kelas</label>
                <select name="pilihan" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    <?php while ($k = $list_kelas->fetch_assoc()): 
                        $val = $k['id_kelas'] . '|' . $k['kode_matkul'];
                    ?>
                        <option value="<?= $val ?>" <?= $pilihan_unik == $val ? 'selected' : '' ?>>
                            Kelas <?= h($k['id_kelas']) ?> - <?= h($k['nama_matkul']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-5">
                <label class="form-label fw-bold">2. Pilih Tugas</label>
                <select name="tugas" class="form-select" <?= !$pilihan_unik ? 'disabled' : '' ?> onchange="this.form.submit()">
                    <option value="">-- Pilih Tugas --</option>
                    <?php if ($list_tugas): while ($t = $list_tugas->fetch_assoc()): ?>
                        <option value="<?= $t['id_tugas'] ?>" <?= $selected_tugas == $t['id_tugas'] ? 'selected' : '' ?>>
                            <?= h($t['nama_tugas']) ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($mahasiswa): ?>
    <div class="section-box">
        <form method="POST">
            <input type="hidden" name="id_tugas" value="<?= h($selected_tugas) ?>">
            <input type="hidden" name="id_kelas" value="<?= h($selected_kelas) ?>">
            <input type="hidden" name="kode_matkul" value="<?= h($selected_matkul) ?>">
            
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>NIM</th>
                        <th>Nama Mahasiswa</th>
                        <th class="text-center">File Tugas</th>
                        <th width="150" class="text-center">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($m = $mahasiswa->fetch_assoc()): ?>
                    <tr>
                        <td><?= h($m['nim']) ?></td>
                        <td><?= h($m['nama_mahasiswa']) ?></td>
                        <td class="text-center">
                            <?php if ($m['file_upload']): ?>
                                <a href="../Mahasiswa/uploads/<?= h($m['file_upload']) ?>" target="_blank" class="btn btn-sm btn-info text-white">
                                    <i class="bi bi-download"></i> Lihat File
                                </a>
                            <?php else: ?>
                                <span class="badge bg-secondary">Belum Kumpul</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <input type="number" name="nilai[<?= $m['id_mahasiswa'] ?>]" 
                                   class="form-control text-center" min="0" max="100" 
                                   value="<?= $m['nilai_db'] ?>">
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-end">
                <button type="submit" name="simpan_nilai" class="btn btn-primary px-4">
                    <i class="bi bi-save me-2"></i> Simpan Nilai
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

</body>
</html>