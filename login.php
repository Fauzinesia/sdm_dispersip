<?php
session_start();

// Jika sudah login, redirect ke index (akan diarahkan ke dashboard sesuai role)
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Proses login
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'config/koneksi.php';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = mysqli_prepare($koneksi, "SELECT u.*, p.nama_lengkap, p.pegawai_id FROM users u LEFT JOIN pegawai p ON u.user_id = p.user_id WHERE u.username = ? AND u.status = 'Aktif'");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $stored = $user['password'];
        $ok = false;
        if (is_string($stored) && password_verify($password, $stored)) {
            $ok = true;
        } elseif ($stored === $password) {
            // Legacy plaintext password, migrate to hashed
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $up = mysqli_prepare($koneksi, "UPDATE users SET password=? WHERE user_id=?");
            mysqli_stmt_bind_param($up, 'si', $newHash, $user['user_id']);
            mysqli_stmt_execute($up);
            mysqli_stmt_close($up);
            $ok = true;
        }
        mysqli_stmt_close($stmt);
        if ($ok) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['pegawai_id'] = $user['pegawai_id'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Username atau password salah!";
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Login - Aplikasi SDM Dispersip Banjarmasin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Aplikasi Manajemen Kepegawaian dan SDM" name="description" />
    <meta content="Dispersip Banjarmasin" name="author" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <!-- Theme Config Js -->
    <script src="assets/js/config.js"></script>

    <!-- Vendor css -->
    <link href="assets/css/vendor.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style" />

    <!-- Icons css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />

    <style>
        .auth-bg {
            background-image: url('assets/images/bg.jpg') !important;
            background-size: cover;
            background-position: center;
        }
    </style>
</head>

<body>

    <div class="auth-bg d-flex min-vh-100">
        <div class="row g-0 justify-content-center w-100 m-xxl-5 px-xxl-4 m-3">
            <div class="col-xxl-3 col-lg-5 col-md-6">
                <a href="index.php" class="auth-brand d-flex justify-content-center mb-3">
                    <img src="assets/images/logo_sdm.png" alt="logo SDM" height="200">
                </a>
                <p class="fw-semibold mb-4 text-center text-muted fs-15">Sistem Manajemen Kepegawaian</p>

                <div class="card overflow-hidden text-center p-xxl-4 p-3 mb-0">

                    <h4 class="fw-semibold mb-3 fs-18">Login ke Akun Anda</h4>

                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ri-error-warning-line me-1"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="text-start mb-3">
                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control"
                                placeholder="Masukkan username" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="Masukkan password" required>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="checkbox-signin">
                                <label class="form-check-label" for="checkbox-signin">Ingat Saya</label>
                            </div>

                            <a href="auth-recoverpw.html" class="text-muted border-bottom border-dashed">Lupa
                                Password?</a>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-primary fw-semibold" type="submit">Login</button>
                        </div>
                    </form>

                    <p class="text-muted fs-14 mb-0">
                        <small>Dinas Perpustakaan dan Kearsipan Kota Banjarmasin</small>
                    </p>

                </div>
                <p class="mt-4 text-center mb-0">
                    <script>document.write(new Date().getFullYear())</script> © Dispersip Banjarmasin
                </p>
            </div>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>

    <!-- App js -->
    <script src="assets/js/app.js"></script>

</body>

</html>
