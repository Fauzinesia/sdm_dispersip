<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pegawai_id = intval($_POST['pegawai_id'] ?? 0);
  $jenis_dokumen = trim($_POST['jenis_dokumen'] ?? '');
  $nama_dokumen = trim($_POST['nama_dokumen'] ?? '');
  $uploaded_by = intval($_SESSION['user_id']);
  if (!$pegawai_id || $nama_dokumen === '') { $error = 'Pegawai dan Nama Dokumen wajib diisi.'; }
  $filePathRel = '';
  if (empty($error)) {
    if (!empty($_FILES['file_path']['name']) && isset($_FILES['file_path']) && $_FILES['file_path']['error'] === UPLOAD_ERR_OK) {
      $allowed = ['pdf','jpg','jpeg','png'];
      $ext = strtolower(pathinfo($_FILES['file_path']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, $allowed, true)) { $error = 'Format file tidak didukung.'; }
      else {
        $root = dirname(__DIR__, 2);
        $dirFS = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR . 'pegawai_' . $pegawai_id;
        if (!is_dir($dirFS)) { @mkdir($dirFS, 0777, true); }
        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', pathinfo($_FILES['file_path']['name'], PATHINFO_FILENAME));
        $basename = $safeName . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $destFS = $dirFS . DIRECTORY_SEPARATOR . $basename;
        if (move_uploaded_file($_FILES['file_path']['tmp_name'], $destFS)) { $filePathRel = 'uploads/arsip/pegawai_' . $pegawai_id . '/' . $basename; }
        else { $error = 'Gagal mengunggah file.'; }
      }
    } else { $error = 'File dokumen wajib diunggah.'; }
  }
  if (empty($error)) {
    $stmt = mysqli_prepare($koneksi, "INSERT INTO arsip_dokumen (pegawai_id, jenis_dokumen, nama_dokumen, file_path, uploaded_by) VALUES (?, NULLIF(?, ''), ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'isssi', $pegawai_id, $jenis_dokumen, $nama_dokumen, $filePathRel, $uploaded_by);
    if (mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); header("Location: /sdm_dispersip/admin/arsip/arsip.php?msg=".urlencode('Dokumen berhasil ditambah')."&type=success"); exit(); }
    mysqli_stmt_close($stmt); $error = 'Gagal menambah dokumen.';
  }
}

$pegRes = mysqli_query($koneksi, "SELECT pegawai_id, nama_lengkap FROM pegawai ORDER BY nama_lengkap");
$hasPegawai = $pegRes && mysqli_num_rows($pegRes) > 0;
$page_title = "Tambah Arsip Dokumen";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Tambah Arsip Dokumen</h4></div></div></div>
<?php if (!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<div class="card"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Form Tambah Dokumen</h5><a href="arsip.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a></div>
<div class="card-body"><form method="post" enctype="multipart/form-data"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Pegawai <span class="text-danger">*</span></label><select name="pegawai_id" class="form-select" required <?php echo !$hasPegawai ? 'disabled' : ''; ?>><option value="">- Pilih Pegawai -</option><?php if ($hasPegawai) { while ($p = mysqli_fetch_assoc($pegRes)) { echo '<option value="'.htmlspecialchars($p['pegawai_id']).'">'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?></select></div>
<div class="col-md-3"><label class="form-label">Jenis Dokumen</label><input type="text" name="jenis_dokumen" class="form-control" placeholder="Contoh: SK, Sertifikat"></div>
<div class="col-md-3"><label class="form-label">Nama Dokumen <span class="text-danger">*</span></label><input type="text" name="nama_dokumen" class="form-control" required placeholder="Nama dokumen"></div>
<div class="col-md-6"><label class="form-label">File Dokumen (pdf/jpg/png) <span class="text-danger">*</span></label><input type="file" name="file_path" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div>
</div><div class="mt-3"><button type="submit" class="btn btn-primary" <?php echo !$hasPegawai ? 'disabled' : ''; ?>><i class="ti ti-device-floppy me-1"></i>Simpan</button><a href="arsip.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div></form></div></div>
</div></div></div>
<?php include '../../includes/footer.php'; ?>

