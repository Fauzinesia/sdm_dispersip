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

// Get jabatan data with pegawai count
$query = "SELECT mj.*, 
          (SELECT COUNT(*) FROM pegawai WHERE jabatan_id = mj.jabatan_id) as jumlah_pegawai
          FROM master_jabatan mj 
          WHERE mj.jabatan_id = ?";
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

$page_title = "Detail Jabatan";
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
                        <h4 class="page-title">Detail Jabatan</h4>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Informasi Jabatan</h5>
                    <div>
                        <a href="/sdm_dispersip/admin/jabatan/edit.php?id=<?php echo $id; ?>" class="btn btn-warning"><i class="ti ti-pencil me-1"></i>Edit</a>
                        <a href="/sdm_dispersip/admin/jabatan/jabatan.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="200">Nama Jabatan</th>
                                    <td>: <?php echo htmlspecialchars($jabatan['nama_jabatan']); ?></td>
                                </tr>
                                <tr>
                                    <th>Eselon</th>
                                    <td>: <?php echo htmlspecialchars($jabatan['eselon'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Jenis Jabatan</th>
                                    <td>: <span class="badge bg-primary"><?php echo htmlspecialchars($jabatan['jenis_jabatan']); ?></span></td>
                                </tr>
                                <tr>
                                    <th>Jumlah Pegawai</th>
                                    <td>: <span class="badge bg-info"><?php echo $jabatan['jumlah_pegawai']; ?> Orang</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="200">Keterangan</th>
                                    <td>: <?php echo htmlspecialchars($jabatan['keterangan'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Dibuat Pada</th>
                                    <td>: <?php echo $jabatan['created_at'] ? date('d F Y, H:i', strtotime($jabatan['created_at'])) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Diubah Pada</th>
                                    <td>: <?php echo $jabatan['updated_at'] ? date('d F Y, H:i', strtotime($jabatan['updated_at'])) : '-'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($jabatan['jumlah_pegawai'] > 0): ?>
            <div class="card mt-3">
                <div class="card-header border-bottom border-dashed">
                    <h5 class="card-title mb-0">Daftar Pegawai dengan Jabatan Ini</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIP</th>
                                    <th>Nama Lengkap</th>
                                    <th>Status Kepegawaian</th>
                                    <th>Status Aktif</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pegawai_query = "SELECT pegawai_id, nip, nama_lengkap, status_kepegawaian, status_aktif 
                                                 FROM pegawai 
                                                 WHERE jabatan_id = ? 
                                                 ORDER BY nama_lengkap";
                                $stmt = mysqli_prepare($koneksi, $pegawai_query);
                                mysqli_stmt_bind_param($stmt, 'i', $id);
                                mysqli_stmt_execute($stmt);
                                $pegawai_result = mysqli_stmt_get_result($stmt);
                                
                                $no = 1;
                                while ($pegawai = mysqli_fetch_assoc($pegawai_result)):
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($pegawai['nip'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($pegawai['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($pegawai['status_kepegawaian']); ?></td>
                                    <td><span class="badge bg-<?php echo $pegawai['status_aktif'] == 'Aktif' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars($pegawai['status_aktif']); ?></span></td>
                                </tr>
                                <?php endwhile; mysqli_stmt_close($stmt); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
