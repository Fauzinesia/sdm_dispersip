<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$page_title = "Detail Pegawai";
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: /sdm_dispersip/admin/pegawai/pegawai.php?msg=".urlencode('ID tidak valid').'&type=danger'); exit(); }
$stmt = mysqli_prepare($koneksi, "SELECT p.*, mj.nama_jabatan, mp.nama_pangkat FROM pegawai p LEFT JOIN master_jabatan mj ON p.jabatan_id=mj.jabatan_id LEFT JOIN master_pangkat mp ON p.pangkat_id=mp.pangkat_id WHERE p.pegawai_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$d = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Detail Pegawai</h4></div></div></div>
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <div>
                        <a href="admin/pegawai/edit.php?id=<?php echo htmlspecialchars($id); ?>" class="btn btn-warning">Edit</a>
                        <a href="admin/pegawai/pegawai.php" class="btn btn-light">Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>NIK</strong><div><?php echo htmlspecialchars($d['nik'] ?? '-'); ?></div></div>
                        <div class="col-md-4"><strong>NIP</strong><div><?php echo htmlspecialchars($d['nip'] ?? '-'); ?></div></div>
                        <div class="col-md-4"><strong>Nama Lengkap</strong><div><?php echo htmlspecialchars($d['nama_lengkap'] ?? '-'); ?></div></div>
                        <div class="col-md-4"><strong>JK</strong><div><?php echo (isset($d['jk']) && $d['jk']==='L') ? 'Laki-laki' : ((isset($d['jk']) && $d['jk']==='P') ? 'Perempuan' : '-'); ?></div></div>
                        <div class="col-md-4"><strong>Status</strong><div><?php echo htmlspecialchars($d['status_kepegawaian'] ?? '-'); ?></div></div>
                        <div class="col-md-4"><strong>Jabatan</strong><div><?php echo htmlspecialchars($d['nama_jabatan'] ?? '-'); ?></div></div>
                        <div class="col-md-4"><strong>Pangkat</strong><div><?php echo htmlspecialchars($d['nama_pangkat'] ?? '-'); ?></div></div>
                        <div class="col-md-4"><strong>Status Aktif</strong><div><span class="badge bg-<?php echo (($d['status_aktif'] ?? '') === 'Aktif') ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars($d['status_aktif'] ?? '-'); ?></span></div></div>
                        <div class="col-md-4"><strong>Tgl Mulai Kerja</strong><div><?php echo isset($d['tgl_mulai_kerja']) ? date('d/m/Y', strtotime($d['tgl_mulai_kerja'])) : '-'; ?></div></div>
                        <div class="col-md-12"><strong>Alamat</strong><div><?php echo nl2br(htmlspecialchars($d['alamat'] ?? '-')); ?></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
