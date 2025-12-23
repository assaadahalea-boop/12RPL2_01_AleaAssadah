<?php
session_start();

/* ================= KONEKSI DB ================= */
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'universitas';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

/* ================= HELPER ================= */
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES); }

/* ================= AMBIL DATA LOGIN DOSEN ================= */
$id_dosen = $_SESSION['nik'] ?? 1001; 

/* ================= PROSES TANGKAP PILIHAN UNIK ================= */
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

/* ================= SIMPAN NILAI AKHIR (PROSES) ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_nilai_akhir'])) {
    $id_kelas = (int)$_POST['id_kelas'];
    $kode_matkul = $_POST['kode_matkul'];
    $nilaiData = $_POST['nilai'] ?? []; 
    
    $stmtCekKRS = $conn->prepare("SELECT id_krs FROM krs WHERE id_kelas=? AND kode_matkul=? AND id_mahasiswa=? AND status='Disetujui' LIMIT 1");
    $stmtCekNA = $conn->prepare("SELECT id_nilai_akhir FROM nilai_akhir WHERE id_krs=?");
    $stmtUpdateNA = $conn->prepare("UPDATE nilai_akhir SET nilai_uts=?, nilai_uas=?, nilai_akhir=?, grade=? WHERE id_nilai_akhir=?");
    $stmtInsertNA = $conn->prepare("INSERT INTO nilai_akhir (id_krs, nilai_uts, nilai_uas, nilai_akhir, grade) VALUES (?, ?, ?, ?, ?)");

    foreach ($nilaiData as $id_mhs => $data) {
        $id_mhs = (int)$id_mhs;
        $uts    = (float)($data['uts'] ?? 0);
        $uas    = (float)($data['uas'] ?? 0);
        $tugas  = (float)($data['rata_tugas'] ?? 0);
        $hadir  = (float)($data['kehadiran'] ?? 0); 
        
        // Perhitungan Bobot: Tugas 25%, UTS 25%, UAS 40%, Hadir 10%
        $na_angka = ($tugas * 0.25) + ($uts * 0.25) + ($uas * 0.4) + ($hadir * 0.1);
        
        if ($na_angka >= 85) $grade = 'A';
        elseif ($na_angka >= 75) $grade = 'B';
        elseif ($na_angka >= 65) $grade = 'C';
        elseif ($na_angka >= 55) $grade = 'D';
        else $grade = 'E';
        
        $stmtCekKRS->bind_param("isi", $id_kelas, $kode_matkul, $id_mhs);
        $stmtCekKRS->execute();
        $resKRS = $stmtCekKRS->get_result();
        
        if ($rowKRS = $resKRS->fetch_assoc()) {
            $id_krs = $rowKRS['id_krs'];
            $stmtCekNA->bind_param("i", $id_krs);
            $stmtCekNA->execute();
            $resNA = $stmtCekNA->get_result();
            
            if ($rowNA = $resNA->fetch_assoc()) {
                $stmtUpdateNA->bind_param("dddsi", $uts, $uas, $na_angka, $grade, $rowNA['id_nilai_akhir']);
                $stmtUpdateNA->execute();
            } else {
                $stmtInsertNA->bind_param("iddds", $id_krs, $uts, $uas, $na_angka, $grade);
                $stmtInsertNA->execute();
            }
        }
    }
    echo "<script>alert('Nilai Akhir Berhasil Disimpan!'); location='nilai_akhir_form.php?pilihan=$pilihan_unik';</script>";
    exit;
}

/* ================= DATA DROPDOWN KELAS ================= */
$queryKelas = "SELECT DISTINCT jk.id_kelas, jk.kode_matkul, mk.nama AS nama_matkul 
               FROM jadwal_kelas jk 
               JOIN mata_kuliah mk ON jk.kode_matkul = mk.kode_matkul 
               WHERE jk.id_dosen = ?";
$stmtK = $conn->prepare($queryKelas);
$stmtK->bind_param("i", $id_dosen);
$stmtK->execute();
$list_kelas = $stmtK->get_result();

