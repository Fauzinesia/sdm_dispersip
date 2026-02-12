<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$page_title = "Data Jabatan";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <?php
            $allowedSort = [
                'nama_jabatan' => 'mj.nama_jabatan',
                'eselon' => 'mj.eselon',
                'jenis_jabatan' => 'mj.jenis_jabatan',
                'created_at' => 'mj.created_at',
                'updated_at' => 'mj.updated_at'
            ];
            $sort = $_GET['sort'] ?? 'nama_jabatan';
            $order = strtolower($_GET['order'] ?? 'asc');
            $order = $order === 'desc' ? 'DESC' : 'ASC';
            $sortCol = $allowedSort[$sort] ?? $allowedSort['nama_jabatan'];
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = 10;
            $offset = ($page - 1) * $perPage;

            $f_jenis = $_GET['f_jenis'] ?? '';
            $f_eselon = $_GET['f_eselon'] ?? '';
            $f_from = $_GET['f_from'] ?? '';
            $f_to = $_GET['f_to'] ?? '';
            $allowedJenis = ['','Struktural','Fungsional','Pelaksana'];
            if (!in_array($f_jenis, $allowedJenis, true)) $f_jenis = '';
            $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
            if ($f_from && !preg_match($dateRegex, $f_from)) $f_from = '';
            if ($f_to && !preg_match($dateRegex, $f_to)) $f_to = '';
            $eselonOpts = mysqli_query($koneksi, "SELECT DISTINCT eselon FROM master_jabatan WHERE eselon IS NOT NULL AND eselon <> '' ORDER BY eselon");
            ?>
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Data Jabatan</h4>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header border-bottom border-dashed d-flex align-items-center">
                    <h5 class="card-title mb-0">Filter Data</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="admin/jabatan/jabatan.php" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Jenis Jabatan</label>
                            <select name="f_jenis" class="form-select">
                                <?php foreach ($allowedJenis as $j): ?>
                                    <option value="<?php echo $j; ?>" <?php echo ($f_jenis===$j)?'selected':''; ?>><?php echo $j ?: '- Semua -'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Eselon</label>
                            <select name="f_eselon" class="form-select">
                                <option value="">- Semua -</option>
                                <?php while ($e = mysqli_fetch_assoc($eselonOpts)): ?>
                                    <option value="<?php echo htmlspecialchars($e['eselon']); ?>" <?php echo ($f_eselon===$e['eselon'])?'selected':''; ?>><?php echo htmlspecialchars($e['eselon']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Dibuat (dari)</label>
                            <input type="date" name="f_from" value="<?php echo htmlspecialchars($f_from); ?>" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Dibuat (sampai)</label>
                            <input type="date" name="f_to" value="<?php echo htmlspecialchars($f_to); ?>" class="form-control">
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button>
                            <a href="admin/jabatan/jabatan.php" class="btn btn-light">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            $where = [];
            if ($f_jenis) { $where[] = "mj.jenis_jabatan = '".mysqli_real_escape_string($koneksi,$f_jenis)."'"; }
            if ($f_eselon) { $where[] = "mj.eselon = '".mysqli_real_escape_string($koneksi,$f_eselon)."'"; }
            if ($f_from) { $where[] = "mj.created_at >= '".$f_from."'"; }
            if ($f_to) { $where[] = "mj.created_at <= '".$f_to."'"; }
            $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
            $total = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM master_jabatan mj $whereSql"))['c'];
            ?>

            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <div>
                        <a href="admin/jabatan/tambah.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah Jabatan</a>
                        <?php
                        // Build cetak URL with current filters
                        $cetakUrl = '/sdm_dispersip/admin/jabatan/cetak.php?'
                            . 'f_jenis=' . urlencode($f_jenis)
                            . '&f_eselon=' . urlencode($f_eselon);
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
                                    <th><a class="link-reset" href="admin/jabatan/jabatan.php?sort=nama_jabatan&order=<?php echo ($sort==='nama_jabatan' && $order==='ASC')?'desc':'asc'; ?>">Nama Jabatan<?php echo $sort==='nama_jabatan' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/jabatan/jabatan.php?sort=eselon&order=<?php echo ($sort==='eselon' && $order==='ASC')?'desc':'asc'; ?>">Eselon<?php echo $sort==='eselon' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/jabatan/jabatan.php?sort=jenis_jabatan&order=<?php echo ($sort==='jenis_jabatan' && $order==='ASC')?'desc':'asc'; ?>">Jenis Jabatan<?php echo $sort==='jenis_jabatan' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th>Keterangan</th>
                                    <th><a class="link-reset" href="admin/jabatan/jabatan.php?sort=created_at&order=<?php echo ($sort==='created_at' && $order==='ASC')?'desc':'asc'; ?>">Dibuat<?php echo $sort==='created_at' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/jabatan/jabatan.php?sort=updated_at&order=<?php echo ($sort==='updated_at' && $order==='ASC')?'desc':'asc'; ?>">Diubah<?php echo $sort==='updated_at' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT mj.* FROM master_jabatan mj $whereSql ORDER BY $sortCol $order LIMIT $perPage OFFSET $offset";
                                $res = mysqli_query($koneksi, $sql);
                                if ($res && mysqli_num_rows($res) > 0) {
                                    $no = $offset + 1;
                                    while ($row = mysqli_fetch_assoc($res)) {
                                        $jenis = $row['jenis_jabatan'];
                                        $created = $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-';
                                        $updated = $row['updated_at'] ? date('d/m/Y H:i', strtotime($row['updated_at'])) : '-';
                                        echo '<tr>' .
                                            '<td>' . $no++ . '</td>' .
                                            '<td>' . htmlspecialchars($row['nama_jabatan']) . '</td>' .
                                            '<td>' . htmlspecialchars($row['eselon'] ?? '-') . '</td>' .
                                            '<td>' . htmlspecialchars($jenis) . '</td>' .
                                            '<td>' . htmlspecialchars($row['keterangan'] ?? '-') . '</td>' .
                                            '<td>' . htmlspecialchars($created) . '</td>' .
                                            '<td>' . htmlspecialchars($updated) . '</td>' .
                                            '<td>' .
                                                '<a href="admin/jabatan/detail.php?id=' . htmlspecialchars($row['jabatan_id']) . '" class="btn btn-sm btn-info me-1"><i class="ti ti-eye me-1"></i>Detail</a>' .
                                                '<a href="admin/jabatan/edit.php?id=' . htmlspecialchars($row['jabatan_id']) . '" class="btn btn-sm btn-warning me-1"><i class="ti ti-pencil me-1"></i>Edit</a>' .
                                                '<a href="admin/jabatan/hapus.php?id=' . htmlspecialchars($row['jabatan_id']) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Hapus data ini?\')"><i class="ti ti-trash me-1"></i>Hapus</a>' .
                                            '</td>' .
                                        '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                    $totalPages = (int)ceil($total / $perPage);
                    if ($totalPages > 1):
                        $baseQS = 'admin/jabatan/jabatan.php?sort=' . urlencode($sort) . '&order=' . strtolower($order)
                            . '&f_jenis=' . urlencode($f_jenis)
                            . '&f_eselon=' . urlencode($f_eselon)
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

        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
