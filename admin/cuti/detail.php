<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'verifikator') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: admin/cuti/cuti.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }
$stmt = mysqli_prepare($koneksi, "SELECT c.*, p.nama_lengkap FROM cuti c JOIN pegawai p ON c.pegawai_id=p.pegawai_id WHERE cuti_id=?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$d = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);
$page_title = "Detail Cuti";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Detail Cuti</h4></div></div></div>
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <div>
                        <a href="admin/cuti/edit.php?id=<?php echo htmlspecialchars($id); ?>" class="btn btn-warning">Edit</a>
                        <a href="admin/cuti/cuti.php" class="btn btn-light">Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Nama Pegawai</strong><div><?php echo htmlspecialchars($d['nama_lengkap'] ?? '-'); ?></div></div>
                        <div class="col-md-4"><strong>Jenis Cuti</strong><div><?php echo htmlspecialchars($d['jenis_cuti'] ?? '-'); ?></div></div>
                        <div class="col-md-4"><strong>Status</strong><div><span class="badge bg-<?php $s=$d['status']??''; echo $s==='Disetujui'?'success':($s==='Ditolak'?'danger':'warning');?>"><?php echo htmlspecialchars($d['status'] ?? '-'); ?></span></div></div>
                        <div class="col-md-4"><strong>Tgl Mulai</strong><div><?php echo isset($d['tgl_mulai']) ? date('d/m/Y', strtotime($d['tgl_mulai'])) : '-'; ?></div></div>
                        <div class="col-md-4"><strong>Tgl Selesai</strong><div><?php echo isset($d['tgl_selesai']) ? date('d/m/Y', strtotime($d['tgl_selesai'])) : '-'; ?></div></div>
                        <div class="col-md-4"><strong>Lama Hari</strong><div><?php echo htmlspecialchars($d['lama_hari'] ?? '0'); ?> Hari <span class="text-muted small">(Hari Kerja)</span></div></div>
                        <div class="col-md-12"><strong>Alasan Pengajuan</strong><div><?php echo nl2br(htmlspecialchars($d['alasan'] ?? '-')); ?></div></div>
                        
                        <?php if($d['status'] === 'Disetujui' || $d['status'] === 'Ditolak'): ?>
                        <div class="col-md-12"><hr></div>
                        <div class="col-md-4"><strong>Diverifikasi Oleh</strong><div><?php echo htmlspecialchars($d['verifikator_nama'] ?? '-'); ?></div></div>
                        <div class="col-md-4"><strong>Tanggal Verifikasi</strong><div><?php echo isset($d['verified_at']) ? date('d/m/Y H:i', strtotime($d['verified_at'])) : '-'; ?></div></div>
                        
                        <?php if($d['status'] === 'Disetujui'): ?>
                        <div class="col-md-12"><strong>Disposisi / Catatan</strong><div><?php echo nl2br(htmlspecialchars($d['disposisi'] ?? '-')); ?></div></div>
                        <?php endif; ?>

                        <?php if($d['status'] === 'Ditolak'): ?>
                        <div class="col-md-12"><strong>Alasan Penolakan</strong><div class="text-danger"><?php echo nl2br(htmlspecialchars($d['alasan_ditolak'] ?? '-')); ?></div></div>
                        <?php endif; ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>

