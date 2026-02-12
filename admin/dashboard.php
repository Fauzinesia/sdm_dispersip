<?php
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Role check removed to allow all users to access this dashboard
// Content will be filtered based on role

// Include koneksi
require_once '../config/koneksi.php';

// Set page title
$page_title = "Dashboard";

// Query untuk statistik (Hanya untuk Admin & Verifikator)
if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'verifikator') {
    $total_pegawai = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pegawai WHERE status_aktif = 'Aktif'"))['total'];
    $total_cuti_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM cuti WHERE status = 'Menunggu'"))['total'];
    $total_users = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE status = 'Aktif'"))['total'];
    $total_jabatan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM master_jabatan"))['total'];
}

// Include header
include '../includes/header.php';
?>

<!-- Sidebar -->
<?php include '../includes/sidebar.php'; ?>

<!-- Navbar -->
<?php include '../includes/navbar.php'; ?>

<!-- Content Start -->
<div class="page-container">

    <!-- Page Content Start -->
    <div class="page-content">

        <div class="container-xxl">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Dashboard</h4>
                    </div>
                </div>
            </div>

            <!-- Stats Cards (Admin & Verifikator Only) -->
            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'verifikator'): ?>
            <div class="row">
                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded">
                                            <i class="ti ti-users fs-24"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Total Pegawai</p>
                                    <h4 class="mb-0"><?php echo $total_pegawai; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                                    <p class="text-muted mb-1">Cuti Pending</p>
                                    <h4 class="mb-0"><?php echo $total_cuti_pending; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-success-subtle text-success rounded">
                                            <i class="ti ti-user-shield fs-24"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Total User</p>
                                    <h4 class="mb-0"><?php echo $total_users; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-info-subtle text-info rounded">
                                            <i class="ti ti-briefcase fs-24"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Total Jabatan</p>
                                    <h4 class="mb-0"><?php echo $total_jabatan; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title">Selamat Datang, <?php echo $_SESSION['nama_lengkap']; ?>!</h4>
                            <p class="text-muted">Selamat datang di Sistem Manajemen Kepegawaian Dispersip Banjarmasin.</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed d-flex align-items-center">
                            <h4 class="header-title">Pengajuan Cuti Terbaru</h4>
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query_cuti = "SELECT c.*, p.nama_lengkap 
                                                      FROM cuti c 
                                                      JOIN pegawai p ON c.pegawai_id = p.pegawai_id";
                                        
                                        // Filter untuk pegawai (hanya lihat data sendiri)
                                        if ($_SESSION['role'] == 'pegawai') {
                                            $pegawai_id = $_SESSION['pegawai_id'];
                                            $query_cuti .= " WHERE c.pegawai_id = '$pegawai_id'";
                                        }
                                        
                                        $query_cuti .= " ORDER BY c.created_at DESC LIMIT 5";
                                        
                                        $result_cuti = mysqli_query($koneksi, $query_cuti);
                                        
                                        if (mysqli_num_rows($result_cuti) > 0):
                                            while ($cuti = mysqli_fetch_assoc($result_cuti)):
                                                $badge_class = $cuti['status'] == 'Disetujui' ? 'success' : ($cuti['status'] == 'Ditolak' ? 'danger' : 'warning');
                                        ?>
                                        <tr>
                                            <td><?php echo $cuti['nama_lengkap']; ?></td>
                                            <td><?php echo $cuti['jenis_cuti']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($cuti['tgl_mulai'])); ?> - <?php echo date('d/m/Y', strtotime($cuti['tgl_selesai'])); ?></td>
                                            <td><span class="badge bg-<?php echo $badge_class; ?>"><?php echo $cuti['status']; ?></span></td>
                                        </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Tidak ada data cuti</td>
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
                            <h4 class="header-title">Informasi Sistem</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <p class="text-muted mb-1">Nama Lengkap</p>
                                <h5><?php echo $_SESSION['nama_lengkap'] ?? $_SESSION['username']; ?></h5>
                            </div>
                            <div class="mb-3">
                                <p class="text-muted mb-1">Role</p>
                                <h5><span class="badge bg-primary"><?php echo strtoupper($_SESSION['role']); ?></span></h5>
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

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="d-flex card-header justify-content-between align-items-center border-bottom border-dashed">
                            <h4 class="header-title">Total Revenue</h4>
                        </div>
                        <div class="card-body p-0 pt-1">
                            <div dir="ltr" class="px-2">
                                <div id="revenue-chart" class="apex-charts" data-colors="#1478f0,#f83f32,#30cf46"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom border-dashed">
                            <h4 class="header-title">Cuti Statistics</h4>
                        </div>
                        <div class="card-body pt-2">
                            <div dir="ltr">
                                <div id="data-visits-chart" class="apex-charts" data-colors="#1478f0,#faae37,#30cf46,#4bbee1"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- container-xxl -->

    </div> <!-- page-content -->

