<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = intval($_POST['pegawai_id']);
    $jenis_cuti = $_POST['jenis_cuti'];
    $tgl_mulai = $_POST['tgl_mulai'] ?: null;
    $tgl_selesai = $_POST['tgl_selesai'] ?: null;
    $alasan = $_POST['alasan'] ?: null;
    $status = $_POST['status'] ?: 'Menunggu';
    $lama_hari = 0;
    if ($tgl_mulai && $tgl_selesai) {
        $lama_hari = hitungHariKerja($koneksi, $tgl_mulai, $tgl_selesai);
    }
    $stmt = mysqli_prepare($koneksi, "INSERT INTO cuti (pegawai_id, jenis_cuti, tgl_mulai, tgl_selesai, lama_hari, alasan, status) VALUES (?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'isssiss', $pegawai_id, $jenis_cuti, $tgl_mulai, $tgl_selesai, $lama_hari, $alasan, $status);
    if (mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); header("Location: /sdm_dispersip/admin/cuti/cuti.php?msg=".urlencode('Data berhasil ditambah')."&type=success"); exit(); }
    mysqli_stmt_close($stmt);
    $error = 'Gagal menambah data.';
}
// Hanya tampilkan pegawai dengan role 'pegawai' (join ke tabel users)
$pegawaiRes = mysqli_query($koneksi, "SELECT p.pegawai_id, p.nama_lengkap FROM pegawai p JOIN users u ON p.user_id = u.user_id WHERE p.status_aktif='Aktif' AND u.role='pegawai' ORDER BY p.nama_lengkap");
$hasPegawai = $pegawaiRes && mysqli_num_rows($pegawaiRes) > 0;
$page_title = "Tambah Cuti";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Tambah Cuti</h4></div></div></div>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Form Tambah Cuti</h5>
                    <a href="admin/cuti/cuti.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pegawai <span class="text-danger">*</span></label>
                                <select name="pegawai_id" class="form-select" required <?php echo !$hasPegawai ? 'disabled' : ''; ?>>
                                    <option value="">- Pilih Pegawai -</option>
                                    <?php if ($hasPegawai) { while ($p = mysqli_fetch_assoc($pegawaiRes)) { echo '<option value="'.htmlspecialchars($p['pegawai_id']).'">'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?>
                                </select>
                                <?php if (!$hasPegawai): ?>
                                    <small class="text-muted">Belum ada pegawai aktif. Tambahkan data di <a href="admin/pegawai/tambah.php">menu Pegawai</a>.</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenis Cuti <span class="text-danger">*</span></label>
                                <select name="jenis_cuti" class="form-select" required>
                                    <option value="Tahunan">Tahunan</option>
                                    <option value="Sakit">Sakit</option>
                                    <option value="Melahirkan">Melahirkan</option>
                                    <option value="Penting">Penting</option>
                                    <option value="Besar">Besar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Menunggu">Menunggu</option>
                                    <option value="Disetujui">Disetujui</option>
                                    <option value="Ditolak">Ditolak</option>
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label">Tgl Mulai <span class="text-danger">*</span></label><input type="date" name="tgl_mulai" class="form-control" required></div>
                            <div class="col-md-3"><label class="form-label">Tgl Selesai <span class="text-danger">*</span></label><input type="date" name="tgl_selesai" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">Alasan</label><textarea name="alasan" class="form-control" rows="3" placeholder="Alasan cuti"></textarea></div>
                        </div>
                        <div class="mt-3"><button type="submit" class="btn btn-primary" <?php echo !$hasPegawai ? 'disabled' : ''; ?>><i class="ti ti-device-floppy me-1"></i>Simpan</button><a href="admin/cuti/cuti.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
