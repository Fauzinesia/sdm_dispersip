<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

// Get filter parameters
$f_periode = $_GET['f_periode'] ?? date('Y-m');
$f_pegawai = $_GET['f_pegawai'] ?? '';

// Query rekap absensi
$query = "SELECT 
            p.pegawai_id,
            p.nip,
            p.nama_lengkap,
            mj.nama_jabatan,
            COUNT(*) as total_hari,
            SUM(CASE WHEN a.status_absensi = 'Hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN a.status_absensi = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN a.status_absensi = 'Tidak Hadir' THEN 1 ELSE 0 END) as tidak_hadir,
            SUM(CASE WHEN a.status_absensi = 'Izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN a.status_absensi = 'Sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN a.status_absensi = 'Cuti' THEN 1 ELSE 0 END) as cuti
          FROM pegawai p
          LEFT JOIN absensi a ON p.pegawai_id = a.pegawai_id AND DATE_FORMAT(a.tanggal, '%Y-%m') = '" . mysqli_real_escape_string($koneksi, $f_periode) . "'
          LEFT JOIN master_jabatan mj ON p.jabatan_id = mj.jabatan_id
          WHERE p.status_aktif = 'Aktif'";

if ($f_pegawai) {
    $query .= " AND p.pegawai_id = " . intval($f_pegawai);
}

$query .= " GROUP BY p.pegawai_id, p.nip, p.nama_lengkap, mj.nama_jabatan
            ORDER BY p.nama_lengkap ASC";

$result = mysqli_query($koneksi, $query);

$page_title = "Rekap Absensi Bulanan";
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
                        <h4 class="page-title">Rekap Absensi Bulanan</h4>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header border-bottom border-dashed d-flex align-items-center">
                    <h5 class="card-title mb-0">Filter Rekap</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="admin/absensi/rekap.php" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Periode (Bulan)</label>
                            <input type="month" name="f_periode" value="<?php echo htmlspecialchars($f_periode); ?>" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pegawai</label>
                            <select name="f_pegawai" class="form-select">
                                <option value="">- Semua Pegawai -</option>
                                <?php
                                $pegawaiList = mysqli_query($koneksi, "SELECT pegawai_id, nip, nama_lengkap FROM pegawai WHERE status_aktif='Aktif' ORDER BY nama_lengkap");
                                while ($peg = mysqli_fetch_assoc($pegawaiList)):
                                ?>
                                    <option value="<?php echo $peg['pegawai_id']; ?>" <?php echo ($f_pegawai == $peg['pegawai_id'])?'selected':''; ?>>
                                        <?php echo htmlspecialchars($peg['nama_lengkap'] . ' (' . $peg['nip'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button>
                            <a href="admin/absensi/rekap.php" class="btn btn-light">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Rekap Periode: <?php echo date('F Y', strtotime($f_periode . '-01')); ?></h5>
                    <div>
                        <?php
                        $cetakUrl = '/sdm_dispersip/admin/absensi/cetak_rekap.php?'
                            . 'f_periode=' . urlencode($f_periode)
                            . '&f_pegawai=' . urlencode($f_pegawai);
                        ?>
                        <a href="<?php echo $cetakUrl; ?>" target="_blank" class="btn btn-secondary">
                            <i class="ti ti-printer me-1"></i>Cetak
                        </a>
                        <a href="admin/absensi/absensi.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive-sm">
                        <table class="table table-striped table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="text-center align-middle">No</th>
                                    <th rowspan="2" class="align-middle">NIP</th>
                                    <th rowspan="2" class="align-middle">Nama Pegawai</th>
                                    <th rowspan="2" class="align-middle">Jabatan</th>
                                    <th colspan="6" class="text-center">Status Kehadiran</th>
                                    <th rowspan="2" class="text-center align-middle">Total</th>
                                    <th rowspan="2" class="text-center align-middle">% Hadir</th>
                                </tr>
                                <tr>
                                    <th class="text-center">Hadir</th>
                                    <th class="text-center">Terlambat</th>
                                    <th class="text-center">Tidak Hadir</th>
                                    <th class="text-center">Izin</th>
                                    <th class="text-center">Sakit</th>
                                    <th class="text-center">Cuti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result && mysqli_num_rows($result) > 0) {
                                    $no = 1;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $total = $row['total_hari'];
                                        $hadir = $row['hadir'];
                                        $persen = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;
                                        
                                        echo '<tr>';
                                        echo '<td class="text-center">' . $no++ . '</td>';
                                        echo '<td>' . htmlspecialchars($row['nip'] ?? '-') . '</td>';
                                        echo '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['nama_jabatan'] ?? '-') . '</td>';
                                        echo '<td class="text-center"><span class="badge bg-success">' . $row['hadir'] . '</span></td>';
                                        echo '<td class="text-center"><span class="badge bg-warning">' . $row['terlambat'] . '</span></td>';
                                        echo '<td class="text-center"><span class="badge bg-danger">' . $row['tidak_hadir'] . '</span></td>';
                                        echo '<td class="text-center"><span class="badge bg-info">' . $row['izin'] . '</span></td>';
                                        echo '<td class="text-center"><span class="badge bg-warning">' . $row['sakit'] . '</span></td>';
                                        echo '<td class="text-center"><span class="badge bg-primary">' . $row['cuti'] . '</span></td>';
                                        echo '<td class="text-center"><strong>' . $total . '</strong></td>';
                                        
                                        $badgeColor = $persen >= 90 ? 'success' : ($persen >= 75 ? 'warning' : 'danger');
                                        echo '<td class="text-center"><span class="badge bg-' . $badgeColor . ' fs-6">' . $persen . '%</span></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="12" class="text-center">Tidak ada data</td></tr>';
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

<style>
@media print {
    .page-title-box, .btn, .card-header .btn-group, .no-print { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    table { font-size: 10pt !important; }
}
</style>

<?php include '../../includes/footer.php'; ?>
