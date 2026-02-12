<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: /sdm_dispersip/admin/hari_libur/hari_libur.php"); exit(); }

// Get data
$stmt = mysqli_prepare($koneksi, "SELECT * FROM hari_libur WHERE libur_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data) { header("Location: /sdm_dispersip/admin/hari_libur/hari_libur.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'] ?? '';
    $nama_libur = trim($_POST['nama_libur'] ?? '');
    $jenis = $_POST['jenis'] ?? 'Nasional';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($tanggal) || empty($nama_libur)) {
        $error = 'Tanggal dan Nama Libur harus diisi!';
    } else {
        // Check duplicate (exclude current record)
        $check = mysqli_prepare($koneksi, "SELECT libur_id FROM hari_libur WHERE tanggal = ? AND libur_id != ?");
        mysqli_stmt_bind_param($check, 'si', $tanggal, $id);
        mysqli_stmt_execute($check);
        if (mysqli_num_rows(mysqli_stmt_get_result($check)) > 0) {
            $error = 'Tanggal libur ini sudah ada!';
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE hari_libur SET tanggal=?, nama_libur=?, jenis=?, keterangan=?, is_active=? WHERE libur_id=?");
            mysqli_stmt_bind_param($stmt, 'ssssii', $tanggal, $nama_libur, $jenis, $keterangan, $is_active, $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: /sdm_dispersip/admin/hari_libur/hari_libur.php?msg=" . urlencode('Data berhasil diupdate') . "&type=success");
                exit();
            }
            mysqli_stmt_close($stmt);
            $error = 'Gagal mengupdate data.';
        }
        mysqli_stmt_close($check);
    }
} else {
    // Pre-fill form
    $_POST = $data;
}

$page_title = "Edit Hari Libur";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Edit Hari Libur</h4>
                    </div>
                </div>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Form Edit Hari Libur</h5>
                    <a href="admin/hari_libur/hari_libur.php" class="btn btn-light">
                        <i class="ti ti-arrow-left me-1"></i>Kembali
                    </a>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal" class="form-control" value="<?php echo htmlspecialchars($_POST['tanggal'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenis <span class="text-danger">*</span></label>
                                <select name="jenis" class="form-select" required>
                                    <option value="Nasional" <?php echo ($_POST['jenis'] === 'Nasional') ? 'selected' : ''; ?>>Nasional</option>
                                    <option value="Cuti Bersama" <?php echo ($_POST['jenis'] === 'Cuti Bersama') ? 'selected' : ''; ?>>Cuti Bersama</option>
                                    <option value="Khusus" <?php echo ($_POST['jenis'] === 'Khusus') ? 'selected' : ''; ?>>Khusus</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo ($_POST['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Aktif</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Nama Libur <span class="text-danger">*</span></label>
                                <input type="text" name="nama_libur" class="form-control" value="<?php echo htmlspecialchars($_POST['nama_libur'] ?? ''); ?>" placeholder="Contoh: Hari Raya Idul Fitri 1446 H" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Keterangan tambahan (opsional)"><?php echo htmlspecialchars($_POST['keterangan'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i>Update
                            </button>
                            <a href="admin/hari_libur/hari_libur.php" class="btn btn-light ms-2">
                                <i class="ti ti-x me-1"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
