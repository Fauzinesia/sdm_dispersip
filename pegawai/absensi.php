<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'pegawai') { header("Location: ../login.php"); exit(); }
require_once '../config/koneksi.php';

$pegawai_id = $_SESSION['pegawai_id'] ?? 0;
if (!$pegawai_id) { 
    echo '<script>alert("Data pegawai tidak ditemukan!"); window.location.href="../login.php";</script>'; 
    exit(); 
}

// Get pegawai data with status_kepegawaian
$stmt = mysqli_prepare($koneksi, "SELECT p.*, u.username FROM pegawai p 
                                  LEFT JOIN users u ON p.user_id = u.user_id 
                                  WHERE p.pegawai_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $pegawai_id);
mysqli_stmt_execute($stmt);
$pegawai_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$pegawai_data) {
    echo '<script>alert("Data pegawai tidak ditemukan!"); window.location.href="../login.php";</script>'; 
    exit();
}

// Check if pegawai is Honorer or Kontrak
$allowed_status = ['Honorer', 'Kontrak'];
$can_absen = in_array($pegawai_data['status_kepegawaian'], $allowed_status);

// Helper function: Calculate distance between two GPS coordinates (Haversine formula)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

// Helper function: Upload base64 image
function uploadBase64Image($base64_string, $type, $pegawai_id) {
    $upload_dir = '../uploads/absensi/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Remove data:image/png;base64, prefix
    $image_parts = explode(";base64,", $base64_string);
    if (count($image_parts) != 2) {
        return null;
    }
    
    $image_base64 = base64_decode($image_parts[1]);
    $filename = 'foto_' . $type . '_' . $pegawai_id . '_' . date('Ymd_His') . '.jpg';
    $filepath = $upload_dir . $filename;
    
    if (file_put_contents($filepath, $image_base64)) {
        return 'uploads/absensi/' . $filename;
    }
    return null;
}

function calculateWorkDuration($jam_masuk, $jam_pulang) {
    if (!$jam_masuk || !$jam_pulang) {
        return '-';
    }
    $start = strtotime($jam_masuk);
    $end = strtotime($jam_pulang);
    if (!$start || !$end || $end <= $start) {
        return '-';
    }
    $diff = $end - $start;
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    if ($hours > 0 && $minutes > 0) {
        return $hours . ' jam ' . $minutes . ' menit';
    } elseif ($hours > 0) {
        return $hours . ' jam';
    } elseif ($minutes > 0) {
        return $minutes . ' menit';
    }
    return '-';
}

