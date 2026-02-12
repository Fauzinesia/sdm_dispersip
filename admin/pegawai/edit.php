<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$page_title = "Ubah Pegawai";
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: /sdm_dispersip/admin/pegawai/pegawai.php?msg=".urlencode('ID tidak valid').'&type=danger'); exit(); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = $_POST['nik'];
    $nip_input = trim($_POST['nip'] ?? '');
    $nip_digits = preg_replace('/\D/', '', $nip_input);
    $nip = ($nip_digits !== '' && preg_match('/^\d{18}$/', $nip_digits)) ? $nip_digits : null;
    $nama_lengkap = $_POST['nama_lengkap'];
    $jk = $_POST['jk'];
    $tgl_lahir = $_POST['tgl_lahir'] ?: null;
    $status_kepegawaian = $_POST['status_kepegawaian'];
    $jabatan_id = $_POST['jabatan_id'] ?: null;
    $pangkat_id = $_POST['pangkat_id'] ?: null;
    $tgl_mulai_kerja = $_POST['tgl_mulai_kerja'] ?: null;
    $alamat = $_POST['alamat'] ?: null;
    $status_aktif = $_POST['status_aktif'];
    $tmt_pensiun = $_POST['tmt_pensiun'] ?: null;
    try {
        $stmt = mysqli_prepare($koneksi, "UPDATE pegawai SET nik=?, nip=?, nama_lengkap=?, jk=?, tgl_lahir=?, status_kepegawaian=?, jabatan_id=?, pangkat_id=?, tgl_mulai_kerja=?, alamat=?, status_aktif=?, tmt_pensiun=? WHERE pegawai_id=?");
        mysqli_stmt_bind_param($stmt, 'ssssssssssssi', $nik, $nip, $nama_lengkap, $jk, $tgl_lahir, $status_kepegawaian, $jabatan_id, $pangkat_id, $tgl_mulai_kerja, $alamat, $status_aktif, $tmt_pensiun, $id);
        if (mysqli_stmt_execute($stmt)) { header("Location: /sdm_dispersip/admin/pegawai/pegawai.php?msg=".urlencode('Data berhasil diubah').'&type=success'); exit(); }
        mysqli_stmt_close($stmt);
        $error = 'Gagal mengubah data.';
    } catch (mysqli_sql_exception $e) {
        $error = 'Gagal mengubah data. Periksa format NIP/NIK dan data terkait.';
    }
}
$stmt = mysqli_prepare($koneksi, "SELECT * FROM pegawai WHERE pegawai_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
function options($koneksi, $table, $id_field, $name_field, $selected_id = null) {
    $out = '';
    $res = mysqli_query($koneksi, "SELECT $id_field AS id, $name_field AS name FROM $table ORDER BY name");
    while ($r = mysqli_fetch_assoc($res)) {
        $sel = ($selected_id && (string)$selected_id === (string)$r['id']) ? ' selected' : '';
        $out .= '<option value="' . htmlspecialchars($r['id']) . '"' . $sel . '>' . htmlspecialchars($r['name']) . '</option>';
    }
    return $out;
}
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Ubah Pegawai</h4></div></div></div>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <a href="admin/pegawai/pegawai.php" class="btn btn-light">Kembali</a>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">NIK</label><input type="text" name="nik" class="form-control" required value="<?php echo htmlspecialchars($data['nik'] ?? ''); ?>"></div>
                            <div class="col-md-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control" value="<?php echo htmlspecialchars($data['nip'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-control" required value="<?php echo htmlspecialchars($data['nama_lengkap'] ?? ''); ?>"></div>
                            <div class="col-md-3"><label class="form-label">Jenis Kelamin</label><select name="jk" class="form-select" required><option value="L" <?php echo (($data['jk'] ?? '') === 'L') ? 'selected' : ''; ?>>Laki-laki</option><option value="P" <?php echo (($data['jk'] ?? '') === 'P') ? 'selected' : ''; ?>>Perempuan</option></select></div>
                            <div class="col-md-3"><label class="form-label">Tanggal Lahir</label><input type="date" name="tgl_lahir" class="form-control" value="<?php echo htmlspecialchars($data['tgl_lahir'] ?? ''); ?>"></div>
                            <div class="col-md-3"><label class="form-label">Status Kepegawaian</label><select name="status_kepegawaian" class="form-select" required><?php $statuses = ['PNS','PPPK','Honorer','Kontrak']; foreach ($statuses as $st) { $sel = (($data['status_kepegawaian'] ?? '') === $st) ? ' selected' : ''; echo '<option value="'.$st.'"'.$sel.'>'.$st.'</option>'; } ?></select></div>
                            <div class="col-md-3"><label class="form-label">Jabatan</label><select name="jabatan_id" class="form-select"><option value="">-</option><?php echo options($koneksi, 'master_jabatan', 'jabatan_id', 'nama_jabatan', $data['jabatan_id'] ?? null); ?></select></div>
                            <div class="col-md-3"><label class="form-label">Pangkat</label><select name="pangkat_id" class="form-select"><option value="">-</option><?php echo options($koneksi, 'master_pangkat', 'pangkat_id', 'nama_pangkat', $data['pangkat_id'] ?? null); ?></select></div>
                            <div class="col-md-3"><label class="form-label">Tgl Mulai Kerja</label><input type="date" name="tgl_mulai_kerja" class="form-control" value="<?php echo htmlspecialchars($data['tgl_mulai_kerja'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2"><?php echo htmlspecialchars($data['alamat'] ?? ''); ?></textarea></div>
                            <div class="col-md-3"><label class="form-label">Status Aktif</label><select name="status_aktif" class="form-select" required><?php $aktif = ['Aktif','Pensiun','Pindah','Meninggal','Nonaktif']; foreach ($aktif as $st) { $sel = (($data['status_aktif'] ?? 'Aktif') === $st) ? ' selected' : ''; echo '<option value="'.$st.'"'.$sel.'>'.$st.'</option>'; } ?></select></div>
                            <div class="col-md-3"><label class="form-label">TMT Pensiun</label><input type="date" name="tmt_pensiun" class="form-control" value="<?php echo htmlspecialchars($data['tmt_pensiun'] ?? ''); ?>"></div>
                        </div>
                        <div class="mt-3"><button type="submit" class="btn btn-primary">Simpan</button><a href="admin/pegawai/pegawai.php" class="btn btn-light ms-2">Batal</a></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
