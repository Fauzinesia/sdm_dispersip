<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

// Get jabatan_id
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: /sdm_dispersip/admin/jabatan/jabatan.php?msg=" . urlencode('ID tidak valid') . "&type=danger");
    exit();
}

// Check if jabatan is being used
$check_query = "SELECT COUNT(*) as count FROM pegawai WHERE jabatan_id = ?";
$stmt = mysqli_prepare($koneksi, $check_query);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$check_result = mysqli_stmt_get_result($stmt);
$check = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($stmt);

if ($check['count'] > 0) {
    header("Location: /sdm_dispersip/admin/jabatan/jabatan.php?msg=" . urlencode('Tidak dapat menghapus! Jabatan masih digunakan oleh ' . $check['count'] . ' pegawai') . "&type=danger");
    exit();
}

// Delete jabatan
$delete_query = "DELETE FROM master_jabatan WHERE jabatan_id = ?";
$stmt = mysqli_prepare($koneksi, $delete_query);
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: /sdm_dispersip/admin/jabatan/jabatan.php?msg=" . urlencode('Data berhasil dihapus') . "&type=success");
    exit();
} else {
    mysqli_stmt_close($stmt);
    header("Location: /sdm_dispersip/admin/jabatan/jabatan.php?msg=" . urlencode('Gagal menghapus data') . "&type=danger");
    exit();
}
?>
