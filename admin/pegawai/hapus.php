<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: /sdm_dispersip/admin/pegawai/pegawai.php?msg=" . urlencode('ID tidak valid') . "&type=danger");
    exit();
}

// Ambil user_id dari pegawai sebelum dihapus
$stmt = mysqli_prepare($koneksi, "SELECT user_id FROM pegawai WHERE pegawai_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user_id = 0;
if ($row = mysqli_fetch_assoc($res)) {
    $user_id = $row['user_id'];
}
mysqli_stmt_close($stmt);

// Mulai Transaksi
mysqli_begin_transaction($koneksi);

try {
    // Daftar tabel yang memiliki relasi foreign key ke pegawai_id
    // Note: Urutan tidak terlalu penting kecuali ada relasi antar child, tapi sebaiknya hapus child dulu
    $tables = [
        'pensiun', 
        'cuti', 
        'gaji', 
        'kgb', 
        'penilaian_kinerja', 
        'kenaikan_pangkat', 
        'arsip_dokumen', 
        'absensi',
        'riwayat_jabatan'
    ];

    foreach ($tables as $table) {
        // Coba hapus data di tabel terkait
        $sql = "DELETE FROM $table WHERE pegawai_id = ?";
        $del = mysqli_prepare($koneksi, $sql);
        if ($del) {
            mysqli_stmt_bind_param($del, 'i', $id);
            mysqli_stmt_execute($del);
            mysqli_stmt_close($del);
        }
    }

    // Hapus data pegawai
    $delPegawai = mysqli_prepare($koneksi, "DELETE FROM pegawai WHERE pegawai_id = ?");
    mysqli_stmt_bind_param($delPegawai, 'i', $id);
    mysqli_stmt_execute($delPegawai);
    mysqli_stmt_close($delPegawai);

    // Hapus user terkait jika ada
    if ($user_id > 0) {
        $delUser = mysqli_prepare($koneksi, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($delUser, 'i', $user_id);
        mysqli_stmt_execute($delUser);
        mysqli_stmt_close($delUser);
    }

    mysqli_commit($koneksi);
    header("Location: /sdm_dispersip/admin/pegawai/pegawai.php?msg=" . urlencode('Data pegawai dan seluruh data terkait berhasil dihapus') . "&type=success");

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    // Log error jika perlu
    header("Location: /sdm_dispersip/admin/pegawai/pegawai.php?msg=" . urlencode('Gagal menghapus data: ' . $e->getMessage()) . "&type=danger");
}
?>
