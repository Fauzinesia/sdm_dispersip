<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = intval($_POST['pegawai_id']);
    $tanggal = $_POST['tanggal'] ?: null;
    $jam_masuk = $_POST['jam_masuk'] ?: null;
    $jam_pulang = $_POST['jam_pulang'] ?: null;
    $status_absensi = $_POST['status_absensi'] ?: 'Hadir';
    $keterangan = $_POST['keterangan'] ?: null;
    
    // Validasi: jam pulang harus setelah jam masuk
    if ($jam_masuk && $jam_pulang && $jam_pulang <= $jam_masuk) {
        $error = 'Jam pulang harus setelah jam masuk!';
    } else {
        // Check if already exists
        $check = mysqli_prepare($koneksi, "SELECT absensi_id FROM absensi WHERE pegawai_id=? AND tanggal=?");
        mysqli_stmt_bind_param($check, 'is', $pegawai_id, $tanggal);
        mysqli_stmt_execute($check);
        $checkRes = mysqli_stmt_get_result($check);
        
        if (mysqli_num_rows($checkRes) > 0) {
            $error = 'Absensi untuk pegawai ini pada tanggal tersebut sudah ada!';
        } else {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO absensi (pegawai_id, tanggal, jam_masuk, jam_pulang, status_absensi, keterangan) VALUES (?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'isssss', $pegawai_id, $tanggal, $jam_masuk, $jam_pulang, $status_absensi, $keterangan);
            if (mysqli_stmt_execute($stmt)) { 
                mysqli_stmt_close($stmt); 
                header("Location: /sdm_dispersip/admin/absensi/absensi.php?msg=".urlencode('Data berhasil ditambah')."&type=success"); 
                exit(); 
            }
            mysqli_stmt_close($stmt);
            $error = 'Gagal menambah data.';
        }
        mysqli_stmt_close($check);
    }
}

$pegawaiRes = mysqli_query($koneksi, "SELECT pegawai_id, nip, nama_lengkap FROM pegawai WHERE status_aktif='Aktif' ORDER BY nama_lengkap");
$hasPegawai = $pegawaiRes && mysqli_num_rows($pegawaiRes) > 0;
$page_title = "Tambah Absensi";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Tambah Absensi</h4></div></div></div>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Form Tambah Absensi</h5>
                    <a href="admin/absensi/absensi.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pegawai <span class="text-danger">*</span></label>
                                <select name="pegawai_id" class="form-select" required <?php echo !$hasPegawai ? 'disabled' : ''; ?>>
                                    <option value="">- Pilih Pegawai -</option>
                                    <?php if ($hasPegawai) { while ($p = mysqli_fetch_assoc($pegawaiRes)) { echo '<option value="'.htmlspecialchars($p['pegawai_id']).'">'.htmlspecialchars($p['nama_lengkap'].' ('.$p['nip'].')').'</option>'; } } ?>
                                </select>
                                <?php if (!$hasPegawai): ?>
                                    <small class="text-muted">Belum ada pegawai aktif. Tambahkan data di <a href="admin/pegawai/tambah.php">menu Pegawai</a>.</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status_absensi" class="form-select" required>
                                    <option value="Hadir">Hadir</option>
                                    <option value="Terlambat">Terlambat</option>
                                    <option value="Tidak Hadir">Tidak Hadir</option>
                                    <option value="Izin">Izin</option>
                                    <option value="Sakit">Sakit</option>
                                    <option value="Cuti">Cuti</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jam Masuk</label>
                                <input type="time" name="jam_masuk" class="form-control" value="08:00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jam Pulang</label>
                                <input type="time" name="jam_pulang" class="form-control" value="16:00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Keterangan tambahan (opsional)"></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary" <?php echo !$hasPegawai ? 'disabled' : ''; ?>><i class="ti ti-device-floppy me-1"></i>Simpan</button>
                            <a href="admin/absensi/absensi.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
