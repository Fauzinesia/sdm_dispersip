<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

// Process POST before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pangkat = $_POST['nama_pangkat'];
    $golongan = $_POST['golongan'];
    $keterangan = $_POST['keterangan'] ?: null;
    
    $stmt = mysqli_prepare($koneksi, "INSERT INTO master_pangkat (nama_pangkat, golongan, keterangan) VALUES (?,?,?)");
    mysqli_stmt_bind_param($stmt, 'sss', $nama_pangkat, $golongan, $keterangan);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: /sdm_dispersip/admin/pangkat/pangkat.php?msg=" . urlencode('Data berhasil ditambah') . "&type=success");
        exit();
    }
    mysqli_stmt_close($stmt);
    $error = 'Gagal menambah data. Periksa duplikasi nama pangkat.';
}

$page_title = "Tambah Pangkat";
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
                        <h4 class="page-title">Tambah Pangkat</h4>
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
                    <h5 class="card-title mb-0">Form Tambah Pangkat</h5>
                    <a href="/sdm_dispersip/admin/pangkat/pangkat.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Pangkat <span class="text-danger">*</span></label>
                                <input type="text" name="nama_pangkat" class="form-control" required placeholder="Contoh: Pembina Utama Muda">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Golongan <span class="text-danger">*</span></label>
                                <input type="text" name="golongan" class="form-control" required placeholder="Contoh: IV/c">
                                <small class="text-muted">Format: Angka Romawi/Huruf (contoh: IV/c)</small>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Deskripsi atau keterangan tambahan tentang pangkat ini"></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button>
                            <a href="/sdm_dispersip/admin/pangkat/pangkat.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
