<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: /sdm_dispersip/admin/cuti/cuti.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }
$stmt = mysqli_prepare($koneksi, "DELETE FROM cuti WHERE cuti_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: /sdm_dispersip/admin/cuti/cuti.php?msg=".urlencode('Data berhasil dihapus')."&type=success");
} else {
    mysqli_stmt_close($stmt);
    header("Location: /sdm_dispersip/admin/cuti/cuti.php?msg=".urlencode('Gagal menghapus data')."&type=danger");
}
exit();

