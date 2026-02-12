<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$page_title = "Data Pangkat";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <?php
            $allowedSort = [
                'nama_pangkat' => 'mp.nama_pangkat',
                'golongan' => 'mp.golongan',
                'created_at' => 'mp.created_at',
                'updated_at' => 'mp.updated_at'
            ];
            $sort = $_GET['sort'] ?? 'golongan';
            $order = strtolower($_GET['order'] ?? 'asc');
            $order = $order === 'desc' ? 'DESC' : 'ASC';
            $sortCol = $allowedSort[$sort] ?? $allowedSort['golongan'];
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = 10;
            $offset = ($page - 1) * $perPage;

            $f_golongan = $_GET['f_golongan'] ?? '';
            $f_from = $_GET['f_from'] ?? '';
            $f_to = $_GET['f_to'] ?? '';
            $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
            if ($f_from && !preg_match($dateRegex, $f_from)) $f_from = '';
            if ($f_to && !preg_match($dateRegex, $f_to)) $f_to = '';
            
            // Get distinct golongan for filter
            $golonganOpts = mysqli_query($koneksi, "SELECT DISTINCT golongan FROM master_pangkat WHERE golongan IS NOT NULL AND golongan <> '' ORDER BY golongan");
            
            // Success/error message
            $msg = $_GET['msg'] ?? '';
            $type = $_GET['type'] ?? 'success';
            if ($msg) {
                echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($msg) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            ?>
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Data Pangkat</h4>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header border-bottom border-dashed d-flex align-items-center">
                    <h5 class="card-title mb-0">Filter Data</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="admin/pangkat/pangkat.php" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Golongan</label>
                            <select name="f_golongan" class="form-select">
                                <option value="">- Semua -</option>
                                <?php while ($g = mysqli_fetch_assoc($golonganOpts)): ?>
                                    <option value="<?php echo htmlspecialchars($g['golongan']); ?>" <?php echo ($f_golongan===$g['golongan'])?'selected':''; ?>><?php echo htmlspecialchars($g['golongan']); ?></option>
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
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button>
                            <a href="admin/pangkat/pangkat.php" class="btn btn-light">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            $where = [];
            if ($f_golongan) { $where[] = "mp.golongan = '".mysqli_real_escape_string($koneksi,$f_golongan)."'"; }
            if ($f_from) { $where[] = "mp.created_at >= '".$f_from."'"; }
            if ($f_to) { $where[] = "mp.created_at <= '".$f_to."'"; }
            $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
            $total = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM master_pangkat mp $whereSql"))['c'];
            ?>

            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <div>
                        <a href="admin/pangkat/tambah.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah Pangkat</a>
                        <?php
                        // Build cetak URL with current filters
                        $cetakUrl = '/sdm_dispersip/admin/pangkat/cetak.php?'
                            . 'f_golongan=' . urlencode($f_golongan);
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
                                    <th><a class="link-reset" href="admin/pangkat/pangkat.php?sort=nama_pangkat&order=<?php echo ($sort==='nama_pangkat' && $order==='ASC')?'desc':'asc'; ?>">Nama Pangkat<?php echo $sort==='nama_pangkat' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/pangkat/pangkat.php?sort=golongan&order=<?php echo ($sort==='golongan' && $order==='ASC')?'desc':'asc'; ?>">Golongan<?php echo $sort==='golongan' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th>Keterangan</th>
                                    <th><a class="link-reset" href="admin/pangkat/pangkat.php?sort=created_at&order=<?php echo ($sort==='created_at' && $order==='ASC')?'desc':'asc'; ?>">Dibuat<?php echo $sort==='created_at' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/pangkat/pangkat.php?sort=updated_at&order=<?php echo ($sort==='updated_at' && $order==='ASC')?'desc':'asc'; ?>">Diubah<?php echo $sort==='updated_at' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT mp.* FROM master_pangkat mp $whereSql ORDER BY $sortCol $order LIMIT $perPage OFFSET $offset";
                                $res = mysqli_query($koneksi, $sql);
                                if ($res && mysqli_num_rows($res) > 0) {
                                    $no = $offset + 1;
                                    while ($row = mysqli_fetch_assoc($res)) {
                                        $created = $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-';
                                        $updated = $row['updated_at'] ? date('d/m/Y H:i', strtotime($row['updated_at'])) : '-';
                                        echo '<tr>' .
                                            '<td>' . $no++ . '</td>' .
                                            '<td>' . htmlspecialchars($row['nama_pangkat']) . '</td>' .
                                            '<td><span class="badge bg-primary">' . htmlspecialchars($row['golongan']) . '</span></td>' .
                                            '<td>' . htmlspecialchars($row['keterangan'] ?? '-') . '</td>' .
                                            '<td>' . htmlspecialchars($created) . '</td>' .
                                            '<td>' . htmlspecialchars($updated) . '</td>' .
                                            '<td>' .
                                                '<a href="admin/pangkat/detail.php?id=' . htmlspecialchars($row['pangkat_id']) . '" class="btn btn-sm btn-info me-1"><i class="ti ti-eye me-1"></i>Detail</a>' .
                                                '<a href="admin/pangkat/edit.php?id=' . htmlspecialchars($row['pangkat_id']) . '" class="btn btn-sm btn-warning me-1"><i class="ti ti-pencil me-1"></i>Edit</a>' .
                                                '<a href="admin/pangkat/hapus.php?id=' . htmlspecialchars($row['pangkat_id']) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Hapus data ini?\')"><i class="ti ti-trash me-1"></i>Hapus</a>' .
                                            '</td>' .
                                        '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                    $totalPages = (int)ceil($total / $perPage);
                    if ($totalPages > 1):
                        $baseQS = 'admin/pangkat/pangkat.php?sort=' . urlencode($sort) . '&order=' . strtolower($order)
                            . '&f_golongan=' . urlencode($f_golongan)
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
