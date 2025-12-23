<?php
session_start();

// ====== Koneksi Database ======
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'universitas';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Koneksi gagal: ' . $conn->connect_error);
}

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES); }

// --- PENGUJIAN MANUAL ---
$_SESSION['user_nik'] = 1001; 
$id_dosen = $_SESSION['user_nik'] ?? null;

// ====== Simpan Nilai Akhir ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_nilai_akhir'])) {
    $id_kelas = (int)$_POST['id_kelas'];
    $nilaiData = $_POST['nilai'] ?? []; 
    
    $pesan = 'Nilai Akhir berhasil disimpan!';
    $error = false;

    $stmtCekKRS = $conn->prepare("SELECT id_krs FROM krs WHERE id_kelas=? AND id_mahasiswa=?");
    $stmtCekNA = $conn->prepare("SELECT id_nilai_akhir FROM nilai_akhir WHERE id_krs=?");
    $stmtUpdateNA = $conn->prepare("UPDATE nilai_akhir SET nilai_uts=?, nilai_uas=?, nilai_akhir=?, grade=? WHERE id_nilai_akhir=?");
    $stmtInsertNA = $conn->prepare("INSERT INTO nilai_akhir (id_krs, nilai_uts, nilai_uas, nilai_akhir, grade) VALUES (?, ?, ?, ?, ?)");

    foreach ($nilaiData as $id_mahasiswa => $data) {
        $nilai_uts = (float)($data['uts'] ?? 0);
        $nilai_uas = (float)($data['uas'] ?? 0);
        $rata_tugas = (float)($data['rata_tugas'] ?? 0);
        $persen_hadir = (float)($data['kehadiran'] ?? 0);
        
        // Bobot: Hadir 10%, Tugas 25%, UTS 25%, UAS 40% (Total 100%)
        $nilai_akhir_angka = ($persen_hadir * 0.1) + ($rata_tugas * 0.25) + ($nilai_uts * 0.25) + ($nilai_uas * 0.4);
        $nilai_akhir_angka = round($nilai_akhir_angka, 2);
        
        if ($nilai_akhir_angka >= 85) $grade = 'A';
        elseif ($nilai_akhir_angka >= 75) $grade = 'B';
        elseif ($nilai_akhir_angka >= 65) $grade = 'C';
        elseif ($nilai_akhir_angka >= 55) $grade = 'D';
        else $grade = 'E';
        
        $stmtCekKRS->bind_param("ii", $id_kelas, $id_mahasiswa);
        $stmtCekKRS->execute();
        $resKRS = $stmtCekKRS->get_result();
        if ($resKRS->num_rows === 0) continue;
        $id_krs = $resKRS->fetch_assoc()['id_krs'];

        $stmtCekNA->bind_param("i", $id_krs);
        $stmtCekNA->execute();
        $resNA = $stmtCekNA->get_result();
        
        if ($resNA->num_rows > 0) {
            $id_na = $resNA->fetch_assoc()['id_nilai_akhir'];
            $stmtUpdateNA->bind_param("dddsi", $nilai_uts, $nilai_uas, $nilai_akhir_angka, $grade, $id_na);
            $stmtUpdateNA->execute();
        } else {
            $stmtInsertNA->bind_param("iddds", $id_krs, $nilai_uts, $nilai_uas, $nilai_akhir_angka, $grade);
            $stmtInsertNA->execute();
        }
    }
    echo "<script>alert('{$pesan}'); window.location='nilai_akhir_form.php?kelas={$id_kelas}';</script>";
    exit;
}

// ====== Ambil Data Dropdown ======
$stmtKelas = $conn->prepare("SELECT k.id_kelas, mk.nama FROM kelas k JOIN mata_kuliah mk ON k.kode_matkul = mk.kode_matkul WHERE k.id_dosen = ?");
$stmtKelas->bind_param("i", $id_dosen);
$stmtKelas->execute();
$kelas = $stmtKelas->get_result();

$selected_kelas = $_GET['kelas'] ?? '';
$mahasiswa = null;

