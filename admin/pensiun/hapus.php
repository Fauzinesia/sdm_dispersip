<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: /sdm_dispersip/admin/pensiun/pensiun.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }

$get = mysqli_prepare($koneksi, "SELECT file_sk FROM pensiun WHERE pensiun_id=?");
mysqli_stmt_bind_param($get, 'i', $id);
mysqli_stmt_execute($get);
$res = mysqli_stmt_get_result($get);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($get);

if (!$row) { header("Location: /sdm_dispersip/admin/pensiun/pensiun.php?msg=".urlencode('Data tidak ditemukan')."&type=danger"); exit(); }

if (!empty($row['file_sk'])) {
  $fs = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $row['file_sk']);
  if (is_file($fs)) { @unlink($fs); }
}

$del = mysqli_prepare($koneksi, "DELETE FROM pensiun WHERE pensiun_id=?");
mysqli_stmt_bind_param($del, 'i', $id);
if (mysqli_stmt_execute($del)) { mysqli_stmt_close($del); header("Location: /sdm_dispersip/admin/pensiun/pensiun.php?msg=".urlencode('Data berhasil dihapus')."&type=success"); exit(); }
mysqli_stmt_close($del); header("Location: /sdm_dispersip/admin/pensiun/pensiun.php?msg=".urlencode('Gagal menghapus data')."&type=danger"); exit();
?>