/* ================= QUERY DATA MAHASISWA (FIX LARAGON) ================= */
$mahasiswa = null;
if ($selected_kelas && $selected_matkul) {
    $sql = "
    SELECT 
        m.id_mahasiswa, m.nim, m.nama_mahasiswa,
        MAX(na.nilai_uts) as nilai_uts, 
        MAX(na.nilai_uas) as nilai_uas, 
        MAX(na.nilai_akhir) as nilai_akhir, 
        MAX(na.grade) as grade,

        -- 1. RATA-RATA NILAI TUGAS
        IFNULL((
            SELECT AVG(nt.nilai)
            FROM nilai_tugas nt
            JOIN tugas t ON nt.id_tugas = t.id_tugas
            JOIN pertemuan p ON t.id_pertemuan = p.id_pertemuan
            WHERE p.id_kelas = ? AND nt.id_mahasiswa = m.id_mahasiswa
        ), 0) AS rata_tugas,

        -- 2. DETAIL NILAI TUGAS
        (
            SELECT GROUP_CONCAT(CONCAT('P', p.id_pertemuan, ':', nt.nilai) SEPARATOR ', ')
            FROM nilai_tugas nt
            JOIN tugas t ON nt.id_tugas = t.id_tugas
            JOIN pertemuan p ON t.id_pertemuan = p.id_pertemuan
            WHERE p.id_kelas = ? AND nt.id_mahasiswa = m.id_mahasiswa
        ) AS detail_tugas,

        -- 3. PERSENTASE KEHADIRAN
        IFNULL((
            SELECT (SUM(CASE WHEN a.status_absensi = 'Hadir' THEN 1 ELSE 0 END) / COUNT(*)) * 100
            FROM absensi a
            JOIN pertemuan p ON a.id_pertemuan = p.id_pertemuan
            WHERE p.id_kelas = ? AND a.nim_mahasiswa = m.nim
        ), 0) AS persen_hadir,

        -- 4. DETAIL BOLOS
        (
            SELECT GROUP_CONCAT(p.id_pertemuan SEPARATOR ', ')
            FROM absensi a
            JOIN pertemuan p ON a.id_pertemuan = p.id_pertemuan
            WHERE p.id_kelas = ? AND a.nim_mahasiswa = m.nim AND a.status_absensi != 'Hadir'
        ) AS detail_bolos

    FROM krs k
    JOIN mahasiswa m ON k.id_mahasiswa = m.id_mahasiswa
    LEFT JOIN nilai_akhir na ON na.id_krs = k.id_krs
    WHERE k.id_kelas = ? 
    AND k.kode_matkul = ? 
    AND k.status = 'Disetujui'
    GROUP BY m.id_mahasiswa, m.nim, m.nama_mahasiswa
    ORDER BY m.nama_mahasiswa ASC";

    $stmtMhs = $conn->prepare($sql);
    $stmtMhs->bind_param("iiiiis", 
        $selected_kelas, $selected_kelas, $selected_kelas, $selected_kelas, $selected_kelas, $selected_matkul 
    );
    $stmtMhs->execute();
    $mahasiswa = $stmtMhs->get_result();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Nilai Akhir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .content { margin-left: 260px; padding: 30px; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background: #fff; }
        .detail-sub { font-size: 0.7rem; color: #888; display: block; margin-top: 2px; }
        @media (max-width: 992px) { .content { margin-left: 0; } }
    </style>
</head>
<body>

<?php if (file_exists('sidebar.php')) include 'sidebar.php'; ?>

<div class="content">
    <h3 class="fw-bold mb-4 text-dark">Laporan & Penginputan Nilai Akhir</h3>

    <div class="card-custom p-4 mb-4">
        <form method="GET">
            <label class="form-label fw-bold">Pilih Mata Kuliah</label>
            <div class="d-flex gap-2">
                <select name="pilihan" class="form-select form-select-lg w-50" onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    <?php while ($k = $list_kelas->fetch_assoc()): 
                        $val_unik = $k['id_kelas'] . '|' . $k['kode_matkul'];
                    ?>
                        <option value="<?= $val_unik ?>" <?= $pilihan_unik == $val_unik ? 'selected' : '' ?>>
                            Kelas <?= h($k['id_kelas']) ?> - <?= h($k['nama_matkul']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($selected_kelas && $mahasiswa): ?>
    <div class="card-custom overflow-hidden">
        <form method="POST">
            <input type="hidden" name="id_kelas" value="<?= h($selected_kelas) ?>">
            <input type="hidden" name="kode_matkul" value="<?= h($selected_matkul) ?>">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark text-center">
                        <tr>
                            <th class="py-3">Mahasiswa</th>
                            <th>Hadir (10%)</th>
                            <th>Tugas (25%)</th>
                            <th width="110">UTS (25%)</th>
                            <th width="110">UAS (40%)</th>
                            <th>Total</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($m = $mahasiswa->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold d-block"><?= h($m['nama_mahasiswa']) ?></span>
                                <small class="text-muted"><?= h($m['nim']) ?></small>
                            </td>
                            <td class="text-center">
                                <input type="hidden" name="nilai[<?= $m['id_mahasiswa'] ?>][kehadiran]" value="<?= $m['persen_hadir'] ?>">
                                <span class="fw-bold fs-6"><?= round($m['persen_hadir'] ?? 0) ?>%</span>
                                <span class="detail-sub <?= $m['detail_bolos'] ? 'text-danger' : 'text-success' ?>">
                                    <?= $m['detail_bolos'] ? 'Bolos P: '.$m['detail_bolos'] : 'Hadir Semua' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <input type="hidden" name="nilai[<?= $m['id_mahasiswa'] ?>][rata_tugas]" value="<?= $m['rata_tugas'] ?>">
                                <span class="fw-bold"><?= number_format($m['rata_tugas'] ?? 0, 1) ?></span>
                                <span class="detail-sub"><?= $m['detail_tugas'] ?: 'Kosong' ?></span>
                            </td>
                            <td>
                                <input type="number" name="nilai[<?= $m['id_mahasiswa'] ?>][uts]" class="form-control text-center uts-field" 
                                       data-id="<?= $m['id_mahasiswa'] ?>" step="0.01" value="<?= $m['nilai_uts'] ?>">
                            </td>
                            <td>
                                <input type="number" name="nilai[<?= $m['id_mahasiswa'] ?>][uas]" class="form-control text-center uas-field" 
                                       data-id="<?= $m['id_mahasiswa'] ?>" step="0.01" value="<?= $m['nilai_uas'] ?>">
                            </td>
                            <td class="text-center fw-bold text-primary fs-5" id="total-<?= $m['id_mahasiswa'] ?>">
                                <?= number_format($m['nilai_akhir'] ?? 0, 2) ?>
                            </td>
                            <td class="text-center">
                                <?php 
                                    $g = $m['grade'] ?? '-';
                                    $badge = ($g == 'A' || $g == 'B') ? 'bg-success' : (($g == 'E') ? 'bg-danger' : 'bg-warning text-dark');
                                ?>
                                <span class="badge <?= $badge ?> px-3 py-2 grade-output" id="grade-<?= $m['id_mahasiswa'] ?>">
                                    <?= $g ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-light d-flex justify-content-end">
                <button type="submit" name="simpan_nilai_akhir" class="btn btn-primary px-5 shadow">
                    <i class="bi bi-save me-2"></i> Simpan Permanen ke DB
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.uts-field, .uas-field').forEach(input => {
    input.addEventListener('input', function() {
        const id = this.dataset.id;
        const uts = parseFloat(document.querySelector(`.uts-field[data-id="${id}"]`).value) || 0;
        const uas = parseFloat(document.querySelector(`.uas-field[data-id="${id}"]`).value) || 0;
        
        const tugas = parseFloat(document.getElementsByName(`nilai[${id}][rata_tugas]`)[0].value) || 0;
        const hadir = parseFloat(document.getElementsByName(`nilai[${id}][kehadiran]`)[0].value) || 0;

        const total = (tugas * 0.25) + (uts * 0.25) + (uas * 0.4) + (hadir * 0.1);
        document.getElementById(`total-${id}`).innerText = total.toFixed(2);

        let grade = 'E', badge = 'bg-danger';
        if (total >= 85) { grade = 'A'; badge = 'bg-success'; }
        else if (total >= 75) { grade = 'B'; badge = 'bg-success'; }
        else if (total >= 65) { grade = 'C'; badge = 'bg-warning text-dark'; }
        else if (total >= 55) { grade = 'D'; badge = 'bg-warning text-dark'; }

        const gDisp = document.getElementById(`grade-${id}`);
        gDisp.innerText = grade;
        gDisp.className = `badge ${badge} px-3 py-2 grade-output`;
    });
});
</script>
</body>
</html>