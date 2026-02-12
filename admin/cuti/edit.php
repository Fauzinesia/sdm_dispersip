<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: admin/cuti/cuti.php?msg=".urlencode('ID tidak valid')."&type=danger"); exit(); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = intval($_POST['pegawai_id']);
    $jenis_cuti = $_POST['jenis_cuti'];
    $tgl_mulai = $_POST['tgl_mulai'] ?: null;
    $tgl_selesai = $_POST['tgl_selesai'] ?: null;
    $alasan = $_POST['alasan'] ?: null;
    $status = $_POST['status'] ?: 'Menunggu';
    $lama_hari = 0;
    if ($tgl_mulai && $tgl_selesai) { $lama_hari = hitungHariKerja($koneksi, $tgl_mulai, $tgl_selesai); }
    $stmt = mysqli_prepare($koneksi, "UPDATE cuti SET pegawai_id=?, jenis_cuti=?, tgl_mulai=?, tgl_selesai=?, lama_hari=?, alasan=?, status=? WHERE cuti_id=?");
    mysqli_stmt_bind_param($stmt, 'isssissi', $pegawai_id, $jenis_cuti, $tgl_mulai, $tgl_selesai, $lama_hari, $alasan, $status, $id);
    if (mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); header("Location: /sdm_dispersip/admin/cuti/cuti.php?msg=".urlencode('Data berhasil diubah')."&type=success"); exit(); }
    mysqli_stmt_close($stmt);
    $error = 'Gagal mengubah data.';
}
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM cuti WHERE cuti_id=".(int)$id));
$page_title = "Ubah Cuti";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Ubah Cuti</h4></div></div></div>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Form Ubah Cuti</h5>
                    <a href="admin/cuti/cuti.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pegawai</label>
                                <select name="pegawai_id" class="form-select" required>
                                    <?php $opt = mysqli_query($koneksi, "SELECT pegawai_id, nama_lengkap FROM pegawai ORDER BY nama_lengkap"); while ($p = mysqli_fetch_assoc($opt)) { $sel = (($data['pegawai_id'] ?? 0)==$p['pegawai_id'])?' selected':''; echo '<option value="'.htmlspecialchars($p['pegawai_id']).'"'.$sel.'>'.htmlspecialchars($p['nama_lengkap']).'</option>'; } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenis Cuti</label>
                                <select name="jenis_cuti" class="form-select" required>
                                    <?php foreach (['Tahunan','Sakit','Melahirkan','Penting','Besar'] as $j) { $sel = (($data['jenis_cuti'] ?? '')===$j)?' selected':''; echo '<option value="'.$j.'"'.$sel.'>'.$j.'</option>'; } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach (['Menunggu','Disetujui','Ditolak'] as $s) { $sel = (($data['status'] ?? '')===$s)?' selected':''; echo '<option value="'.$s.'"'.$sel.'>'.$s.'</option>'; } ?>
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label">Tgl Mulai</label><input type="date" name="tgl_mulai" class="form-control" value="<?php echo htmlspecialchars($data['tgl_mulai'] ?? ''); ?>" required></div>
                            <div class="col-md-3"><label class="form-label">Tgl Selesai</label><input type="date" name="tgl_selesai" class="form-control" value="<?php echo htmlspecialchars($data['tgl_selesai'] ?? ''); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Alasan</label><textarea name="alasan" class="form-control" rows="3"><?php echo htmlspecialchars($data['alasan'] ?? ''); ?></textarea></div>
                        </div>
                        <div class="mt-3"><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button><a href="admin/cuti/cuti.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
