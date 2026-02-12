<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

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
    $filePathRel = '';
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
    $stmt = mysqli_prepare($koneksi, "INSERT INTO pensiun (pegawai_id, jenis, nomor_sk, tanggal_sk, tmt, keterangan, file_sk) VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?, ''), NULLIF(?, ''))");
    mysqli_stmt_bind_param($stmt, 'issssss', $pegawai_id, $jenis, $nomor_sk, $tanggal_sk, $tmt, $keterangan, $filePathRel);
    if (mysqli_stmt_execute($stmt)) {
      mysqli_stmt_close($stmt);
      $up = mysqli_prepare($koneksi, "UPDATE pegawai SET status_aktif='Pensiun', tmt_pensiun=? WHERE pegawai_id=?");
      mysqli_stmt_bind_param($up, 'si', $tmt, $pegawai_id);
      mysqli_stmt_execute($up);
      mysqli_stmt_close($up);
      header("Location: pensiun.php?msg=".urlencode('Data berhasil ditambah')."&type=success"); exit();
    }
    mysqli_stmt_close($stmt); $error = 'Gagal menambah data.';
  }
}

$dateRegexFilter = '/^\d{4}-\d{2}-\d{2}$/';
$tmtFilter = $_GET['tmt'] ?? '';
if ($tmtFilter && preg_match($dateRegexFilter, $tmtFilter)) {
  $stmtFilter = mysqli_prepare($koneksi, "SELECT pegawai_id, nama_lengkap FROM pegawai WHERE tmt_pensiun = ? ORDER BY nama_lengkap");
  mysqli_stmt_bind_param($stmtFilter, 's', $tmtFilter);
  mysqli_stmt_execute($stmtFilter);
  $pegawaiRes = mysqli_stmt_get_result($stmtFilter);
} else {
  $pegawaiRes = false;
}
$hasPegawai = $pegawaiRes && mysqli_num_rows($pegawaiRes) > 0;
$page_title = "Tambah Pensiun";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Tambah Pensiun</h4></div></div></div>
<?php if (!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<div class="card"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Form Tambah Pensiun</h5><a href="admin/pensiun/pensiun.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a></div>
<div class="card-body">
<form method="get" action="admin/pensiun/tambah.php" class="row g-3 mb-3">
  <div class="col-md-3"><label class="form-label">TMT Pensiun (Filter Pegawai)</label><input type="date" name="tmt" value="<?php echo htmlspecialchars($tmtFilter); ?>" class="form-control"></div>
  <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-light"><i class="ti ti-filter me-1"></i>Filter</button></div>
</form>
<form method="post" enctype="multipart/form-data"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Pegawai <span class="text-danger">*</span></label><select name="pegawai_id" class="form-select" required <?php echo !$hasPegawai ? 'disabled' : ''; ?>><option value="">- Pilih Pegawai -</option><?php if ($hasPegawai) { while ($p = mysqli_fetch_assoc($pegawaiRes)) { echo '<option value="'.htmlspecialchars($p['pegawai_id']).'">'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?></select><?php if (!$hasPegawai): ?><small class="text-muted">Pilih TMT pada filter di atas agar daftar pegawai muncul sesuai TMT pensiun.</small><?php endif; ?></div>
<div class="col-md-3"><label class="form-label">Jenis</label><select name="jenis" class="form-select"><option value="BUP">BUP</option><option value="Dini">Dini</option><option value="Lainnya">Lainnya</option></select></div>
<div class="col-md-3"><label class="form-label">Nomor SK</label><input type="text" name="nomor_sk" class="form-control" placeholder="Nomor SK"></div>
<div class="col-md-3"><label class="form-label">Tanggal SK</label><input type="date" name="tanggal_sk" class="form-control"></div>
<div class="col-md-3"><label class="form-label">TMT <span class="text-danger">*</span></label><input type="date" name="tmt" class="form-control" required value="<?php echo htmlspecialchars($tmtFilter); ?>"></div>
<div class="col-md-12"><label class="form-label">Keterangan</label><textarea name="keterangan" class="form-control" rows="3" placeholder="Keterangan tambahan"></textarea></div>
<div class="col-md-6"><label class="form-label">File SK (pdf/jpg/png)</label><input type="file" name="file_sk" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
</div><div class="mt-3"><button type="submit" class="btn btn-primary" <?php echo !$hasPegawai ? 'disabled' : ''; ?>><i class="ti ti-device-floppy me-1"></i>Simpan</button><a href="admin/pensiun/pensiun.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div></form></div></div>
</div></div></div>
<?php include '../../includes/footer.php'; ?>
