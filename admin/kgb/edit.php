<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: /sdm_dispersip/admin/kgb/kgb.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }

$stmt = mysqli_prepare($koneksi, "SELECT k.*, p.nama_lengkap FROM kgb k JOIN pegawai p ON k.pegawai_id=p.pegawai_id WHERE k.kgb_id=?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || mysqli_num_rows($res)===0) { mysqli_stmt_close($stmt); header("Location: /sdm_dispersip/admin/kgb/kgb.php?msg=".urlencode('Data tidak ditemukan')."&type=danger"); exit(); }
$kgb = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = intval($_POST['pegawai_id'] ?? 0);
    $nomor_sk = trim($_POST['nomor_sk'] ?? '');
    $tanggal_sk = $_POST['tanggal_sk'] ?? '';
    $tmt_mulai = $_POST['tmt_mulai'] ?? '';
    $gaji_lama = ($_POST['gaji_lama'] !== '' ? floatval($_POST['gaji_lama']) : 0);
    $gaji_baru = ($_POST['gaji_baru'] !== '' ? floatval($_POST['gaji_baru']) : 0);
    $jadwal_kgb_berikut = $_POST['jadwal_kgb_berikut'] ?? '';
    $status = $_POST['status'] ?? 'Draft';

    $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
    if (!$pegawai_id || !$tmt_mulai || !preg_match($dateRegex, $tmt_mulai)) {
        $error = 'Harap isi pegawai dan TMT Mulai dengan benar.';
    } else {
        $filePathRel = $kgb['file_sk'] ?? '';
        if (!empty($_FILES['file_sk']['name']) && isset($_FILES['file_sk']) && $_FILES['file_sk']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['pdf','jpg','jpeg','png'];
            $ext = strtolower(pathinfo($_FILES['file_sk']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed, true)) {
                $root = dirname(__DIR__, 2);
                $dirFS = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'kgb';
                if (!is_dir($dirFS)) { @mkdir($dirFS, 0777, true); }
                $basename = 'sk_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destFS = $dirFS . DIRECTORY_SEPARATOR . $basename;
                if (move_uploaded_file($_FILES['file_sk']['tmp_name'], $destFS)) {
                    $filePathRel = 'uploads/kgb/' . $basename;
                }
            }
        }

        $u = "UPDATE kgb SET pegawai_id=?, nomor_sk=NULLIF(?, ''), tanggal_sk=NULLIF(?, ''), tmt_mulai=?, gaji_lama=NULLIF(?, 0), gaji_baru=NULLIF(?, 0), jadwal_kgb_berikut=NULLIF(?, ''), file_sk=NULLIF(?, ''), status=? WHERE kgb_id=?";
        $ustmt = mysqli_prepare($koneksi, $u);
        mysqli_stmt_bind_param($ustmt, 'isssddsssi', $pegawai_id, $nomor_sk, $tanggal_sk, $tmt_mulai, $gaji_lama, $gaji_baru, $jadwal_kgb_berikut, $filePathRel, $status, $id);
        if (mysqli_stmt_execute($ustmt)) { mysqli_stmt_close($ustmt); header("Location: /sdm_dispersip/admin/kgb/kgb.php?msg=".urlencode('Data berhasil diubah')."&type=success"); exit(); }
        mysqli_stmt_close($ustmt); $error = 'Gagal mengubah data.';
    }
}

$pegawaiRes = mysqli_query($koneksi, "SELECT pegawai_id, nama_lengkap FROM pegawai WHERE status_aktif='Aktif' ORDER BY nama_lengkap");
$hasPegawai = $pegawaiRes && mysqli_num_rows($pegawaiRes) > 0;
$page_title = "Edit KGB";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Edit KGB</h4></div></div></div>
<?php if (!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<div class="card"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Form Edit KGB</h5><a href="admin/kgb/kgb.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a></div>
<div class="card-body"><form method="post" enctype="multipart/form-data" action="admin/kgb/edit.php?id=<?php echo $id; ?>"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Pegawai <span class="text-danger">*</span></label><select name="pegawai_id" class="form-select" required <?php echo !$hasPegawai ? 'disabled' : ''; ?>><option value="">- Pilih Pegawai -</option><?php if ($hasPegawai) { while ($p = mysqli_fetch_assoc($pegawaiRes)) { $sel = ($p['pegawai_id']==$kgb['pegawai_id'])?'selected':''; echo '<option value="'.htmlspecialchars($p['pegawai_id']).'" '.$sel.'>'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?></select></div>
<div class="col-md-3"><label class="form-label">Nomor SK</label><input type="text" name="nomor_sk" class="form-control" placeholder="Nomor SK" value="<?php echo htmlspecialchars($kgb['nomor_sk'] ?? ''); ?>"></div>
<div class="col-md-3"><label class="form-label">Tanggal SK</label><input type="date" name="tanggal_sk" class="form-control" value="<?php echo htmlspecialchars($kgb['tanggal_sk'] ?? ''); ?>"></div>
<div class="col-md-3"><label class="form-label">TMT Mulai <span class="text-danger">*</span></label><input type="date" name="tmt_mulai" class="form-control" required value="<?php echo htmlspecialchars($kgb['tmt_mulai']); ?>"></div>
<div class="col-md-3"><label class="form-label">Gaji Lama</label><input type="number" step="0.01" name="gaji_lama" class="form-control" placeholder="0.00" value="<?php echo htmlspecialchars($kgb['gaji_lama'] ?? ''); ?>"></div>
<div class="col-md-3"><label class="form-label">Gaji Baru</label><input type="number" step="0.01" name="gaji_baru" class="form-control" placeholder="0.00" value="<?php echo htmlspecialchars($kgb['gaji_baru'] ?? ''); ?>"></div>
<div class="col-md-3"><label class="form-label">Jadwal KGB Berikut</label><input type="date" name="jadwal_kgb_berikut" class="form-control" value="<?php echo htmlspecialchars($kgb['jadwal_kgb_berikut'] ?? ''); ?>"></div>
<div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="Draft" <?php echo ($kgb['status']==='Draft')?'selected':''; ?>>Draft</option><option value="Disahkan" <?php echo ($kgb['status']==='Disahkan')?'selected':''; ?>>Disahkan</option></select></div>
<div class="col-md-6"><label class="form-label">File SK (pdf/jpg/png)</label><input type="file" name="file_sk" class="form-control" accept=".pdf,.jpg,.jpeg,.png"><?php if (!empty($kgb['file_sk'])) { echo '<small class="text-muted">File saat ini: <a href="/sdm_dispersip/'.htmlspecialchars($kgb['file_sk']).'" target="_blank">'.htmlspecialchars(basename($kgb['file_sk'])).'</a></small>'; } ?></div>
</div><div class="mt-3"><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Perubahan</button><a href="admin/kgb/kgb.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div></form></div></div>
</div></div></div>
<?php include '../../includes/footer.php'; ?>

