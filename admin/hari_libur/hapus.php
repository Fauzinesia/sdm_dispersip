<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { 
    header("Location: /sdm_dispersip/admin/hari_libur/hari_libur.php?msg=" . urlencode('ID tidak valid') . "&type=danger"); 
    exit(); 
}

$stmt = mysqli_prepare($koneksi, "DELETE FROM hari_libur WHERE libur_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    $msg = 'Data berhasil dihapus';
    $type = 'success';
} else {
    $msg = 'Gagal menghapus data';
    $type = 'danger';
}

mysqli_stmt_close($stmt);
header("Location: /sdm_dispersip/admin/hari_libur/hari_libur.php?msg=" . urlencode($msg) . "&type=$type");
exit();
?>
