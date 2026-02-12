<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: /sdm_dispersip/admin/absensi/absensi.php"); exit(); }

$stmt = mysqli_prepare($koneksi, "SELECT a.*, p.nama_lengkap, p.nip FROM absensi a JOIN pegawai p ON a.pegawai_id=p.pegawai_id WHERE a.absensi_id=?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$data) { header("Location: /sdm_dispersip/admin/absensi/absensi.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'] ?: null;
    $jam_masuk = $_POST['jam_masuk'] ?: null;
    $jam_pulang = $_POST['jam_pulang'] ?: null;
    $status_absensi = $_POST['status_absensi'] ?: 'Hadir';
    $keterangan = $_POST['keterangan'] ?: null;
    
    // Validasi: jam pulang harus setelah jam masuk
    if ($jam_masuk && $jam_pulang && $jam_pulang <= $jam_masuk) {
        $error = 'Jam pulang harus setelah jam masuk!';
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE absensi SET tanggal=?, jam_masuk=?, jam_pulang=?, status_absensi=?, keterangan=? WHERE absensi_id=?");
        mysqli_stmt_bind_param($stmt, 'sssssi', $tanggal, $jam_masuk, $jam_pulang, $status_absensi, $keterangan, $id);
        if (mysqli_stmt_execute($stmt)) { 
            mysqli_stmt_close($stmt); 
            header("Location: /sdm_dispersip/admin/absensi/absensi.php?msg=".urlencode('Data berhasil diupdate')."&type=success"); 
            exit(); 
        }
        mysqli_stmt_close($stmt);
        $error = 'Gagal mengupdate data.';
    }
}

$page_title = "Edit Absensi";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Edit Absensi</h4></div></div></div>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Form Edit Absensi</h5>
                    <a href="admin/absensi/absensi.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pegawai</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['nama_lengkap'].' ('.$data['nip'].')'); ?>" disabled>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal" class="form-control" value="<?php echo htmlspecialchars($data['tanggal']); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status_absensi" class="form-select" required>
                                    <?php 
                                    $statuses = ['Hadir','Terlambat','Tidak Hadir','Izin','Sakit','Cuti'];
                                    foreach ($statuses as $s) {
                                        $sel = ($data['status_absensi'] === $s) ? 'selected' : '';
                                        echo "<option value=\"$s\" $sel>$s</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jam Masuk</label>
                                <input type="time" name="jam_masuk" class="form-control" value="<?php echo htmlspecialchars($data['jam_masuk'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jam Pulang</label>
                                <input type="time" name="jam_pulang" class="form-control" value="<?php echo htmlspecialchars($data['jam_pulang'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Keterangan tambahan"><?php echo htmlspecialchars($data['keterangan'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Update</button>
                            <a href="admin/absensi/absensi.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