// Helper function: Check if today is a working day
function isWorkingDay($koneksi, $date = null) {
    if (!$date) $date = date('Y-m-d');
    
    // Check if weekend (Saturday = 6, Sunday = 0)
    $day_of_week = date('w', strtotime($date));
    if ($day_of_week == 0 || $day_of_week == 6) {
        return [
            'is_working' => false,
            'reason' => 'Weekend',
            'message' => 'Hari Libur (Akhir Pekan)',
            'day_name' => $day_of_week == 0 ? 'Minggu' : 'Sabtu'
        ];
    }
    
    // Check if national holiday
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM hari_libur WHERE tanggal = ? AND is_active = 1");
    mysqli_stmt_bind_param($stmt, 's', $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($holiday = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        return [
            'is_working' => false,
            'reason' => 'Holiday',
            'message' => $holiday['nama_libur'],
            'jenis' => $holiday['jenis'],
            'keterangan' => $holiday['keterangan']
        ];
    }
    
    mysqli_stmt_close($stmt);
    return ['is_working' => true];
}

// Check if today is a working day
$today_check = isWorkingDay($koneksi);
$is_holiday = !$today_check['is_working'];

// Handle form submission (hanya untuk Honorer & Kontrak dan bukan hari libur)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_absen && !$is_holiday) {
    $action = $_POST['action'] ?? '';
    
    // Office location (Dispersip Banjarmasin)
    $office_lat = -3.3295557332445473;
    $office_lng = 114.59019248133578;
    $max_radius = 50; // meters
    
    if ($action === 'absen_masuk') {
        $tanggal = date('Y-m-d');
        $jam_masuk = date('H:i:s');
        $lat_masuk = floatval($_POST['lat_masuk'] ?? 0);
        $lng_masuk = floatval($_POST['lng_masuk'] ?? 0);
        
        // Validate geofencing
        if ($lat_masuk && $lng_masuk) {
            $distance = calculateDistance($office_lat, $office_lng, $lat_masuk, $lng_masuk);
            if ($distance > $max_radius) {
                $error = 'Anda berada di luar radius kantor! Jarak: ' . round($distance) . 'm (Maks: ' . $max_radius . 'm)';
            }
        } else {
            $error = 'GPS tidak terdeteksi! Pastikan GPS aktif dan izinkan akses lokasi.';
        }
        
        if (!isset($error)) {
            // Check if already absen today
            $check = mysqli_prepare($koneksi, "SELECT absensi_id FROM absensi WHERE pegawai_id=? AND tanggal=?");
            mysqli_stmt_bind_param($check, 'is', $pegawai_id, $tanggal);
            mysqli_stmt_execute($check);
            $exists = mysqli_num_rows(mysqli_stmt_get_result($check)) > 0;
            mysqli_stmt_close($check);
            
            if ($exists) {
                $error = 'Anda sudah melakukan absen masuk hari ini!';
            } else {
                // Handle photo upload
                $foto_masuk = null;
                if (isset($_POST['foto_masuk_data']) && $_POST['foto_masuk_data']) {
                    $foto_masuk = uploadBase64Image($_POST['foto_masuk_data'], 'masuk', $pegawai_id);
                }
                
                // Determine status based on time
                $jam_kerja = '08:00:00';
                $toleransi = 15; // minutes
                $status_absensi = 'Hadir';
                
                if ($jam_masuk > date('H:i:s', strtotime($jam_kerja . ' +' . $toleransi . ' minutes'))) {
                    $status_absensi = 'Terlambat';
                }
                
                $stmt = mysqli_prepare($koneksi, "INSERT INTO absensi (pegawai_id, tanggal, jam_masuk, lat_masuk, lng_masuk, foto_masuk, status_absensi) VALUES (?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($stmt, 'issddss', $pegawai_id, $tanggal, $jam_masuk, $lat_masuk, $lng_masuk, $foto_masuk, $status_absensi);
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Absen masuk berhasil! Status: ' . $status_absensi . ' | Jarak: ' . round($distance) . 'm';
                } else {
                    $error = 'Gagal mencatat absen masuk!';
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($action === 'absen_pulang') {
        $tanggal = date('Y-m-d');
        $jam_pulang = date('H:i:s');
        $lat_pulang = floatval($_POST['lat_pulang'] ?? 0);
        $lng_pulang = floatval($_POST['lng_pulang'] ?? 0);
        
        // Validate geofencing
        if ($lat_pulang && $lng_pulang) {
            $distance = calculateDistance($office_lat, $office_lng, $lat_pulang, $lng_pulang);
            if ($distance > $max_radius) {
                $error = 'Anda berada di luar radius kantor! Jarak: ' . round($distance) . 'm (Maks: ' . $max_radius . 'm)';
            }
        } else {
            $error = 'GPS tidak terdeteksi! Pastikan GPS aktif dan izinkan akses lokasi.';
        }
        
        if (!isset($error)) {
            // Handle photo upload
            $foto_pulang = null;
            if (isset($_POST['foto_pulang_data']) && $_POST['foto_pulang_data']) {
                $foto_pulang = uploadBase64Image($_POST['foto_pulang_data'], 'pulang', $pegawai_id);
            }
            
            // Update existing absensi
            $stmt = mysqli_prepare($koneksi, "UPDATE absensi SET jam_pulang=?, lat_pulang=?, lng_pulang=?, foto_pulang=? WHERE pegawai_id=? AND tanggal=?");
            mysqli_stmt_bind_param($stmt, 'sddsis', $jam_pulang, $lat_pulang, $lng_pulang, $foto_pulang, $pegawai_id, $tanggal);
            if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                $success = 'Absen pulang berhasil! Jarak: ' . round($distance) . 'm';
            } else {
                $error = 'Belum ada absen masuk atau sudah absen pulang!';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get today's absensi
$today = date('Y-m-d');
$today_absensi = null;
$stmt = mysqli_prepare($koneksi, "SELECT * FROM absensi WHERE pegawai_id=? AND tanggal=?");
mysqli_stmt_bind_param($stmt, 'is', $pegawai_id, $today);
mysqli_stmt_execute($stmt);
$today_absensi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Get absensi history (last 30 days)
$query = "SELECT * FROM absensi WHERE pegawai_id = ? ORDER BY tanggal DESC LIMIT 30";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'i', $pegawai_id);
mysqli_stmt_execute($stmt);
$history = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Get monthly stats
$current_month = date('Y-m');
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status_absensi = 'Hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN status_absensi = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
    SUM(CASE WHEN status_absensi = 'Tidak Hadir' THEN 1 ELSE 0 END) as tidak_hadir,
    SUM(CASE WHEN status_absensi = 'Izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN status_absensi = 'Sakit' THEN 1 ELSE 0 END) as sakit,
    SUM(CASE WHEN status_absensi = 'Cuti' THEN 1 ELSE 0 END) as cuti
FROM absensi 
WHERE pegawai_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ?";
$stmt = mysqli_prepare($koneksi, $stats_query);
mysqli_stmt_bind_param($stmt, 'is', $pegawai_id, $current_month);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$page_title = "Absensi Saya";
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';
?>
<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Absensi Saya</h4>
                    </div>
                </div>
            </div>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ti ti-check me-1"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ti ti-alert-circle me-1"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (!$can_absen): ?>
            <!-- Alert untuk PNS & PPPK -->
            <div class="alert alert-info" role="alert">
                <h5 class="alert-heading"><i class="ti ti-info-circle me-2"></i>Informasi</h5>
                <p class="mb-0">Fitur absensi mandiri hanya tersedia untuk pegawai dengan status kepegawaian <strong>Honorer</strong> dan <strong>Kontrak</strong>.</p>
                <p class="mb-0 mt-2">Status kepegawaian Anda: <strong><?php echo htmlspecialchars($pegawai_data['status_kepegawaian']); ?></strong></p>
                <hr>
                <p class="mb-0"><small>Absensi Anda akan dicatat oleh admin. Silakan hubungi bagian kepegawaian untuk informasi lebih lanjut.</small></p>
            </div>
            <?php endif; ?>

            <?php if ($is_holiday): ?>
            <!-- Alert untuk Hari Libur -->
            <div class="alert alert-warning" role="alert">
                <h5 class="alert-heading">
                    <i class="ti ti-calendar-off me-2"></i>
                    <?php echo $today_check['reason'] === 'Weekend' ? 'Hari Libur (Akhir Pekan)' : 'Hari Libur Nasional'; ?>
                </h5>
                <hr>
                <p class="mb-2">
                    <strong>Tanggal:</strong> <?php echo date('d F Y'); ?> 
                    <?php if (isset($today_check['day_name'])): ?>
                        (<?php echo $today_check['day_name']; ?>)
                    <?php endif; ?>
                </p>
                <?php if ($today_check['reason'] === 'Holiday'): ?>
                    <p class="mb-2"><strong>Nama Libur:</strong> <?php echo htmlspecialchars($today_check['message']); ?></p>
                    <p class="mb-2"><strong>Jenis:</strong> <span class="badge bg-<?php echo $today_check['jenis'] === 'Nasional' ? 'danger' : ($today_check['jenis'] === 'Cuti Bersama' ? 'warning' : 'info'); ?>"><?php echo htmlspecialchars($today_check['jenis']); ?></span></p>
                    <?php if (!empty($today_check['keterangan'])): ?>
                        <p class="mb-0"><strong>Keterangan:</strong> <?php echo htmlspecialchars($today_check['keterangan']); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="mb-0">Hari ini adalah hari libur (akhir pekan). Absensi tidak diperlukan.</p>
                <?php endif; ?>
                <hr>
                <p class="mb-0"><small><i class="ti ti-info-circle me-1"></i>Fitur absensi dinonaktifkan pada hari libur. Anda dapat melihat riwayat absensi di bawah.</small></p>
            </div>
            <?php endif; ?>

            <?php if ($can_absen && !$is_holiday): ?>
            <!-- Absensi Form untuk Honorer & Kontrak -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed">
                            <h5 class="card-title mb-0"><i class="ti ti-clock me-2"></i>Absensi Hari Ini</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <p class="text-muted mb-1">Tanggal</p>
                                <h5><?php echo date('d F Y'); ?></h5>
                            </div>
                            <div class="mb-3">
                                <p class="text-muted mb-1">Waktu Sekarang</p>
                                <h5 id="currentTime"><?php echo date('H:i:s'); ?></h5>
                            </div>
                            
                                <?php if ($today_absensi): ?>
                                <div class="alert alert-success">
                                    <h6><i class="ti ti-check-circle me-1"></i>Sudah Absen Hari Ini</h6>
                                    <p class="mb-1"><strong>Jam Masuk:</strong> <?php echo date('H:i', strtotime($today_absensi['jam_masuk'])); ?></p>
                                    <?php if ($today_absensi['jam_pulang']): ?>
                                        <p class="mb-1"><strong>Jam Pulang:</strong> <?php echo date('H:i', strtotime($today_absensi['jam_pulang'])); ?></p>
                                        <p class="mb-1"><strong>Total Jam Kerja:</strong> <?php echo calculateWorkDuration($today_absensi['jam_masuk'], $today_absensi['jam_pulang']); ?></p>
                                        <span class="badge bg-success">✓ Absensi Lengkap</span>
                                    <?php else: ?>
                                        <p class="mb-0 text-warning"><strong>Belum absen pulang</strong></p>
                                    <?php endif; ?>
                                    <p class="mb-0 mt-2"><strong>Status:</strong> <span class="badge bg-<?php echo $today_absensi['status_absensi'] === 'Hadir' ? 'success' : 'warning'; ?>"><?php echo $today_absensi['status_absensi']; ?></span></p>
                                </div>
                                
                                <?php if (!$today_absensi['jam_pulang']): ?>
                                <!-- Absen Pulang Form -->
                                <form method="post" id="formAbsenPulang">
                                    <input type="hidden" name="action" value="absen_pulang">
                                    <input type="hidden" name="lat_pulang" id="lat_pulang">
                                    <input type="hidden" name="lng_pulang" id="lng_pulang">
                                    <input type="hidden" name="foto_pulang_data" id="foto_pulang_data">
                                    
                                    <!-- Camera Preview -->
                                    <div class="mb-3">
                                        <label class="form-label">Foto Selfie Pulang</label>
                                        <video id="camera_pulang" width="100%" height="240" autoplay style="border-radius: 8px; background: #000;"></video>
                                        <canvas id="canvas_pulang" style="display:none;"></canvas>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="startCamera('pulang')"><i class="ti ti-camera me-1"></i>Buka Kamera</button>
                                            <button type="button" class="btn btn-sm btn-info" onclick="capturePhoto('pulang')"><i class="ti ti-capture me-1"></i>Ambil Foto</button>
                                        </div>
                                        <img id="preview_pulang" style="display:none; width:100%; margin-top:10px; border-radius:8px;">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning w-100" id="btnAbsenPulang">
                                        <i class="ti ti-logout me-1"></i>Absen Pulang
                                    </button>
                                </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Absen Masuk Form -->
                                <form method="post" id="formAbsenMasuk">
                                    <input type="hidden" name="action" value="absen_masuk">
                                    <input type="hidden" name="lat_masuk" id="lat_masuk">
                                    <input type="hidden" name="lng_masuk" id="lng_masuk">
                                    <input type="hidden" name="foto_masuk_data" id="foto_masuk_data">
                                    
                                    <!-- Camera Preview -->
                                    <div class="mb-3">
                                        <label class="form-label">Foto Selfie Masuk</label>
                                        <video id="camera_masuk" width="100%" height="240" autoplay style="border-radius: 8px; background: #000;"></video>
                                        <canvas id="canvas_masuk" style="display:none;"></canvas>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="startCamera('masuk')"><i class="ti ti-camera me-1"></i>Buka Kamera</button>
                                            <button type="button" class="btn btn-sm btn-info" onclick="capturePhoto('masuk')"><i class="ti ti-capture me-1"></i>Ambil Foto</button>
                                        </div>
                                        <img id="preview_masuk" style="display:none; width:100%; margin-top:10px; border-radius:8px;">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100" id="btnAbsenMasuk">
                                        <i class="ti ti-login me-1"></i>Absen Masuk
                                    </button>
                                </form>
                                <small class="text-muted mt-2 d-block">📍 Pastikan Anda berada di kantor (radius 50m)</small>
                                <small class="text-muted d-block">📷 Ambil foto selfie sebelum absen</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed">
                            <h5 class="card-title mb-0"><i class="ti ti-chart-bar me-2"></i>Statistik Bulan Ini</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="text-center p-3 bg-success-subtle rounded">
                                        <h3 class="mb-0 text-success"><?php echo $stats['hadir'] ?? 0; ?></h3>
                                        <p class="mb-0 text-muted">Hadir</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 bg-warning-subtle rounded">
                                        <h3 class="mb-0 text-warning"><?php echo $stats['terlambat'] ?? 0; ?></h3>
                                        <p class="mb-0 text-muted">Terlambat</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 bg-info-subtle rounded">
                                        <h3 class="mb-0 text-info"><?php echo $stats['izin'] ?? 0; ?></h3>
                                        <p class="mb-0 text-muted">Izin</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 bg-primary-subtle rounded">
                                        <h3 class="mb-0 text-primary"><?php echo $stats['cuti'] ?? 0; ?></h3>
                                        <p class="mb-0 text-muted">Cuti</p>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <p class="text-muted mb-1">Total Kehadiran Bulan Ini</p>
                                <h4><?php echo $stats['total'] ?? 0; ?> Hari</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Riwayat Absensi -->
            <div class="card">
                <div class="card-header border-bottom border-dashed">
                    <h5 class="card-title mb-0"><i class="ti ti-history me-2"></i>Riwayat Absensi (30 Hari Terakhir)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Pulang</th>
                                    <th>Total Jam Kerja</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($history && mysqli_num_rows($history) > 0) {
                                    $no = 1;
                                    while ($row = mysqli_fetch_assoc($history)) {
                                        $badgeMap = [
                                            'Hadir' => 'success',
                                            'Terlambat' => 'warning',
                                            'Tidak Hadir' => 'danger',
                                            'Izin' => 'info',
                                            'Sakit' => 'warning',
                                            'Cuti' => 'primary'
                                        ];
                                        $badge = $badgeMap[$row['status_absensi']] ?? 'secondary';
                                        echo '<tr>';
                                        echo '<td>' . $no++ . '</td>';
                                        echo '<td>' . date('d/m/Y', strtotime($row['tanggal'])) . '</td>';
                                        echo '<td>' . ($row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-') . '</td>';
                                        echo '<td>' . ($row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '<span class="text-muted">-</span>') . '</td>';
                                        echo '<td>' . htmlspecialchars(calculateWorkDuration($row['jam_masuk'], $row['jam_pulang'])) . '</td>';
                                        echo '<td><span class="badge bg-' . $badge . '">' . htmlspecialchars($row['status_absensi']) . '</span></td>';
                                        echo '<td>' . htmlspecialchars($row['keterangan'] ?? '-') . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center">Belum ada riwayat absensi</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Update current time
setInterval(function() {
    const now = new Date();
    document.getElementById('currentTime').textContent = now.toLocaleTimeString('id-ID');
}, 1000);

// Camera variables
let stream_masuk = null;
let stream_pulang = null;

// Start camera
async function startCamera(type) {
    try {
        const video = document.getElementById('camera_' + type);
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'user' }, 
            audio: false 
        });
        video.srcObject = stream;
        if (type === 'masuk') stream_masuk = stream;
        else stream_pulang = stream;
    } catch (error) {
        alert('Tidak dapat mengakses kamera! Pastikan izin kamera diaktifkan.');
        console.error('Camera error:', error);
    }
}

// Capture photo
function capturePhoto(type) {
    const video = document.getElementById('camera_' + type);
    const canvas = document.getElementById('canvas_' + type);
    const preview = document.getElementById('preview_' + type);
    const input = document.getElementById('foto_' + type + '_data');
    
    if (!video.srcObject) {
        alert('Silakan buka kamera terlebih dahulu!');
        return;
    }
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    const imageData = canvas.toDataURL('image/jpeg', 0.8);
    preview.src = imageData;
    preview.style.display = 'block';
    input.value = imageData;
    
    // Stop camera
    const stream = type === 'masuk' ? stream_masuk : stream_pulang;
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }
}

// Get GPS location and validate
function getLocationAndValidate(inputLatId, inputLngId, callback) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            document.getElementById(inputLatId).value = lat;
            document.getElementById(inputLngId).value = lng;
            
            // Office location
            const office_lat = -3.3295557332445473;
            const office_lng = 114.59019248133578;
            
            // Calculate distance
            const distance = calculateDistance(office_lat, office_lng, lat, lng);
            
            if (distance > 50) {
                alert('⚠️ Anda berada di luar radius kantor!\n\nJarak Anda: ' + Math.round(distance) + ' meter\nMaksimal: 50 meter\n\nSilakan datang ke kantor untuk absen.');
                return false;
            }
            
            callback();
        }, function(error) {
            alert('GPS tidak terdeteksi! Pastikan GPS aktif dan izinkan akses lokasi.\n\nError: ' + error.message);
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        });
    } else {
        alert('Browser Anda tidak mendukung GPS!');
    }
}

// Calculate distance (Haversine formula)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // meters
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// Form submit handlers
document.getElementById('formAbsenMasuk')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Check if photo is captured
    if (!document.getElementById('foto_masuk_data').value) {
        alert('📷 Silakan ambil foto selfie terlebih dahulu!');
        return;
    }
    
    // Get location and validate
    getLocationAndValidate('lat_masuk', 'lng_masuk', () => {
        this.submit();
    });
});

document.getElementById('formAbsenPulang')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Check if photo is captured
    if (!document.getElementById('foto_pulang_data').value) {
        alert('📷 Silakan ambil foto selfie terlebih dahulu!');
        return;
    }
    
    // Get location and validate
    getLocationAndValidate('lat_pulang', 'lng_pulang', () => {
        this.submit();
    });
});
</script>

<?php include '../includes/footer.php'; ?>
