<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: /sdm_dispersip/admin/users/users.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }

$check = mysqli_prepare($koneksi, "SELECT COUNT(*) AS c FROM pegawai WHERE user_id=?");
mysqli_stmt_bind_param($check, 'i', $id);
mysqli_stmt_execute($check);
$res = mysqli_stmt_get_result($check);
$refCount = $res ? intval(mysqli_fetch_assoc($res)['c'] ?? 0) : 0;
mysqli_stmt_close($check);

if ($refCount > 0) {
  header("Location: /sdm_dispersip/admin/users/users.php?msg=".urlencode('Tidak dapat menghapus! User terhubung dengan data pegawai')."&type=danger");
  exit();
}

$del = mysqli_prepare($koneksi, "DELETE FROM users WHERE user_id=?");
mysqli_stmt_bind_param($del, 'i', $id);
if (mysqli_stmt_execute($del)) { mysqli_stmt_close($del); header("Location: /sdm_dispersip/admin/users/users.php?msg=".urlencode('User berhasil dihapus')."&type=success"); exit(); }
mysqli_stmt_close($del); header("Location: /sdm_dispersip/admin/users/users.php?msg=".urlencode('Gagal menghapus user')."&type=danger"); exit();
?>

