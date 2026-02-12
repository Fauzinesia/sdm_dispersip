<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
  header("Location: /sdm_dispersip/admin/kenaikan_pangkat/kenaikan_pangkat.php?msg=".urlencode('ID tidak valid')."&type=danger");
  exit();
}

$q = "SELECT kp.*, p.nama_lengkap FROM kenaikan_pangkat kp JOIN pegawai p ON kp.pegawai_id=p.pegawai_id WHERE kp.kp_id=?";
$stmt = mysqli_prepare($koneksi, $q);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pegawai_id = intval($_POST['pegawai_id'] ?? 0);
  $pangkat_lama_id = intval($_POST['pangkat_lama_id'] ?? 0);
  $pangkat_baru_id = intval($_POST['pangkat_baru_id'] ?? 0);
  $nomor_sk = trim($_POST['nomor_sk'] ?? '');
  $tanggal_sk = $_POST['tanggal_sk'] ?? '';
  $tmt = $_POST['tmt'] ?? '';

  $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
  if (!$pegawai_id || !$pangkat_baru_id || !$tmt || !preg_match($dateRegex, $tmt)) {
    $error = 'Harap isi pegawai, pangkat baru, dan TMT dengan benar.';
  } else {
    $filePathRel = $kp['file_sk'] ?? '';
    if (!empty($_FILES['file_sk']['name']) && isset($_FILES['file_sk']) && $_FILES['file_sk']['error'] === UPLOAD_ERR_OK) {
      $allowed = ['pdf','jpg','jpeg','png'];
      $ext = strtolower(pathinfo($_FILES['file_sk']['name'], PATHINFO_EXTENSION));
      if (in_array($ext, $allowed, true)) {
        $root = dirname(__DIR__, 2);
        $dirFS = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'kp';
        if (!is_dir($dirFS)) { @mkdir($dirFS, 0777, true); }
        $basename = 'sk_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destFS = $dirFS . DIRECTORY_SEPARATOR . $basename;
        if (move_uploaded_file($_FILES['file_sk']['tmp_name'], $destFS)) {
          $filePathRel = 'uploads/kp/' . $basename;
        }
      }
    }

    $u = "UPDATE kenaikan_pangkat SET pegawai_id=?, pangkat_lama_id=NULLIF(?,0), pangkat_baru_id=?, nomor_sk=NULLIF(?,''), tanggal_sk=NULLIF(?,''), tmt=?, file_sk=NULLIF(?, '') WHERE kp_id=?";
    $ustmt = mysqli_prepare($koneksi, $u);
    mysqli_stmt_bind_param($ustmt, 'iiissssi', $pegawai_id, $pangkat_lama_id, $pangkat_baru_id, $nomor_sk, $tanggal_sk, $tmt, $filePathRel, $id);
    if (mysqli_stmt_execute($ustmt)) {
      mysqli_stmt_close($ustmt);
      header("Location: /sdm_dispersip/admin/kenaikan_pangkat/kenaikan_pangkat.php?msg=".urlencode('Data berhasil diubah')."&type=success");
      exit();
    }
    mysqli_stmt_close($ustmt);
    $error = 'Gagal mengubah data.';
  }
}

$pegawaiRes = mysqli_query($koneksi, "SELECT pegawai_id, nama_lengkap FROM pegawai WHERE status_aktif='Aktif' ORDER BY nama_lengkap");
$hasPegawai = $pegawaiRes && mysqli_num_rows($pegawaiRes) > 0;
$pangkatRes = mysqli_query($koneksi, "SELECT pangkat_id, nama_pangkat FROM master_pangkat ORDER BY nama_pangkat");
$hasPangkat = $pangkatRes && mysqli_num_rows($pangkatRes) > 0;

$page_title = "Edit Kenaikan Pangkat";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container">
  <div class="page-content">
    <div class="container-xxl">
      <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Edit Kenaikan Pangkat</h4></div></div></div>
      <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
      <?php endif; ?>
      <div class="card">
        <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Form Edit Kenaikan Pangkat</h5>
          <a href="/sdm_dispersip/admin/kenaikan_pangkat/kenaikan_pangkat.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
        </div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Pegawai <span class="text-danger">*</span></label>
                <select name="pegawai_id" class="form-select" required <?php echo !$hasPegawai ? 'disabled' : ''; ?>>
                  <option value="">- Pilih Pegawai -</option>
                  <?php if ($hasPegawai) { while ($p = mysqli_fetch_assoc($pegawaiRes)) { $sel = ($p['pegawai_id']==$kp['pegawai_id'])?'selected':''; echo '<option value="'.htmlspecialchars($p['pegawai_id']).'" '.$sel.'>'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Pangkat Lama</label>
                <select name="pangkat_lama_id" class="form-select" <?php echo !$hasPangkat ? 'disabled' : ''; ?>>
                  <option value="0">- Pilih Pangkat Lama -</option>
                  <?php if ($hasPangkat) { mysqli_data_seek($pangkatRes, 0); while ($pg = mysqli_fetch_assoc($pangkatRes)) { $sel = ($pg['pangkat_id']==($kp['pangkat_lama_id'] ?? 0))?'selected':''; echo '<option value="'.htmlspecialchars($pg['pangkat_id']).'" '.$sel.'>'.htmlspecialchars($pg['nama_pangkat']).'</option>'; } } ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Pangkat Baru <span class="text-danger">*</span></label>
                <select name="pangkat_baru_id" class="form-select" required <?php echo !$hasPangkat ? 'disabled' : ''; ?>>
                  <option value="">- Pilih Pangkat Baru -</option>
                  <?php if ($hasPangkat) { mysqli_data_seek($pangkatRes, 0); while ($pg2 = mysqli_fetch_assoc($pangkatRes)) { $sel = ($pg2['pangkat_id']==$kp['pangkat_baru_id'])?'selected':''; echo '<option value="'.htmlspecialchars($pg2['pangkat_id']).'" '.$sel.'>'.htmlspecialchars($pg2['nama_pangkat']).'</option>'; } } ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Nomor SK</label>
                <input type="text" name="nomor_sk" class="form-control" placeholder="Nomor SK" value="<?php echo htmlspecialchars($kp['nomor_sk'] ?? ''); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Tanggal SK</label>
                <input type="date" name="tanggal_sk" class="form-control" value="<?php echo htmlspecialchars($kp['tanggal_sk'] ?? ''); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">TMT <span class="text-danger">*</span></label>
                <input type="date" name="tmt" class="form-control" required value="<?php echo htmlspecialchars($kp['tmt']); ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">File SK (pdf/jpg/png)</label>
                <input type="file" name="file_sk" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                <?php if (!empty($kp['file_sk'])) { echo '<small class="text-muted">File saat ini: <a href="/sdm_dispersip/'.htmlspecialchars($kp['file_sk']).'" target="_blank">'.htmlspecialchars(basename($kp['file_sk'])).'</a></small>'; } ?>
              </div>
            </div>
            <div class="mt-3">
              <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Perubahan</button>
              <a href="/sdm_dispersip/admin/kenaikan_pangkat/kenaikan_pangkat.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include '../../includes/footer.php'; ?>