</div> <!-- page-container -->

<?php
// Build chart data from database
$months = [];
$revTotals = [];
$cutiCounts = [];
$now = new DateTime('now');
for ($i = 11; $i >= 0; $i--) {
    $m = clone $now; $m->modify('-'.$i.' months');
    $months[] = $m->format('Y-m');
}
$minMonth = $months[0];
$maxMonth = end($months);
$minDate = $months[0].'-01';
$maxDate = (new DateTime(end($months).'-01'))->modify('last day of this month')->format('Y-m-d');
$gajiMap = [];
$qGaji = mysqli_query($koneksi, "SELECT periode, SUM(total_gaji) AS total FROM gaji WHERE periode >= '".mysqli_real_escape_string($koneksi,$minMonth)."' AND periode <= '".mysqli_real_escape_string($koneksi,$maxMonth)."' GROUP BY periode");
if ($qGaji) { while ($r = mysqli_fetch_assoc($qGaji)) { $gajiMap[$r['periode']] = (float)$r['total']; } }
$cutiMap = [];
$qCuti = mysqli_query($koneksi, "SELECT DATE_FORMAT(tgl_mulai, '%Y-%m') AS bulan, COUNT(*) AS total FROM cuti WHERE tgl_mulai >= '".mysqli_real_escape_string($koneksi,$minDate)."' AND tgl_mulai <= '".mysqli_real_escape_string($koneksi,$maxDate)."' GROUP BY bulan");
if ($qCuti) { while ($r = mysqli_fetch_assoc($qCuti)) { $cutiMap[$r['bulan']] = (int)$r['total']; } }
foreach ($months as $m) { $revTotals[] = isset($gajiMap[$m]) ? (float)$gajiMap[$m] : 0; $cutiCounts[] = isset($cutiMap[$m]) ? (int)$cutiMap[$m] : 0; }
$donutLabels = [];
$donutValues = [];
$qDonut = mysqli_query($koneksi, "SELECT jenis_cuti AS label, COUNT(*) AS total FROM cuti GROUP BY jenis_cuti");
if ($qDonut) { while ($r = mysqli_fetch_assoc($qDonut)) { $donutLabels[] = $r['label']; $donutValues[] = (int)$r['total']; } }

// Extra JS for charts from index.html theme
$extra_js = '<script src="assets/vendor/apexcharts/apexcharts.min.js"></script>'
    . '<script>'
    . 'document.addEventListener("DOMContentLoaded",function(){'
    . 'var months=' . json_encode($months) . ';'
    . 'var totalGaji=' . json_encode($revTotals) . ';'
    . 'var totalCuti=' . json_encode($cutiCounts) . ';'
    . 'var revenueOptions={'
        . 'series:[{name:"Total Gaji",type:"column",data:totalGaji},{name:"Jumlah Cuti",type:"line",data:totalCuti}],'
        . 'chart:{height:320,type:"line",toolbar:{show:false}},'
        . 'stroke:{width:[0,3],curve:"smooth"},'
        . 'dataLabels:{enabled:false},'
        . 'colors:["#30cf46","#1478f0"],'
        . 'xaxis:{categories:months},'
        . 'yaxis:[{title:{text:"Gaji"}},{opposite:true,title:{text:"Cuti"}}]'
    . '};'
    . 'var rev=new ApexCharts(document.querySelector("#revenue-chart"),revenueOptions); rev.render();'
    . 'var donutOptions={'
        . 'series:' . json_encode($donutValues) . ',labels:' . json_encode($donutLabels) . ',chart:{type:"donut",height:300},colors:["#1478f0","#faae37","#30cf46","#4bbee1","#ef4444"],legend:{position:"bottom"}};'
    . 'var donut=new ApexCharts(document.querySelector("#data-visits-chart"),donutOptions); donut.render();'
    . '});' . '</script>';
// Include footer
include '../includes/footer.php';
?>
