<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$page_title = "Data Pegawai";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <?php
            $allowedSort = [
                'nik' => 'p.nik',
                'nip' => 'p.nip',
                'nama_lengkap' => 'p.nama_lengkap',
                'jk' => 'p.jk',
                'status_kepegawaian' => 'p.status_kepegawaian',
                'jabatan' => 'mj.nama_jabatan',
                'pangkat' => 'mp.nama_pangkat',
                'status_aktif' => 'p.status_aktif',
                'tgl_mulai_kerja' => 'p.tgl_mulai_kerja'
            ];
            $sort = $_GET['sort'] ?? 'nama_lengkap';
            $order = strtolower($_GET['order'] ?? 'asc');
            $order = $order === 'desc' ? 'DESC' : 'ASC';
            $sortCol = $allowedSort[$sort] ?? $allowedSort['nama_lengkap'];
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            $msg = $_GET['msg'] ?? '';
            $type = $_GET['type'] ?? 'success';
            if ($msg) {
                echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($msg) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            ?>
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Data Pegawai</h4>
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="card mb-3">
                <div class="card-header border-bottom border-dashed d-flex align-items-center">
                    <h5 class="card-title mb-0">Filter Data</h5>
                </div>
                <div class="card-body">
                    <?php
                    // collect filter inputs
                    $f_status = $_GET['f_status'] ?? '';
                    $f_aktif = $_GET['f_aktif'] ?? '';
                    $f_jabatan = isset($_GET['f_jabatan']) ? intval($_GET['f_jabatan']) : 0;
                    $f_pangkat = isset($_GET['f_pangkat']) ? intval($_GET['f_pangkat']) : 0;
                    $f_from = $_GET['f_from'] ?? '';
                    $f_to = $_GET['f_to'] ?? '';
                    $allowedStatus = ['','PNS','PPPK','Honorer','Kontrak'];
                    $allowedAktif = ['','Aktif','Pensiun','Pindah','Meninggal','Nonaktif'];
                    if (!in_array($f_status, $allowedStatus, true)) $f_status = '';
                    if (!in_array($f_aktif, $allowedAktif, true)) $f_aktif = '';
                    $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
                    if ($f_from && !preg_match($dateRegex, $f_from)) $f_from = '';
                    if ($f_to && !preg_match($dateRegex, $f_to)) $f_to = '';
                    // options for selects
                    $opt_jabatan = mysqli_query($koneksi, "SELECT jabatan_id, nama_jabatan FROM master_jabatan ORDER BY nama_jabatan");
                    $opt_pangkat = mysqli_query($koneksi, "SELECT pangkat_id, nama_pangkat FROM master_pangkat ORDER BY nama_pangkat");
                    ?>
                    <form method="get" action="admin/pegawai/pegawai.php" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status Kepegawaian</label>
                            <select name="f_status" class="form-select">
                                <?php foreach ($allowedStatus as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo ($f_status===$st)?'selected':''; ?>><?php echo $st ?: '- Semua -'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status Aktif</label>
                            <select name="f_aktif" class="form-select">
                                <?php foreach ($allowedAktif as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo ($f_aktif===$st)?'selected':''; ?>><?php echo $st ?: '- Semua -'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Jabatan</label>
                            <select name="f_jabatan" class="form-select">
                                <option value="0">- Semua -</option>
                                <?php while ($j = mysqli_fetch_assoc($opt_jabatan)): ?>
                                    <option value="<?php echo htmlspecialchars($j['jabatan_id']); ?>" <?php echo ($f_jabatan==intval($j['jabatan_id']))?'selected':''; ?>><?php echo htmlspecialchars($j['nama_jabatan']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Pangkat</label>
                            <select name="f_pangkat" class="form-select">
                                <option value="0">- Semua -</option>
                                <?php while ($p = mysqli_fetch_assoc($opt_pangkat)): ?>
                                    <option value="<?php echo htmlspecialchars($p['pangkat_id']); ?>" <?php echo ($f_pangkat==intval($p['pangkat_id']))?'selected':''; ?>><?php echo htmlspecialchars($p['nama_pangkat']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tgl Mulai Kerja (dari)</label>
                            <input type="date" name="f_from" value="<?php echo htmlspecialchars($f_from); ?>" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tgl Mulai Kerja (sampai)</label>
                            <input type="date" name="f_to" value="<?php echo htmlspecialchars($f_to); ?>" class="form-control">
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                            <a href="admin/pegawai/pegawai.php" class="btn btn-light">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <div>
                        <a href="admin/pegawai/tambah.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah Pegawai</a>
                        <?php
                        // Build cetak URL with current filters
                        $cj = ($f_jabatan > 0) ? (string)$f_jabatan : '';
                        $cp = ($f_pangkat > 0) ? (string)$f_pangkat : '';
                        $cetakUrl = 'admin/pegawai/cetak.php?'
                            . 'f_status=' . urlencode($f_aktif)
                            . '&f_jabatan=' . urlencode($cj)
                            . '&f_pangkat=' . urlencode($cp)
                            . '&f_kepegawaian=' . urlencode($f_status);
                        ?>
                        <a href="<?php echo $cetakUrl; ?>" target="_blank" class="btn btn-secondary"><i class="ti ti-printer me-1"></i>Cetak</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive-sm">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th><a class="link-reset" href="admin/pegawai/pegawai.php?sort=nip&order=<?php echo ($sort==='nip' && $order==='ASC')?'desc':'asc'; ?>">NIP<?php echo $sort==='nip' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/pegawai/pegawai.php?sort=nama_lengkap&order=<?php echo ($sort==='nama_lengkap' && $order==='ASC')?'desc':'asc'; ?>">Nama Lengkap<?php echo $sort==='nama_lengkap' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/pegawai/pegawai.php?sort=jk&order=<?php echo ($sort==='jk' && $order==='ASC')?'desc':'asc'; ?>">JK<?php echo $sort==='jk' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/pegawai/pegawai.php?sort=status_kepegawaian&order=<?php echo ($sort==='status_kepegawaian' && $order==='ASC')?'desc':'asc'; ?>">Status<?php echo $sort==='status_kepegawaian' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/pegawai/pegawai.php?sort=jabatan&order=<?php echo ($sort==='jabatan' && $order==='ASC')?'desc':'asc'; ?>">Jabatan<?php echo $sort==='jabatan' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/pegawai/pegawai.php?sort=pangkat&order=<?php echo ($sort==='pangkat' && $order==='ASC')?'desc':'asc'; ?>">Pangkat<?php echo $sort==='pangkat' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/pegawai/pegawai.php?sort=nik&order=<?php echo ($sort==='nik' && $order==='ASC')?'desc':'asc'; ?>">NIK<?php echo $sort==='nik' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/pegawai/pegawai.php?sort=status_aktif&order=<?php echo ($sort==='status_aktif' && $order==='ASC')?'desc':'asc'; ?>">Status Aktif<?php echo $sort==='status_aktif' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/pegawai/pegawai.php?sort=tgl_mulai_kerja&order=<?php echo ($sort==='tgl_mulai_kerja' && $order==='ASC')?'desc':'asc'; ?>">Tgl Mulai Kerja<?php echo $sort==='tgl_mulai_kerja' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // build where clauses from filters
                                $where = [];
                                if ($f_status) { $where[] = "p.status_kepegawaian = '".mysqli_real_escape_string($koneksi,$f_status)."'"; }
                                if ($f_aktif) { $where[] = "p.status_aktif = '".mysqli_real_escape_string($koneksi,$f_aktif)."'"; }
                                if ($f_jabatan > 0) { $where[] = "p.jabatan_id = ".$f_jabatan; }
                                if ($f_pangkat > 0) { $where[] = "p.pangkat_id = ".$f_pangkat; }
                                if ($f_from) { $where[] = "p.tgl_mulai_kerja >= '".$f_from."'"; }
                                if ($f_to) { $where[] = "p.tgl_mulai_kerja <= '".$f_to."'"; }
                                $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
                                $total = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM pegawai p $whereSql"))['c'];
                                $sql = "SELECT p.*, mj.nama_jabatan, mp.nama_pangkat
                                        FROM pegawai p
                                        LEFT JOIN master_jabatan mj ON p.jabatan_id = mj.jabatan_id
                                        LEFT JOIN master_pangkat mp ON p.pangkat_id = mp.pangkat_id
                                        $whereSql
                                        ORDER BY $sortCol $order
                                        LIMIT $perPage OFFSET $offset";
                                $res = mysqli_query($koneksi, $sql);
                                if ($res && mysqli_num_rows($res) > 0) {
                                    $no = $offset + 1;
                                    while ($row = mysqli_fetch_assoc($res)) {
                                        $jk = $row['jk'] === 'L' ? 'Laki-laki' : 'Perempuan';
                                        $status = $row['status_kepegawaian'];
                                        $jabatan = $row['nama_jabatan'] ?? '-';
                                        $pangkat = $row['nama_pangkat'] ?? '-';
                                        $status_aktif = $row['status_aktif'];
                                        $tgl_mulai = $row['tgl_mulai_kerja'] ? date('d/m/Y', strtotime($row['tgl_mulai_kerja'])) : '-';
                                        echo '<tr>' .
                                            '<td>' . $no++ . '</td>' .
                                            '<td>' . htmlspecialchars($row['nip'] ?? '') . '</td>' .
                                            '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>' .
                                            '<td>' . htmlspecialchars($jk) . '</td>' .
                                            '<td>' . htmlspecialchars($status) . '</td>' .
                                            '<td>' . htmlspecialchars($jabatan) . '</td>' .
                                            '<td>' . htmlspecialchars($pangkat) . '</td>' .
                                            '<td>' . htmlspecialchars($row['nik']) . '</td>' .
                                            '<td><span class="badge bg-' . ($status_aktif === 'Aktif' ? 'success' : 'secondary') . '">' . htmlspecialchars($status_aktif) . '</span></td>' .
                                            '<td>' . htmlspecialchars($tgl_mulai) . '</td>' .
                                            '<td><div class="d-flex flex-wrap gap-1">' .
                                                '<a href="admin/pegawai/detail.php?id=' . htmlspecialchars($row['pegawai_id']) . '" class="btn btn-sm btn-info"><i class="ti ti-eye me-1"></i>Detail</a>' .
                                                '<a href="admin/pegawai/edit.php?id=' . htmlspecialchars($row['pegawai_id']) . '" class="btn btn-sm btn-warning"><i class="ti ti-pencil me-1"></i>Edit</a>' .
                                                '<a href="admin/pegawai/hapus.php?id=' . htmlspecialchars($row['pegawai_id']) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Hapus data ini?\')"><i class="ti ti-trash me-1"></i>Hapus</a>' .
                                            '</div></td>' .
                                        '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="11" class="text-center">Tidak ada data pegawai</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                    $totalPages = (int)ceil($total / $perPage);
                    if ($totalPages > 1):
                        $baseQS = 'admin/pegawai/pegawai.php?sort=' . urlencode($sort) . '&order=' . strtolower($order)
                            . '&f_status=' . urlencode($f_status)
                            . '&f_aktif=' . urlencode($f_aktif)
                            . '&f_jabatan=' . urlencode((string)$f_jabatan)
                            . '&f_pangkat=' . urlencode((string)$f_pangkat)
                            . '&f_from=' . urlencode($f_from)
                            . '&f_to=' . urlencode($f_to);
                    ?>
                    <nav aria-label="Page navigation" class="mt-3">
                        <ul class="pagination mb-0">
                            <li class="page-item <?php echo ($page<=1)?'disabled':''; ?>">
                                <a class="page-link" href="<?php echo $baseQS . '&page=' . max(1, $page-1); ?>">Prev</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i===$page)?'active':''; ?>">
                                <a class="page-link" href="<?php echo $baseQS . '&page=' . $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page>=$totalPages)?'disabled':''; ?>">
                                <a class="page-link" href="<?php echo $baseQS . '&page=' . min($totalPages, $page+1); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="card mt-3">
                <div class="card-header border-bottom border-dashed d-flex align-items-center">
                    <h5 class="card-title mb-0">Distribusi Status Kepegawaian (hasil filter)</h5>
                </div>
                <div class="card-body">
                    <?php
                    $chartQuery = "SELECT status_kepegawaian AS label, COUNT(*) AS jumlah FROM pegawai p $whereSql GROUP BY status_kepegawaian";
                    $chartRes = mysqli_query($koneksi, $chartQuery);
                    $labels = [];
                    $values = [];
                    if ($chartRes) {
                        while ($cr = mysqli_fetch_assoc($chartRes)) {
                            $labels[] = $cr['label'];
                            $values[] = (int)$cr['jumlah'];
                        }
                    }
                    ?>
                    <div class="row">
                        <div class="col-12">
                            <canvas id="pegawaiChart" style="max-height: 360px;"></canvas>
                        </div>
                    </div>
                    <?php if (array_sum($values) === 0): ?>
                        <div class="text-muted mt-2">Tidak ada data sesuai filter.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
// render chart after assets loaded
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('pegawaiChart');
    if (!ctx || typeof Chart === 'undefined') { return; }
    var labels = <?php echo json_encode($labels); ?>;
    var values = <?php echo json_encode($values); ?>;
    var data = {
        labels: labels,
        datasets: [{
            label: 'Jumlah Pegawai',
            data: values,
            backgroundColor: ['#3b82f6','#22c55e','#f59e0b','#ef4444'],
            borderColor: '#0ea5e9',
            borderWidth: 1
        }]
    };
    var chart = new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: { enabled: true }
            },
            scales: {
                x: { title: { display: true, text: 'Status Kepegawaian' } },
                y: { beginAtZero: true, title: { display: true, text: 'Jumlah' } }
            }
        }
    });
});
</script>
