<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: /sdm_dispersip/admin/pensiun/pensiun.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }

$stmt = mysqli_prepare($koneksi, "SELECT ps.*, p.nama_lengkap FROM pensiun ps JOIN pegawai p ON ps.pegawai_id=p.pegawai_id WHERE ps.pensiun_id=?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || mysqli_num_rows($res)===0) { mysqli_stmt_close($stmt); header("Location: /sdm_dispersip/admin/pensiun/pensiun.php?msg=".urlencode('Data tidak ditemukan')."&type=danger"); exit(); }
$ps = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pegawai_id = intval($_POST['pegawai_id'] ?? 0);
  $jenis = $_POST['jenis'] ?? 'BUP';
  $nomor_sk = trim($_POST['nomor_sk'] ?? '');
  $tanggal_sk = $_POST['tanggal_sk'] ?? '';
  $tmt = $_POST['tmt'] ?? '';
  $keterangan = trim($_POST['keterangan'] ?? '');
  $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
  if (!$pegawai_id || !$tmt || !preg_match($dateRegex, $tmt)) {
    $error = 'Harap isi pegawai dan TMT dengan benar.';
  } else {
    $filePathRel = $ps['file_sk'] ?? '';
    if (!empty($_FILES['file_sk']['name']) && isset($_FILES['file_sk']) && $_FILES['file_sk']['error'] === UPLOAD_ERR_OK) {
      $allowed = ['pdf','jpg','jpeg','png'];
      $ext = strtolower(pathinfo($_FILES['file_sk']['name'], PATHINFO_EXTENSION));
      if (in_array($ext, $allowed, true)) {
        $root = dirname(__DIR__, 2);
        $dirFS = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pensiun';
        if (!is_dir($dirFS)) { @mkdir($dirFS, 0777, true); }
        $basename = 'sk_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destFS = $dirFS . DIRECTORY_SEPARATOR . $basename;
        if (move_uploaded_file($_FILES['file_sk']['tmp_name'], $destFS)) { $filePathRel = 'uploads/pensiun/' . $basename; }
      }
    }
    $u = "UPDATE pensiun SET pegawai_id=?, jenis=?, nomor_sk=NULLIF(?, ''), tanggal_sk=NULLIF(?, ''), tmt=?, keterangan=NULLIF(?, ''), file_sk=NULLIF(?, '') WHERE pensiun_id=?";
    $ustmt = mysqli_prepare($koneksi, $u);
    mysqli_stmt_bind_param($ustmt, 'issssssi', $pegawai_id, $jenis, $nomor_sk, $tanggal_sk, $tmt, $keterangan, $filePathRel, $id);
    if (mysqli_stmt_execute($ustmt)) {
      mysqli_stmt_close($ustmt);
      $up = mysqli_prepare($koneksi, "UPDATE pegawai SET status_aktif='Pensiun', tmt_pensiun=? WHERE pegawai_id=?");
      mysqli_stmt_bind_param($up, 'si', $tmt, $pegawai_id);
      mysqli_stmt_execute($up);
      mysqli_stmt_close($up);
      header("Location: /sdm_dispersip/admin/pensiun/pensiun.php?msg=".urlencode('Data berhasil diubah')."&type=success"); exit();
    }
    mysqli_stmt_close($ustmt); $error = 'Gagal mengubah data.';
  }
}

$pegawaiRes = mysqli_query($koneksi, "SELECT pegawai_id, nama_lengkap FROM pegawai ORDER BY nama_lengkap");
$hasPegawai = $pegawaiRes && mysqli_num_rows($pegawaiRes) > 0;
$page_title = "Edit Pensiun";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Edit Pensiun</h4></div></div></div>
<?php if (!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<div class="card"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Form Edit Pensiun</h5><a href="admin/pensiun/pensiun.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a></div>
<div class="card-body"><form method="post" enctype="multipart/form-data"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Pegawai <span class="text-danger">*</span></label><select name="pegawai_id" class="form-select" required <?php echo !$hasPegawai ? 'disabled' : ''; ?>><option value="">- Pilih Pegawai -</option><?php if ($hasPegawai) { while ($p = mysqli_fetch_assoc($pegawaiRes)) { $sel = ($p['pegawai_id']==$ps['pegawai_id'])?'selected':''; echo '<option value="'.htmlspecialchars($p['pegawai_id']).'" '.$sel.'>'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?></select></div>
<div class="col-md-3"><label class="form-label">Jenis</label><select name="jenis" class="form-select"><option value="BUP" <?php echo ($ps['jenis']==='BUP')?'selected':''; ?>>BUP</option><option value="Dini" <?php echo ($ps['jenis']==='Dini')?'selected':''; ?>>Dini</option><option value="Lainnya" <?php echo ($ps['jenis']==='Lainnya')?'selected':''; ?>>Lainnya</option></select></div>
<div class="col-md-3"><label class="form-label">Nomor SK</label><input type="text" name="nomor_sk" class="form-control" placeholder="Nomor SK" value="<?php echo htmlspecialchars($ps['nomor_sk'] ?? ''); ?>"></div>
<div class="col-md-3"><label class="form-label">Tanggal SK</label><input type="date" name="tanggal_sk" class="form-control" value="<?php echo htmlspecialchars($ps['tanggal_sk'] ?? ''); ?>"></div>
<div class="col-md-3"><label class="form-label">TMT <span class="text-danger">*</span></label><input type="date" name="tmt" class="form-control" required value="<?php echo htmlspecialchars($ps['tmt']); ?>"></div>
<div class="col-md-12"><label class="form-label">Keterangan</label><textarea name="keterangan" class="form-control" rows="3" placeholder="Keterangan tambahan"><?php echo htmlspecialchars($ps['keterangan'] ?? ''); ?></textarea></div>
<div class="col-md-6"><label class="form-label">File SK (pdf/jpg/png)</label><input type="file" name="file_sk" class="form-control" accept=".pdf,.jpg,.jpeg,.png"><?php if (!empty($ps['file_sk'])) { echo '<small class="text-muted">File saat ini: <a href="/sdm_dispersip/'.htmlspecialchars($ps['file_sk']).'" target="_blank">'.htmlspecialchars(basename($ps['file_sk'])).'</a></small>'; } ?></div>
</div><div class="mt-3"><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Perubahan</button><a href="admin/pensiun/pensiun.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div></form></div></div>
</div></div></div>
<?php include '../../includes/footer.php'; ?>

