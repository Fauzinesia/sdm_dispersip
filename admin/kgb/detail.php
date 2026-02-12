<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'verifikator') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: /sdm_dispersip/admin/kgb/kgb.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }

$sql = "SELECT k.*, p.nama_lengkap FROM kgb k JOIN pegawai p ON k.pegawai_id=p.pegawai_id WHERE k.kgb_id=?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || mysqli_num_rows($res)===0) { mysqli_stmt_close($stmt); header("Location: /sdm_dispersip/admin/kgb/kgb.php?msg=".urlencode('Data tidak ditemukan')."&type=danger"); exit(); }
$kgb = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$page_title = "Detail KGB";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Detail KGB</h4></div></div></div>
<div class="card"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Informasi KGB</h5><div><a href="/sdm_dispersip/admin/kgb/edit.php?id=<?php echo $id; ?>" class="btn btn-warning"><i class="ti ti-pencil me-1"></i>Edit</a><a href="/sdm_dispersip/admin/kgb/kgb.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a></div></div>
<div class="card-body">
<div class="row">
<div class="col-md-6"><table class="table table-borderless">
<tr><th width="220">Nama Pegawai</th><td>: <?php echo htmlspecialchars($kgb['nama_lengkap']); ?></td></tr>
<tr><th>Nomor SK</th><td>: <?php echo htmlspecialchars($kgb['nomor_sk'] ?? '-'); ?></td></tr>
<tr><th>Tanggal SK</th><td>: <?php echo $kgb['tanggal_sk'] ? date('d F Y', strtotime($kgb['tanggal_sk'])) : '-'; ?></td></tr>
<tr><th>TMT Mulai</th><td>: <?php echo $kgb['tmt_mulai'] ? date('d F Y', strtotime($kgb['tmt_mulai'])) : '-'; ?></td></tr>
</table></div>
<div class="col-md-6"><table class="table table-borderless">
<tr><th width="220">Gaji Lama</th><td>: <?php echo is_null($kgb['gaji_lama']) ? '-' : number_format((float)$kgb['gaji_lama'],2); ?></td></tr>
<tr><th>Gaji Baru</th><td>: <?php echo is_null($kgb['gaji_baru']) ? '-' : number_format((float)$kgb['gaji_baru'],2); ?></td></tr>
<tr><th>Jadwal KGB Berikut</th><td>: <?php echo $kgb['jadwal_kgb_berikut'] ? date('d F Y', strtotime($kgb['jadwal_kgb_berikut'])) : '-'; ?></td></tr>
<tr><th>File SK</th><td>: <?php if (!empty($kgb['file_sk'])) { echo '<a href="/sdm_dispersip/'.htmlspecialchars($kgb['file_sk']).'" target="_blank" class="btn btn-sm btn-info"><i class="ti ti-file-text"></i> Lihat</a>'; } else { echo '-'; } ?></td></tr>
<tr><th>Status</th><td>: <span class="badge bg-<?php echo ($kgb['status']==='Disahkan')?'success':'secondary'; ?>"><?php echo htmlspecialchars($kgb['status']); ?></span></td></tr>
<tr><th>Dibuat Pada</th><td>: <?php echo $kgb['created_at'] ? date('d F Y, H:i', strtotime($kgb['created_at'])) : '-'; ?></td></tr>
</table></div>
</div>
</div></div>
</div></div></div>
<?php include '../../includes/footer.php'; ?>

