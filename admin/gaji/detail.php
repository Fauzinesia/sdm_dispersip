<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: /sdm_dispersip/admin/gaji/gaji.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }

$sql = "SELECT g.*, p.nama_lengkap FROM gaji g JOIN pegawai p ON g.pegawai_id=p.pegawai_id WHERE g.gaji_id=?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || mysqli_num_rows($res)===0) { mysqli_stmt_close($stmt); header("Location: /sdm_dispersip/admin/gaji/gaji.php?msg=".urlencode('Data tidak ditemukan')."&type=danger"); exit(); }
$g = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$page_title = "Detail Gaji";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Detail Gaji</h4></div></div></div>
<div class="card"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Informasi Gaji</h5><div><a href="/sdm_dispersip/admin/gaji/edit.php?id=<?php echo $id; ?>" class="btn btn-warning"><i class="ti ti-pencil me-1"></i>Edit</a><a href="/sdm_dispersip/admin/gaji/gaji.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a></div></div>
<div class="card-body"><div class="row">
<div class="col-md-6"><table class="table table-borderless">
<tr><th width="220">Nama Pegawai</th><td>: <?php echo htmlspecialchars($g['nama_lengkap']); ?></td></tr>
<tr><th>Periode</th><td>: <?php echo htmlspecialchars($g['periode']); ?></td></tr>
<tr><th>Gaji Pokok</th><td>: <?php echo number_format((float)$g['gaji_pokok'],2); ?></td></tr>
<tr><th>Tunjangan</th><td>: <?php echo number_format((float)$g['tunjangan'],2); ?></td></tr>
</table></div>
<div class="col-md-6"><table class="table table-borderless">
<tr><th width="220">Potongan</th><td>: <?php echo number_format((float)$g['potongan'],2); ?></td></tr>
<tr><th>Total Gaji</th><td>: <?php echo number_format((float)$g['total_gaji'],2); ?></td></tr>
<tr><th>Dibuat</th><td>: <?php echo $g['created_at'] ? date('d F Y, H:i', strtotime($g['created_at'])) : '-'; ?></td></tr>
</table></div>
</div>
<div class="row"><div class="col-12"><label class="form-label">Keterangan</label><div class="border rounded p-3" style="min-height:80px;"><?php echo $g['keterangan'] ? nl2br(htmlspecialchars($g['keterangan'])) : '-'; ?></div></div></div>
</div></div>
</div></div></div>
<?php include '../../includes/footer.php'; ?>

