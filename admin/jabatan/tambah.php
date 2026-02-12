<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

// Process POST before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_jabatan = $_POST['nama_jabatan'];
    $eselon = $_POST['eselon'] ?: null;
    $jenis_jabatan = $_POST['jenis_jabatan'];
    $keterangan = $_POST['keterangan'] ?: null;
    
    $stmt = mysqli_prepare($koneksi, "INSERT INTO master_jabatan (nama_jabatan, eselon, jenis_jabatan, keterangan) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'ssss', $nama_jabatan, $eselon, $jenis_jabatan, $keterangan);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: /sdm_dispersip/admin/jabatan/jabatan.php?msg=" . urlencode('Data berhasil ditambah') . "&type=success");
        exit();
    }
    mysqli_stmt_close($stmt);
    $error = 'Gagal menambah data. Periksa duplikasi nama jabatan.';
}

$page_title = "Tambah Jabatan";
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
                        <h4 class="page-title">Tambah Jabatan</h4>
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
                    <h5 class="card-title mb-0">Form Tambah Jabatan</h5>
                    <a href="/sdm_dispersip/admin/jabatan/jabatan.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Jabatan <span class="text-danger">*</span></label>
                                <input type="text" name="nama_jabatan" class="form-control" required placeholder="Contoh: Kepala Dinas">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Eselon</label>
                                <input type="text" name="eselon" class="form-control" placeholder="Contoh: II.b">
                                <small class="text-muted">Kosongkan jika tidak ada eselon</small>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Jenis Jabatan <span class="text-danger">*</span></label>
                                <select name="jenis_jabatan" class="form-select" required>
                                    <option value="">- Pilih Jenis -</option>
                                    <option value="Struktural">Struktural</option>
                                    <option value="Fungsional">Fungsional</option>
                                    <option value="Pelaksana">Pelaksana</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Deskripsi atau keterangan tambahan tentang jabatan ini"></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button>
                            <a href="/sdm_dispersip/admin/jabatan/jabatan.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