if ($selected_kelas) {
    $sql = "
        SELECT 
            m.id_mahasiswa, m.nim, m.nama_mahasiswa, 
            na.nilai_uts, na.nilai_uas, na.nilai_akhir, na.grade,
            -- Rata-rata Tugas
            (SELECT AVG(nt.nilai) FROM nilai_tugas nt JOIN tugas t ON nt.id_tugas = t.id_tugas JOIN pertemuan p ON t.id_pertemuan = p.id_pertemuan WHERE p.id_kelas = ? AND nt.id_mahasiswa = m.id_mahasiswa) AS rata_tugas,
            -- Persentase Kehadiran (Hadir / Total Pertemuan * 100)
            (SELECT (COUNT(CASE WHEN status_absensi='Hadir' THEN 1 END) / COUNT(*)) * 100 
             FROM absensi a JOIN pertemuan p ON a.id_pertemuan = p.id_pertemuan 
             WHERE p.id_kelas = ? AND a.nim_mahasiswa = m.nim) AS persen_hadir
        FROM mahasiswa m
        JOIN krs ON krs.id_mahasiswa = m.id_mahasiswa AND krs.id_kelas = ?
        LEFT JOIN nilai_akhir na ON na.id_krs = krs.id_krs
        WHERE krs.status = 'Disetujui'
        ORDER BY m.nama_mahasiswa ASC";
    
    $stmtMhs = $conn->prepare($sql);
    $stmtMhs->bind_param("iii", $selected_kelas, $selected_kelas, $selected_kelas); 
    $stmtMhs->execute();
    $mahasiswa = $stmtMhs->get_result();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Nilai Akhir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7fc; font-family: 'Poppins', sans-serif; }
        .sidebar { width: 250px; min-height: 100vh; background: linear-gradient(180deg, #1565c0, #1e88e5); position: fixed; left: 0; top: 0; padding: 25px 0; color: white; }
        .content { margin-left: 270px; padding: 30px; }
        .penilaian-box { background: #fff; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 30px; }
        @media print {
            .sidebar, .btn, .form-select, .alert, form > button, .no-print { display: none !important; }
            .content { margin-left: 0; padding: 0; }
            .penilaian-box { box-shadow: none; border: none; }
            input { border: none !important; background: transparent !important; text-align: center; width: 50px; }
        }
    </style>
</head>
<body>

<div class="sidebar no-print">
    <h4 class="text-center">SIAKAD</h4>
    <a href="#" class="nav-link text-white px-3 mt-4 active"><i class="bi bi-bar-chart"></i> Input Nilai</a>
</div>

<div class="content">
    <h4 class="fw-semibold mb-4 no-print"><i class="bi bi-bar-chart"></i> Input Nilai Akhir Mata Kuliah</h4>
    
    <div class="d-none d-print-block text-center mb-4">
        <h3>Laporan Nilai Mahasiswa</h3>
        <h5>Mata Kuliah: (<?= h($selected_kelas) ?>)</h5>
    </div>

    <div class="penilaian-box">
        <form method="GET" class="d-flex flex-wrap gap-2 mb-3 no-print">
            <select name="kelas" class="form-select w-auto" required>
                <option value="">Pilih Mata Kuliah & Kelas</option>
                <?php while ($k = $kelas->fetch_assoc()): ?>
                    <option value="<?= h($k['id_kelas']) ?>" <?= $selected_kelas == $k['id_kelas'] ? 'selected' : '' ?>>
                        Kelas <?= h($k['id_kelas']); ?> - <?= h($k['nama']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
        </form>
      
        <?php if ($selected_kelas && $mahasiswa && $mahasiswa->num_rows > 0): ?>
            <div class="alert alert-info py-2 no-print">
                ⚠ *Bobot:* Absensi (10%) + Tugas (25%) + UTS (25%) + UAS (40%).
            </div>
            
            <form method="POST">
                <input type="hidden" name="id_kelas" value="<?= h($selected_kelas) ?>">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-danger text-white">
                            <tr>
                                <th>NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th>Absen(10%)</th>
                                <th>Tugas(25%)</th>
                                <th>UTS(25%)</th>
                                <th>UAS(40%)</th>
                                <th>Total</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($mhs = $mahasiswa->fetch_assoc()): 
                                $rata_tugas = $mhs['rata_tugas'] ?? 0;
                                $persen_hadir = $mhs['persen_hadir'] ?? 0;
                            ?>
                                <tr>
                                    <td><?= h($mhs['nim']); ?></td>
                                    <td class="text-start"><?= h($mhs['nama_mahasiswa']); ?></td>
                                    <td class="bg-light">
                                        <input type="hidden" name="nilai[<?= $mhs['id_mahasiswa']; ?>][kehadiran]" value="<?= $persen_hadir ?>">
                                        <?= number_format($persen_hadir, 0); ?>%
                                    </td>
                                    <td class="bg-light">
                                        <input type="hidden" name="nilai[<?= $mhs['id_mahasiswa']; ?>][rata_tugas]" value="<?= $rata_tugas ?>">
                                        <?= number_format($rata_tugas, 2); ?>
                                    </td>
                                    <td>
                                        <input type="number" name="nilai[<?= $mhs['id_mahasiswa']; ?>][uts]" class="form-control text-center mx-auto" style="width: 80px;" step="0.01" value="<?= h($mhs['nilai_uts'] ?? ''); ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" name="nilai[<?= $mhs['id_mahasiswa']; ?>][uas]" class="form-control text-center mx-auto" style="width: 80px;" step="0.01" value="<?= h($mhs['nilai_uas'] ?? ''); ?>" required>
                                    </td>
                                    <td class="fw-bold"><?= $mhs['nilai_akhir'] ? number_format($mhs['nilai_akhir'], 2) : '-'; ?></td>
                                    <td class="fw-bold text-success"><?= h($mhs['grade'] ?? '-'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 no-print">
                    <button type="submit" name="simpan_nilai_akhir" class="btn btn-success">
                        <i class="bi bi-save"></i> Simpan Nilai
                    </button>
                    <button type="button" onclick="window.print()" class="btn btn-secondary shadow-sm">
                        <i class="bi bi-printer"></i> Cetak Laporan
                    </button>
                </div>
            </form>
        <?php elseif ($selected_kelas): ?>
            <p class="alert alert-info text-center">Tidak ditemukan mahasiswa atau krs belum disetujui.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>