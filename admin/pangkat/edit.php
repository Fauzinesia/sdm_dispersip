<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

// Get pangkat_id
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: /sdm_dispersip/admin/pangkat/pangkat.php?msg=" . urlencode('ID tidak valid') . "&type=danger");
    exit();
}

// Get pangkat data
$query = "SELECT * FROM master_pangkat WHERE pangkat_id = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    mysqli_stmt_close($stmt);
    header("Location: /sdm_dispersip/admin/pangkat/pangkat.php?msg=" . urlencode('Data tidak ditemukan') . "&type=danger");
    exit();
}

$pangkat = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Process POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pangkat = $_POST['nama_pangkat'];
    $golongan = $_POST['golongan'];
    $keterangan = $_POST['keterangan'] ?: null;
    
    $update_query = "UPDATE master_pangkat SET nama_pangkat = ?, golongan = ?, keterangan = ? WHERE pangkat_id = ?";
    $stmt = mysqli_prepare($koneksi, $update_query);
    mysqli_stmt_bind_param($stmt, 'sssi', $nama_pangkat, $golongan, $keterangan, $id);
    
    try {
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: /sdm_dispersip/admin/pangkat/pangkat.php?msg=" . urlencode('Data berhasil diubah') . "&type=success");
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        mysqli_stmt_close($stmt);
        $error = 'Gagal mengubah data: ' . $e->getMessage();
    }
}

$page_title = "Edit Pangkat";
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
                        <h4 class="page-title">Edit Pangkat</h4>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Form Edit Pangkat</h5>
                    <a href="/sdm_dispersip/admin/pangkat/pangkat.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Pangkat <span class="text-danger">*</span></label>
                                <input type="text" name="nama_pangkat" class="form-control" required placeholder="Contoh: Pembina Utama Muda" value="<?php echo htmlspecialchars($pangkat['nama_pangkat']); ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Golongan <span class="text-danger">*</span></label>
                                <input type="text" name="golongan" class="form-control" required placeholder="Contoh: IV/c" value="<?php echo htmlspecialchars($pangkat['golongan']); ?>">
                                <small class="text-muted">Format: Angka Romawi/Huruf (contoh: IV/c)</small>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Deskripsi atau keterangan tambahan tentang pangkat ini"><?php echo htmlspecialchars($pangkat['keterangan'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Perubahan</button>
                            <a href="/sdm_dispersip/admin/pangkat/pangkat.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
