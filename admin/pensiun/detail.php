<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: /sdm_dispersip/admin/pensiun/pensiun.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }

$sql = "SELECT ps.*, p.nama_lengkap FROM pensiun ps JOIN pegawai p ON ps.pegawai_id=p.pegawai_id WHERE ps.pensiun_id=?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || mysqli_num_rows($res)===0) { mysqli_stmt_close($stmt); header("Location: /sdm_dispersip/admin/pensiun/pensiun.php?msg=".urlencode('Data tidak ditemukan')."&type=danger"); exit(); }
$d = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$page_title = "Detail Pensiun";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Detail Pensiun</h4></div></div></div>
<div class="card"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Informasi Pensiun</h5><div><a href="/sdm_dispersip/admin/pensiun/edit.php?id=<?php echo $id; ?>" class="btn btn-warning"><i class="ti ti-pencil me-1"></i>Edit</a><a href="/sdm_dispersip/admin/pensiun/pensiun.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a></div></div>
<div class="card-body"><div class="row">
<div class="col-md-6"><table class="table table-borderless">
<tr><th width="220">Nama Pegawai</th><td>: <?php echo htmlspecialchars($d['nama_lengkap']); ?></td></tr>
<tr><th>Jenis</th><td>: <?php echo htmlspecialchars($d['jenis']); ?></td></tr>
<tr><th>Nomor SK</th><td>: <?php echo htmlspecialchars($d['nomor_sk'] ?? '-'); ?></td></tr>
<tr><th>Tanggal SK</th><td>: <?php echo $d['tanggal_sk'] ? date('d F Y', strtotime($d['tanggal_sk'])) : '-'; ?></td></tr>
</table></div>
<div class="col-md-6"><table class="table table-borderless">
<tr><th width="220">TMT</th><td>: <?php echo $d['tmt'] ? date('d F Y', strtotime($d['tmt'])) : '-'; ?></td></tr>
<tr><th>File SK</th><td>: <?php if (!empty($d['file_sk'])) { echo '<a href="/sdm_dispersip/'.htmlspecialchars($d['file_sk']).'" target="_blank" class="btn btn-sm btn-info"><i class="ti ti-file-text"></i> Lihat</a>'; } else { echo '-'; } ?></td></tr>
<tr><th>Dibuat Pada</th><td>: <?php echo $d['created_at'] ? date('d F Y, H:i', strtotime($d['created_at'])) : '-'; ?></td></tr>
</table></div>
</div>
<div class="row"><div class="col-12"><label class="form-label">Keterangan</label><div class="border rounded p-3" style="min-height:80px;"><?php echo $d['keterangan'] ? nl2br(htmlspecialchars($d['keterangan'])) : '-'; ?></div></div></div>
</div></div>
</div></div></div>
<?php include '../../includes/footer.php'; ?>

