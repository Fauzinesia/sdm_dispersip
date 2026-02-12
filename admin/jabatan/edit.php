<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

// Get jabatan_id
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: /sdm_dispersip/admin/jabatan/jabatan.php?msg=" . urlencode('ID tidak valid') . "&type=danger");
    exit();
}

// Get jabatan data
$query = "SELECT * FROM master_jabatan WHERE jabatan_id = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    mysqli_stmt_close($stmt);
    header("Location: /sdm_dispersip/admin/jabatan/jabatan.php?msg=" . urlencode('Data tidak ditemukan') . "&type=danger");
    exit();
}

$jabatan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Process POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_jabatan = $_POST['nama_jabatan'];
    $eselon = $_POST['eselon'] ?: null;
    $jenis_jabatan = $_POST['jenis_jabatan'];
    $keterangan = $_POST['keterangan'] ?: null;
    
    $update_query = "UPDATE master_jabatan SET nama_jabatan = ?, eselon = ?, jenis_jabatan = ?, keterangan = ? WHERE jabatan_id = ?";
    $stmt = mysqli_prepare($koneksi, $update_query);
    mysqli_stmt_bind_param($stmt, 'ssssi', $nama_jabatan, $eselon, $jenis_jabatan, $keterangan, $id);
    
    try {
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: /sdm_dispersip/admin/jabatan/jabatan.php?msg=" . urlencode('Data berhasil diubah') . "&type=success");
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        mysqli_stmt_close($stmt);
        $error = 'Gagal mengubah data: ' . $e->getMessage();
    }
}

$page_title = "Edit Jabatan";
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
                        <h4 class="page-title">Edit Jabatan</h4>
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
                    <h5 class="card-title mb-0">Form Edit Jabatan</h5>
                    <a href="/sdm_dispersip/admin/jabatan/jabatan.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Jabatan <span class="text-danger">*</span></label>
                                <input type="text" name="nama_jabatan" class="form-control" required placeholder="Contoh: Kepala Dinas" value="<?php echo htmlspecialchars($jabatan['nama_jabatan']); ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Eselon</label>
                                <input type="text" name="eselon" class="form-control" placeholder="Contoh: II.b" value="<?php echo htmlspecialchars($jabatan['eselon'] ?? ''); ?>">
                                <small class="text-muted">Kosongkan jika tidak ada eselon</small>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Jenis Jabatan <span class="text-danger">*</span></label>
                                <select name="jenis_jabatan" class="form-select" required>
                                    <option value="">- Pilih Jenis -</option>
                                    <option value="Struktural" <?php echo ($jabatan['jenis_jabatan'] == 'Struktural') ? 'selected' : ''; ?>>Struktural</option>
                                    <option value="Fungsional" <?php echo ($jabatan['jenis_jabatan'] == 'Fungsional') ? 'selected' : ''; ?>>Fungsional</option>
                                    <option value="Pelaksana" <?php echo ($jabatan['jenis_jabatan'] == 'Pelaksana') ? 'selected' : ''; ?>>Pelaksana</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Deskripsi atau keterangan tambahan tentang jabatan ini"><?php echo htmlspecialchars($jabatan['keterangan'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Perubahan</button>
                            <a href="/sdm_dispersip/admin/jabatan/jabatan.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
