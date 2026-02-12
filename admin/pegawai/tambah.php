<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik_raw = trim($_POST['nik'] ?? '');
    $nik_digits = preg_replace('/\D/', '', $nik_raw);
    $nik = $nik_digits;
    $nip_input = trim($_POST['nip'] ?? '');
    $nip_digits = preg_replace('/\D/', '', $nip_input);
    $nip = ($nip_digits !== '' && preg_match('/^\d{18}$/', $nip_digits)) ? $nip_digits : null;
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $jk = $_POST['jk'] ?? 'L';
    $tgl_lahir = $_POST['tgl_lahir'] ?: null;
    $status_kepegawaian = $_POST['status_kepegawaian'] ?? '';
    $jabatan_id = $_POST['jabatan_id'] ?: null;
    $pangkat_id = $_POST['pangkat_id'] ?: null;
    $tgl_mulai_kerja = $_POST['tgl_mulai_kerja'] ?: null;
    $alamat = $_POST['alamat'] ?: null;
    $status_aktif = $_POST['status_aktif'] ?? 'Aktif';
    $tmt_pensiun = $_POST['tmt_pensiun'] ?: null;

    if (!preg_match('/^\d{16}$/', $nik)) { $error = 'NIK harus tepat 16 digit angka.'; }
    if (empty($error) && $nama_lengkap === '') { $error = 'Nama lengkap wajib diisi.'; }
    if (empty($error)) {
        $cekNik = mysqli_prepare($koneksi, "SELECT COUNT(*) AS c FROM pegawai WHERE nik=?");
        mysqli_stmt_bind_param($cekNik, 's', $nik);
        mysqli_stmt_execute($cekNik);
        $resNik = mysqli_stmt_get_result($cekNik);
        $nikExists = ($resNik && (intval(mysqli_fetch_assoc($resNik)['c'] ?? 0) > 0));
        mysqli_stmt_close($cekNik);
        if ($nikExists) { $error = 'NIK sudah terdaftar.'; }
    }
    if (empty($error) && $nip !== null) {
        $cekNip = mysqli_prepare($koneksi, "SELECT COUNT(*) AS c FROM pegawai WHERE nip=?");
        mysqli_stmt_bind_param($cekNip, 's', $nip);
        mysqli_stmt_execute($cekNip);
        $resNip = mysqli_stmt_get_result($cekNip);
        $nipExists = ($resNip && (intval(mysqli_fetch_assoc($resNip)['c'] ?? 0) > 0));
        mysqli_stmt_close($cekNip);
        if ($nipExists) { $error = 'NIP sudah terdaftar.'; }
    }
    if (empty($error)) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO pegawai (nik, nip, nama_lengkap, jk, tgl_lahir, status_kepegawaian, jabatan_id, pangkat_id, tgl_mulai_kerja, alamat, status_aktif, tmt_pensiun) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssssssssssss', $nik, $nip, $nama_lengkap, $jk, $tgl_lahir, $status_kepegawaian, $jabatan_id, $pangkat_id, $tgl_mulai_kerja, $alamat, $status_aktif, $tmt_pensiun);
        if (mysqli_stmt_execute($stmt)) { header("Location: /sdm_dispersip/admin/pegawai/pegawai.php?msg=" . urlencode('Data berhasil ditambah') . "&type=success"); exit(); }
        mysqli_stmt_close($stmt);
        $error = 'Gagal menambah data. Periksa duplikasi NIK/NIP.';
    }
}
function options($koneksi, $table, $id_field, $name_field, $selected_id = null) {
    $out = '';
    $res = mysqli_query($koneksi, "SELECT $id_field AS id, $name_field AS name FROM $table ORDER BY name");
    if ($res) { while ($r = mysqli_fetch_assoc($res)) { $sel = ($selected_id !== null && (string)$selected_id === (string)$r['id']) ? ' selected' : ''; $out .= '<option value="' . htmlspecialchars($r['id']) . '"' . $sel . '>' . htmlspecialchars($r['name']) . '</option>'; } }
    return $out;
}
$page_title = "Tambah Pegawai";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Tambah Pegawai</h4></div></div></div>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Form Tambah Pegawai</h5>
                    <a href="admin/pegawai/pegawai.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">NIK <span class="text-danger">*</span></label><input type="text" name="nik" class="form-control" required inputmode="numeric" pattern="\d{16}" maxlength="16" placeholder="16 digit sesuai KTP"></div>
                            <div class="col-md-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control" inputmode="numeric" pattern="\d{18}" maxlength="18" placeholder="Opsional 18 digit"></div>
                            <div class="col-md-6"><label class="form-label">Nama Lengkap <span class="text-danger">*</span></label><input type="text" name="nama_lengkap" class="form-control" required placeholder="Nama sesuai identitas"></div>
                            <div class="col-md-3"><label class="form-label">Jenis Kelamin</label><select name="jk" class="form-select" required><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
                            <div class="col-md-3"><label class="form-label">Tanggal Lahir</label><input type="date" name="tgl_lahir" class="form-control" placeholder="YYYY-MM-DD"></div>
                            <div class="col-md-3"><label class="form-label">Status Kepegawaian <span class="text-danger">*</span></label><select name="status_kepegawaian" class="form-select" required><option value="">- Pilih Status -</option><option value="PNS">PNS</option><option value="PPPK">PPPK</option><option value="Honorer">Honorer</option><option value="Kontrak">Kontrak</option></select></div>
                            <div class="col-md-3"><label class="form-label">Jabatan</label><select name="jabatan_id" class="form-select"><option value="">-</option><?php echo options($koneksi, 'master_jabatan', 'jabatan_id', 'nama_jabatan'); ?></select></div>
                            <div class="col-md-3"><label class="form-label">Pangkat</label><select name="pangkat_id" class="form-select"><option value="">-</option><?php echo options($koneksi, 'master_pangkat', 'pangkat_id', 'nama_pangkat'); ?></select></div>
                            <div class="col-md-3"><label class="form-label">Tgl Mulai Kerja</label><input type="date" name="tgl_mulai_kerja" class="form-control" placeholder="YYYY-MM-DD"></div>
                            <div class="col-md-6"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="3" placeholder="Alamat domisili"></textarea></div>
                            <div class="col-md-3"><label class="form-label">Status Aktif <span class="text-danger">*</span></label><select name="status_aktif" class="form-select" required><option value="Aktif">Aktif</option><option value="Pensiun">Pensiun</option><option value="Pindah">Pindah</option><option value="Meninggal">Meninggal</option><option value="Nonaktif">Nonaktif</option></select></div>
                            <div class="col-md-3"><label class="form-label">TMT Pensiun</label><input type="date" name="tmt_pensiun" class="form-control" placeholder="YYYY-MM-DD"></div>
                        </div>
                        <div class="mt-3"><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button><a href="admin/pegawai/pegawai.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
