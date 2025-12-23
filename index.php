<?php
session_start();

// ==============================
// KONEKSI DATABASE
// ==============================
try {
    $db = new PDO("mysql:host=127.0.0.1;dbname=universitas;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// ==============================
// PROSES LOGIN
// ==============================
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ===========================
    // CEK LOGIN ADMIN (HARD CODE)
    // ===========================
    if ($username === "adm" && $password === "123") {

        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = "Administrator";

        header("Location: admin/dashboard.php");
        exit;
    }

    // ===========================
    // CEK LOGIN MAHASISWA
    // Username = nama_mahasiswa
    // Password = nim
    // ===========================
    $q = $db->prepare("SELECT * FROM mahasiswa WHERE nama_mahasiswa = ?");
    $q->execute([$username]);
    $mhs = $q->fetch();

    if ($mhs) {

        if ($password == $mhs['nim']) {

            $_SESSION['role'] = 'mahasiswa';
            $_SESSION['id_mahasiswa'] = $mhs['id_mahasiswa'];

            header("Location: Mahasiswa/dashboard.php");
            exit;
        }
    }

    // ===========================
    // CEK LOGIN DOSEN
    // Username = nama
    // Password = nik
    // ===========================
    $q = $db->prepare("SELECT * FROM dosen WHERE nama = ?");
    $q->execute([$username]);
    $dsn = $q->fetch();

    if ($dsn) {

        if ($password == $dsn['nik']) {

            $_SESSION['role'] = 'dosen';
            $_SESSION['nik'] = $dsn['nik'];
            $_SESSION['nama'] = $dsn['nama'];

            header("Location: Dosen/dashboard.php");
            exit;
        }
    }

    // ===========================
    // JIKA LOGIN GAGAL
    // ===========================
    $error = "Username atau password salah!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Telkom University</title>

    <!-- CSS -->
    <link rel="stylesheet" href="styles.css">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>

    <div class="background-shape"></div>

    <div class="login-card">

        <!-- Pesan Error -->
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- LOGO -->
        <div class="logo-section">
            <div class="logo-box">
                <svg viewBox="0 0 100 100" class="telkom-logo-svg">
                    <path fill="#FFFFFF" d="M50 0c-13.8 0-25 11.2-25 25v25c0 13.8 11.2 25 25 25h25V50H50V25h25V0H50zM50 50h25v25H50V50z"/>
                    <path fill="#EE2C2F" d="M50 50c-13.8 0-25 11.2-25 25s11.2 25 25 25 25-11.2 25-25-11.2-25-25-25zM50 75h25v25H50V75z"/>
                </svg>
            </div>

            <div class="text-logo">
                <span class="university-name">Telkom University</span>
                <span class="location">JAKARTA</span>
            </div>
        </div>

        <!-- FORM LOGIN -->
        <form action="" method="POST" class="login-form">

            <div class="input-group">
                <i class="fas fa-user icon"></i>
                <input type="text" name="username" placeholder="username" required>
            </div>

            <div class="input-group">
                <i class="fas fa-lock icon"></i>
                <input type="password" name="password" placeholder="password" required>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>

            <button type="submit" class="connect-button">Connect</button>

        </form>

        <p class="footer-link">
            Powered by Dukungan Teknologi Informasi Telkom University Jakarta
        </p>

    </div>

    <script>
        // TOGGLE PASSWORD
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const pass = this.previousElementSibling;
            if (pass.type === "password") {
                pass.type = "text";
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                pass.type = "password";
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    </script>

</body>
</html>
