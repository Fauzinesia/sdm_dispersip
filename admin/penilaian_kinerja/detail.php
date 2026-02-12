<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'verifikator') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: /sdm_dispersip/admin/penilaian_kinerja/penilaian_kinerja.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }

$sql = "SELECT pk.*, p.nama_lengkap, u.username AS penilai_username
        FROM penilaian_kinerja pk
        JOIN pegawai p ON pk.pegawai_id=p.pegawai_id
        LEFT JOIN users u ON pk.penilai_user_id=u.user_id
        WHERE pk.penilaian_id=?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || mysqli_num_rows($res)===0) { mysqli_stmt_close($stmt); header("Location: /sdm_dispersip/admin/penilaian_kinerja/penilaian_kinerja.php?msg=".urlencode('Data tidak ditemukan')."&type=danger"); exit(); }
$d = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$page_title = "Detail Penilaian Kinerja";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Detail Penilaian Kinerja</h4></div></div></div>
<div class="card"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Informasi Penilaian</h5><div><?php if ($_SESSION['role'] === 'admin'): ?><a href="/sdm_dispersip/admin/penilaian_kinerja/edit.php?id=<?php echo $id; ?>" class="btn btn-warning"><i class="ti ti-pencil me-1"></i>Edit</a><?php endif; ?><a href="<?php echo ($_SESSION['role'] === 'verifikator') ? '/sdm_dispersip/verifikator/penilaian_kinerja.php' : '/sdm_dispersip/admin/penilaian_kinerja/penilaian_kinerja.php'; ?>" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a></div></div>
<div class="card-body"><div class="row">
<div class="col-md-6"><table class="table table-borderless">
<tr><th width="220">Nama Pegawai</th><td>: <?php echo htmlspecialchars($d['nama_lengkap']); ?></td></tr>
<tr><th>Periode</th><td>: <?php echo htmlspecialchars($d['periode']); ?></td></tr>
<tr><th>Kuantitas</th><td>: <?php echo htmlspecialchars(number_format((float)$d['nilai_kuantitas'],2)); ?></td></tr>
<tr><th>Kualitas</th><td>: <?php echo htmlspecialchars(number_format((float)$d['nilai_kualitas'],2)); ?></td></tr>
</table></div>
<div class="col-md-6"><table class="table table-borderless">
<tr><th width="220">Perilaku</th><td>: <?php echo htmlspecialchars(number_format((float)$d['nilai_perilaku'],2)); ?></td></tr>
<tr><th>Skor Akhir</th><td>: <?php echo htmlspecialchars(number_format((float)$d['skor_akhir'],2)); ?></td></tr>
<tr><th>Predikat</th><td>: <span class="badge bg-info"><?php echo htmlspecialchars(getPredikatKinerja((float)$d['skor_akhir'])); ?></span></td></tr>
<tr><th>Penilai</th><td>: <?php echo htmlspecialchars($d['penilai_username'] ?? '-'); ?></td></tr>
<tr><th>Dibuat Pada</th><td>: <?php echo $d['created_at'] ? date('d F Y, H:i', strtotime($d['created_at'])) : '-'; ?></td></tr>
</table></div>
</div>
<div class="row"><div class="col-12"><label class="form-label">Komentar</label><div class="border rounded p-3" style="min-height:80px;"><?php echo $d['komentar'] ? nl2br(htmlspecialchars($d['komentar'])) : '-'; ?></div></div></div>
</div></div>
</div></div></div>
<?php include '../../includes/footer.php'; ?>

