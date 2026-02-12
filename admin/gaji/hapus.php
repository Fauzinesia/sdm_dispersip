<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: /sdm_dispersip/admin/gaji/gaji.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }

$del = mysqli_prepare($koneksi, "DELETE FROM gaji WHERE gaji_id=?");
mysqli_stmt_bind_param($del, 'i', $id);
if (mysqli_stmt_execute($del)) { mysqli_stmt_close($del); header("Location: /sdm_dispersip/admin/gaji/gaji.php?msg=".urlencode('Data berhasil dihapus')."&type=success"); exit(); }
mysqli_stmt_close($del); header("Location: /sdm_dispersip/admin/gaji/gaji.php?msg=".urlencode('Gagal menghapus data')."&type=danger"); exit();
?>

