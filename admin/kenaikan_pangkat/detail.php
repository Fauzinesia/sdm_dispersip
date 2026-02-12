<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'verifikator') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
  header("Location: /sdm_dispersip/admin/kenaikan_pangkat/kenaikan_pangkat.php?msg=".urlencode('ID tidak valid')."&type=danger");
  exit();
}

$sql = "SELECT kp.*, p.nama_lengkap, mp1.nama_pangkat AS pangkat_lama, mp2.nama_pangkat AS pangkat_baru
        FROM kenaikan_pangkat kp
        JOIN pegawai p ON kp.pegawai_id = p.pegawai_id
        LEFT JOIN master_pangkat mp1 ON kp.pangkat_lama_id = mp1.pangkat_id
        LEFT JOIN master_pangkat mp2 ON kp.pangkat_baru_id = mp2.pangkat_id
        WHERE kp.kp_id = ?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || mysqli_num_rows($res) === 0) {
  mysqli_stmt_close($stmt);
  header("Location: /sdm_dispersip/admin/kenaikan_pangkat/kenaikan_pangkat.php?msg=".urlencode('Data tidak ditemukan')."&type=danger");
  exit();
}
$kp = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$page_title = "Detail Kenaikan Pangkat";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container">
  <div class="page-content">
    <div class="container-xxl">
      <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Detail Kenaikan Pangkat</h4></div></div></div>
      <div class="card">
        <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Informasi Kenaikan Pangkat</h5>
          <div>
            <a href="/sdm_dispersip/admin/kenaikan_pangkat/edit.php?id=<?php echo $id; ?>" class="btn btn-warning"><i class="ti ti-pencil me-1"></i>Edit</a>
            <a href="/sdm_dispersip/admin/kenaikan_pangkat/kenaikan_pangkat.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <table class="table table-borderless">
                <tr><th width="220">Nama Pegawai</th><td>: <?php echo htmlspecialchars($kp['nama_lengkap']); ?></td></tr>
                <tr><th>Pangkat Lama</th><td>: <?php echo htmlspecialchars($kp['pangkat_lama'] ?? '-'); ?></td></tr>
                <tr><th>Pangkat Baru</th><td>: <?php echo htmlspecialchars($kp['pangkat_baru']); ?></td></tr>
                <tr><th>Nomor SK</th><td>: <?php echo htmlspecialchars($kp['nomor_sk'] ?? '-'); ?></td></tr>
              </table>
            </div>
            <div class="col-md-6">
              <table class="table table-borderless">
                <tr><th width="220">Tanggal SK</th><td>: <?php echo $kp['tanggal_sk'] ? date('d F Y', strtotime($kp['tanggal_sk'])) : '-'; ?></td></tr>
                <tr><th>TMT</th><td>: <?php echo $kp['tmt'] ? date('d F Y', strtotime($kp['tmt'])) : '-'; ?></td></tr>
                <tr><th>File SK</th><td>: <?php if (!empty($kp['file_sk'])) { echo '<a href="/sdm_dispersip/'.htmlspecialchars($kp['file_sk']).'" target="_blank" class="btn btn-sm btn-info"><i class="ti ti-file-text"></i> Lihat</a>'; } else { echo '-'; } ?></td></tr>
                <tr><th>Dibuat Pada</th><td>: <?php echo $kp['created_at'] ? date('d F Y, H:i', strtotime($kp['created_at'])) : '-'; ?></td></tr>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include '../../includes/footer.php'; ?>

