<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'verifikator') { header("Location: ../admin/dashboard.php"); exit(); }
require_once '../config/koneksi.php';
$page_title = "Dashboard Verifikator";
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_status'])) {
                $id = intval($_POST['cuti_id'] ?? 0);
                $new = $_POST['new_status'] ?? '';
                $verifikator_id = intval($_SESSION['user_id']);
                $now = date('Y-m-d H:i:s');
            
                if ($id > 0 && in_array($new, ['Disetujui', 'Ditolak'], true)) {
                    if ($new === 'Disetujui') {
                        $disposisi = $_POST['disposisi'] ?? '';
                        $st = mysqli_prepare($koneksi, "UPDATE cuti SET status=?, disposisi=?, verifikator_user_id=?, verified_at=? WHERE cuti_id=?");
                        mysqli_stmt_bind_param($st, 'ssisi', $new, $disposisi, $verifikator_id, $now, $id);
                    } else {
                        $alasan = $_POST['alasan_ditolak'] ?? '';
                        $st = mysqli_prepare($koneksi, "UPDATE cuti SET status=?, alasan_ditolak=?, verifikator_user_id=?, verified_at=? WHERE cuti_id=?");
                        mysqli_stmt_bind_param($st, 'ssisi', $new, $alasan, $verifikator_id, $now, $id);
                    }
                    
                    if (mysqli_stmt_execute($st)) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Status cuti diperbarui menjadi ' . htmlspecialchars($new) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    } else {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Gagal memperbarui status<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    }
                    mysqli_stmt_close($st);
                }
            }
            $pending_cuti = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM cuti WHERE status='Menunggu'"))['total'] ?? 0;
            ?>

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Dashboard Verifikator</h4>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded">
                                            <i class="ti ti-calendar-event fs-24"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Cuti Menunggu</p>
                                    <h4 class="mb-0"><?php echo (int)$pending_cuti; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed d-flex align-items-center">
                            <h4 class="header-title">Pengajuan Cuti Menunggu Persetujuan</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive-sm">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nama Pegawai</th>
                                            <th>Jenis Cuti</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Initialize modals variable early
                                        $modals = '';
                                        $query_cuti = "SELECT c.*, p.nama_lengkap FROM cuti c JOIN pegawai p ON c.pegawai_id=p.pegawai_id WHERE c.status='Menunggu' ORDER BY c.created_at DESC LIMIT 10";
                                        $result_cuti = mysqli_query($koneksi, $query_cuti);
                                        if ($result_cuti && mysqli_num_rows($result_cuti) > 0):
                                            while ($cuti = mysqli_fetch_assoc($result_cuti)):
                                                $modalApprove = 'modalApprove'.$cuti['cuti_id'];
                                                $modalReject = 'modalReject'.$cuti['cuti_id'];
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cuti['nama_lengkap']); ?></td>
                                            <td><?php echo htmlspecialchars($cuti['jenis_cuti']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($cuti['tgl_mulai'])); ?> - <?php echo date('d/m/Y', strtotime($cuti['tgl_selesai'])); ?></td>
                                            <td><span class="badge bg-warning">Menunggu</span></td>
                                            <td>
                                                <a href="admin/cuti/detail.php?id=<?php echo htmlspecialchars($cuti['cuti_id']); ?>" class="btn btn-sm btn-info me-1"><i class="ti ti-eye me-1"></i>Detail</a>
                                                <button type="button" class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#<?php echo $modalApprove; ?>"><i class="ti ti-check me-1"></i>Setujui</button>
                                                <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#<?php echo $modalReject; ?>"><i class="ti ti-x me-1"></i>Tolak</button>
                                            </td>
                                        </tr>
                                        <?php 
                                            // Modal Approve
                                            $modals .= '<div class="modal fade" id="'.$modalApprove.'" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <form method="post" action="" class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Setujui Cuti</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="cuti_id" value="'.htmlspecialchars($cuti['cuti_id']).'">
                                                            <input type="hidden" name="new_status" value="Disetujui">
                                                            <div class="mb-3">
                                                                <label class="form-label">Disposisi / Catatan Persetujuan</label>
                                                                <textarea name="disposisi" class="form-control" rows="3" placeholder="Masukkan disposisi atau catatan jika ada..."></textarea>
                                                            </div>
                                                            <p>Apakah Anda yakin ingin menyetujui pengajuan cuti ini?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="set_status" class="btn btn-success">Ya, Setujui</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>';

                                            // Modal Reject
                                            $modals .= '<div class="modal fade" id="'.$modalReject.'" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <form method="post" action="" class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Tolak Cuti</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="cuti_id" value="'.htmlspecialchars($cuti['cuti_id']).'">
                                                            <input type="hidden" name="new_status" value="Ditolak">
                                                            <div class="mb-3">
                                                                <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                                                                <textarea name="alasan_ditolak" class="form-control" rows="3" placeholder="Wajib diisi..." required></textarea>
                                                            </div>
                                                            <p>Apakah Anda yakin ingin menolak pengajuan cuti ini?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="set_status" class="btn btn-danger">Ya, Tolak</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>';
                                            endwhile;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Tidak ada pengajuan menunggu</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Modals moved to footer area -->
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed d-flex align-items-center">
                            <h4 class="header-title">Informasi Akun</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <p class="text-muted mb-1">Nama Lengkap</p>
                                <h5><?php echo $_SESSION['nama_lengkap'] ?? $_SESSION['username']; ?></h5>
                            </div>
                            <div class="mb-3">
                                <p class="text-muted mb-1">Role</p>
                                <h5><span class="badge bg-primary">Verifikator</span></h5>
                            </div>
                            <div class="mb-3">
                                <p class="text-muted mb-1">Login Terakhir</p>
                                <h5><?php echo date('d F Y, H:i'); ?></h5>
                            </div>
                            <hr>
                            <div class="text-center">
                                <p class="text-muted mb-2">Dinas Perpustakaan dan Kearsipan</p>
                                <p class="text-muted mb-0">Kota Banjarmasin</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php 
// Echo modals here to prevent z-index issues and ensure they are defined
echo $modals; 
?>

<?php include '../includes/footer.php'; ?>

