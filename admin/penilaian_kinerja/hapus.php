<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'verifikator') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$redirectBase = ($_SESSION['role'] === 'verifikator') ? '/sdm_dispersip/verifikator/penilaian_kinerja.php' : '/sdm_dispersip/admin/penilaian_kinerja/penilaian_kinerja.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: " . $redirectBase . "?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }

$del = mysqli_prepare($koneksi, "DELETE FROM penilaian_kinerja WHERE penilaian_id=?");
mysqli_stmt_bind_param($del, 'i', $id);
if (mysqli_stmt_execute($del)) { mysqli_stmt_close($del); header("Location: " . $redirectBase . "?msg=".urlencode('Data berhasil dihapus')."&type=success"); exit(); }
mysqli_stmt_close($del); header("Location: " . $redirectBase . "?msg=".urlencode('Gagal menghapus data')."&type=danger"); exit();
?>
