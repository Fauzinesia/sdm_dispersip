<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: /sdm_dispersip/admin/gaji/gaji.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }

$stmt = mysqli_prepare($koneksi, "SELECT g.*, p.nama_lengkap FROM gaji g JOIN pegawai p ON g.pegawai_id=p.pegawai_id WHERE g.gaji_id=?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || mysqli_num_rows($res)===0) { mysqli_stmt_close($stmt); header("Location: /sdm_dispersip/admin/gaji/gaji.php?msg=".urlencode('Data tidak ditemukan')."&type=danger"); exit(); }
$g = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pegawai_id = intval($_POST['pegawai_id'] ?? 0);
  $periode = trim($_POST['periode'] ?? '');
  $gaji_pokok = ($_POST['gaji_pokok'] !== '' ? floatval($_POST['gaji_pokok']) : 0);
  $tunjangan = ($_POST['tunjangan'] !== '' ? floatval($_POST['tunjangan']) : 0);
  $potongan = ($_POST['potongan'] !== '' ? floatval($_POST['potongan']) : 0);
  $keterangan = trim($_POST['keterangan'] ?? '');
  if (!$pegawai_id || !preg_match('/^\d{4}-\d{2}$/', $periode)) { $error = 'Harap isi pegawai dan periode (YYYY-MM) dengan benar.'; }
  if (empty($error)) {
    $cek = mysqli_prepare($koneksi, "SELECT COUNT(*) AS c FROM gaji WHERE pegawai_id=? AND periode=? AND gaji_id<>?");
    mysqli_stmt_bind_param($cek, 'isi', $pegawai_id, $periode, $id);
    mysqli_stmt_execute($cek);
    $res = mysqli_stmt_get_result($cek);
    $exists = ($res && (intval(mysqli_fetch_assoc($res)['c'] ?? 0) > 0));
    mysqli_stmt_close($cek);
    if ($exists) { $error = 'Data gaji untuk pegawai dan periode ini sudah ada.'; }
  }
  if (empty($error)) {
    $u = "UPDATE gaji SET pegawai_id=?, periode=?, gaji_pokok=?, tunjangan=?, potongan=?, keterangan=NULLIF(?, '') WHERE gaji_id=?";
    $ustmt = mysqli_prepare($koneksi, $u);
    mysqli_stmt_bind_param($ustmt, 'issddsi', $pegawai_id, $periode, $gaji_pokok, $tunjangan, $potongan, $keterangan, $id);
    if (mysqli_stmt_execute($ustmt)) { mysqli_stmt_close($ustmt); header("Location: /sdm_dispersip/admin/gaji/gaji.php?msg=".urlencode('Data berhasil diubah')."&type=success"); exit(); }
    mysqli_stmt_close($ustmt); $error = 'Gagal mengubah data gaji.';
  }
}

$pegRes = mysqli_query($koneksi, "SELECT pegawai_id, nama_lengkap FROM pegawai WHERE status_aktif='Aktif' ORDER BY nama_lengkap");
$hasPegawai = $pegRes && mysqli_num_rows($pegRes) > 0;
$page_title = "Edit Data Gaji";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Edit Data Gaji</h4></div></div></div>
<?php if (!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<div class="card"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Form Edit Gaji</h5><a href="admin/gaji/gaji.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a></div>
<div class="card-body"><form method="post"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Pegawai <span class="text-danger">*</span></label><select name="pegawai_id" class="form-select" required <?php echo !$hasPegawai ? 'disabled' : ''; ?>><option value="">- Pilih Pegawai -</option><?php if ($hasPegawai) { while ($p = mysqli_fetch_assoc($pegRes)) { $sel = ($p['pegawai_id']==$g['pegawai_id'])?'selected':''; echo '<option value="'.htmlspecialchars($p['pegawai_id']).'" '.$sel.'>'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?></select></div>
<div class="col-md-3"><label class="form-label">Periode <span class="text-danger">*</span></label><input type="month" name="periode" class="form-control" required value="<?php echo htmlspecialchars($g['periode']); ?>"></div>
<div class="col-md-3"><label class="form-label">Gaji Pokok</label><input type="number" step="0.01" name="gaji_pokok" class="form-control" placeholder="0.00" value="<?php echo htmlspecialchars($g['gaji_pokok']); ?>"></div>
<div class="col-md-3"><label class="form-label">Tunjangan</label><input type="number" step="0.01" name="tunjangan" class="form-control" placeholder="0.00" value="<?php echo htmlspecialchars($g['tunjangan']); ?>"></div>
<div class="col-md-3"><label class="form-label">Potongan</label><input type="number" step="0.01" name="potongan" class="form-control" placeholder="0.00" value="<?php echo htmlspecialchars($g['potongan']); ?>"></div>
<div class="col-md-12"><label class="form-label">Keterangan</label><textarea name="keterangan" class="form-control" rows="3" placeholder="Catatan"><?php echo htmlspecialchars($g['keterangan'] ?? ''); ?></textarea></div>
</div><div class="mt-3"><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Perubahan</button><a href="admin/gaji/gaji.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div></form></div></div>
</div></div></div>
<?php include '../../includes/footer.php'; ?>

