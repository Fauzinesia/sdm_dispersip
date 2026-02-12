<?php
session_start();

// Cek apakah user sudah login
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($role === 'verifikator') {
        header("Location: verifikator/dashboard.php");
    } else {
        header("Location: pegawai/dashboard.php");
    }
    exit();
} else {
    // Jika belum login, redirect ke halaman login
    header("Location: login.php");
}
exit();
?>
