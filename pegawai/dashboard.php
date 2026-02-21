<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
require_once '../config/koneksi.php';
$page_title = "Dashboard Pegawai";
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';
?>

<style>
/* Mobile-First Dashboard Styles */
.bg-gradient-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
}

.hover-card {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    border-color: #0d6efd;
}

.icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .page-title {
        font-size: 1.5rem;
    }
    
    .card-body {
        padding: 1rem !important;
    }
    
    .hover-card .card-body {
        padding: 0.75rem !important;
    }
    
    .icon-circle {
        width: 50px;
        height: 50px;
    }
    
    .icon-circle i {
        font-size: 1.5rem !important;
    }
}

/* Smooth animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.5s ease;
}
</style>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Dashboard Pegawai</h4>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title">Selamat Datang, <?php echo $_SESSION['nama_lengkap'] ?? $_SESSION['username']; ?>!</h4>
                            <p class="text-muted">Berikut ringkasan aktivitas Anda.</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $pegawai_id = $_SESSION['pegawai_id'] ?? 0;
            $can_absen = false;
            $today_absensi = null;
            $today_work_duration = '-';
            
            if ($pegawai_id) {
                $stmt = mysqli_prepare($koneksi, "SELECT status_kepegawaian FROM pegawai WHERE pegawai_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $pegawai_id);
                mysqli_stmt_execute($stmt);
                $pegawai_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);
                
                $allowed_status = ['Honorer', 'Kontrak'];
                $can_absen = in_array($pegawai_data['status_kepegawaian'] ?? '', $allowed_status);
                
                $today = date('Y-m-d');
                $stmt = mysqli_prepare($koneksi, "SELECT * FROM absensi WHERE pegawai_id=? AND tanggal=?");
                mysqli_stmt_bind_param($stmt, 'is', $pegawai_id, $today);
                mysqli_stmt_execute($stmt);
                $today_absensi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);
                
                if ($today_absensi && !empty($today_absensi['jam_masuk']) && !empty($today_absensi['jam_pulang'])) {
                    $start = strtotime($today_absensi['jam_masuk']);
                    $end = strtotime($today_absensi['jam_pulang']);
                    if ($start && $end && $end > $start) {
                        $diff = $end - $start;
                        $hours = floor($diff / 3600);
                        $minutes = floor(($diff % 3600) / 60);
                        if ($hours > 0 && $minutes > 0) {
                            $today_work_duration = $hours . ' jam ' . $minutes . ' menit';
                        } elseif ($hours > 0) {
                            $today_work_duration = $hours . ' jam';
                        } elseif ($minutes > 0) {
                            $today_work_duration = $minutes . ' menit';
                        }
                    }
                }
            }
            ?>

            <!-- Quick Access: Absensi -->
            <?php if ($can_absen): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card bg-gradient-primary text-white shadow-lg">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8 col-12 mb-3 mb-md-0">
                                    <h5 class="text-white mb-2"><i class="ti ti-clock me-2"></i>Absensi Hari Ini</h5>
                                    <?php if ($today_absensi): ?>
                                        <div class="d-flex flex-wrap gap-3 mb-2">
                                            <div>
                                                <small class="text-white-50">Jam Masuk</small>
                                                <p class="mb-0 fw-bold"><?php echo date('H:i', strtotime($today_absensi['jam_masuk'])); ?></p>
                                            </div>
                                            <?php if ($today_absensi['jam_pulang']): ?>
                                                <div>
                                                    <small class="text-white-50">Jam Pulang</small>
                                                    <p class="mb-0 fw-bold"><?php echo date('H:i', strtotime($today_absensi['jam_pulang'])); ?></p>
                                                </div>
                                                <div>
                                                    <small class="text-white-50">Total Jam Kerja</small>
                                                    <p class="mb-0 fw-bold"><?php echo $today_work_duration; ?></p>
                                                </div>
                                            <?php else: ?>
                                                <div>
                                                    <small class="text-white-50">Jam Pulang</small>
                                                    <p class="mb-0 text-warning fw-bold">Belum absen</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge bg-light text-dark">
                                            <i class="ti ti-check-circle me-1"></i><?php echo $today_absensi['status_absensi']; ?>
                                        </span>
                                    <?php else: ?>
                                        <p class="mb-0 fs-5">⚠️ Anda belum melakukan absensi hari ini</p>
                                        <small class="text-white-50">Segera lakukan absensi sebelum terlambat!</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 col-12 text-md-end text-center">
                                    <a href="pegawai/absensi.php" class="btn btn-light btn-lg shadow-sm w-100 w-md-auto">
                                        <i class="ti ti-fingerprint me-2"></i>
                                        <?php if (!$today_absensi): ?>
                                            <strong>Absen Sekarang</strong>
                                        <?php elseif (!$today_absensi['jam_pulang']): ?>
                                            <strong>Absen Pulang</strong>
                                        <?php else: ?>
                                            Lihat Detail
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions (Mobile-Friendly) -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <a href="pegawai/cuti.php" class="text-decoration-none">
                        <div class="card hover-card text-center">
                            <div class="card-body p-3">
                                <div class="icon-circle bg-primary-subtle mb-2">
                                    <i class="ti ti-calendar-event text-primary fs-3"></i>
                                </div>
                                <h6 class="mb-0 text-dark">Cuti</h6>
                                <small class="text-muted">Pengajuan</small>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="pegawai/penilaian_kinerja.php" class="text-decoration-none">
                        <div class="card hover-card text-center">
                            <div class="card-body p-3">
                                <div class="icon-circle bg-success-subtle mb-2">
                                    <i class="ti ti-chart-bar text-success fs-3"></i>
                                </div>
                                <h6 class="mb-0 text-dark">Kinerja</h6>
                                <small class="text-muted">Penilaian</small>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="pegawai/arsip.php" class="text-decoration-none">
                        <div class="card hover-card text-center">
                            <div class="card-body p-3">
                                <div class="icon-circle bg-warning-subtle mb-2">
                                    <i class="ti ti-file-text text-warning fs-3"></i>
                                </div>
                                <h6 class="mb-0 text-dark">Arsip</h6>
                                <small class="text-muted">Dokumen</small>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="pegawai/gaji.php" class="text-decoration-none">
                        <div class="card hover-card text-center">
                            <div class="card-body p-3">
                                <div class="icon-circle bg-info-subtle mb-2">
                                    <i class="ti ti-wallet text-info fs-3"></i>
                                </div>
                                <h6 class="mb-0 text-dark">Gaji</h6>
                                <small class="text-muted">Slip Gaji</small>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed d-flex align-items-center">
                            <h4 class="header-title">Pengajuan Cuti Anda</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive-sm">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Jenis Cuti</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query_cuti = "SELECT c.* FROM cuti c WHERE 1=1";
                                        if (isset($_SESSION['pegawai_id']) && $_SESSION['pegawai_id']) {
                                            $pegawai_id = intval($_SESSION['pegawai_id']);
                                            $query_cuti .= " AND c.pegawai_id = '".$pegawai_id."'";
                                        }
                                        $query_cuti .= " ORDER BY c.created_at DESC LIMIT 10";
                                        $result_cuti = mysqli_query($koneksi, $query_cuti);
                                        if ($result_cuti && mysqli_num_rows($result_cuti) > 0):
                                            while ($cuti = mysqli_fetch_assoc($result_cuti)):
                                                $badge_class = $cuti['status'] == 'Disetujui' ? 'success' : ($cuti['status'] == 'Ditolak' ? 'danger' : 'warning');
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cuti['jenis_cuti']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($cuti['tgl_mulai'])); ?> - <?php echo date('d/m/Y', strtotime($cuti['tgl_selesai'])); ?></td>
                                            <td><span class="badge bg-<?php echo $badge_class; ?>"><?php echo htmlspecialchars($cuti['status']); ?></span></td>
                                        </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Belum ada pengajuan cuti</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
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
                                <h5><?php echo $_SESSION['nama_lengkap'] ?? '-'; ?></h5>
                            </div>
                            <div class="mb-3">
                                <p class="text-muted mb-1">Role</p>
                                <h5><span class="badge bg-primary">Pegawai</span></h5>
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

<?php include '../includes/footer.php'; ?>

