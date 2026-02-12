<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$page_title = "Data Absensi";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <?php
            // Handle bulk delete
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
                $ids = $_POST['selected_ids'] ?? [];
                if (is_array($ids) && count($ids) > 0) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = mysqli_prepare($koneksi, "DELETE FROM absensi WHERE absensi_id IN ($placeholders)");
                    $types = str_repeat('i', count($ids));
                    mysqli_stmt_bind_param($stmt, $types, ...$ids);
                    if (mysqli_stmt_execute($stmt)) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Berhasil menghapus ' . count($ids) . ' data absensi<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    }
                    mysqli_stmt_close($stmt);
                }
            }

            // Sorting & Pagination
            $allowedSort = [
                'tanggal' => 'a.tanggal',
                'nama_lengkap' => 'p.nama_lengkap',
                'nip' => 'p.nip',
                'jam_masuk' => 'a.jam_masuk',
                'jam_pulang' => 'a.jam_pulang',
                'status_absensi' => 'a.status_absensi'
            ];
            $sort = $_GET['sort'] ?? 'tanggal';
            $order = strtolower($_GET['order'] ?? 'desc');
            $order = $order === 'asc' ? 'ASC' : 'DESC';
            $sortCol = $allowedSort[$sort] ?? $allowedSort['tanggal'];
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = 15;
            $offset = ($page - 1) * $perPage;

            // Filters
            $f_status = $_GET['f_status'] ?? '';
            $f_from = $_GET['f_from'] ?? '';
            $f_to = $_GET['f_to'] ?? '';
            $f_pegawai = $_GET['f_pegawai'] ?? '';
            $f_q = trim($_GET['f_q'] ?? '');
            
            $allowedStatus = ['','Hadir','Terlambat','Tidak Hadir','Izin','Sakit','Cuti'];
            if (!in_array($f_status, $allowedStatus, true)) $f_status = '';
            
            $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
            if ($f_from && !preg_match($dateRegex, $f_from)) $f_from = '';
            if ($f_to && !preg_match($dateRegex, $f_to)) $f_to = '';
            ?>
            
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Manajemen Absensi</h4>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header border-bottom border-dashed d-flex align-items-center">
                    <h5 class="card-title mb-0">Filter Data</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="admin/absensi/absensi.php" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Dari</label>
                            <input type="date" name="f_from" value="<?php echo htmlspecialchars($f_from); ?>" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Sampai</label>
                            <input type="date" name="f_to" value="<?php echo htmlspecialchars($f_to); ?>" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="f_status" class="form-select">
                                <?php foreach ($allowedStatus as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo ($f_status===$s)?'selected':''; ?>><?php echo $s ?: '- Semua -'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
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
                        <div class="col-md-6">
                            <label class="form-label">Cari Nama/NIP</label>
                            <input type="text" name="f_q" value="<?php echo htmlspecialchars($f_q); ?>" class="form-control" placeholder="Masukkan nama atau NIP">
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button>
                            <a href="admin/absensi/absensi.php" class="btn btn-light">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            // Build WHERE clause
            $where = [];
            if ($f_status) { $where[] = "a.status_absensi = '".mysqli_real_escape_string($koneksi,$f_status)."'"; }
            if ($f_from) { $where[] = "a.tanggal >= '".$f_from."'"; }
            if ($f_to) { $where[] = "a.tanggal <= '".$f_to."'"; }
            if ($f_pegawai) { $where[] = "a.pegawai_id = ".intval($f_pegawai); }
            if ($f_q) { 
                $q = mysqli_real_escape_string($koneksi, $f_q); 
                $where[] = "(p.nama_lengkap LIKE '%$q%' OR p.nip LIKE '%$q%')"; 
            }
            $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
            
            $total = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM absensi a JOIN pegawai p ON a.pegawai_id=p.pegawai_id $whereSql"))['c'];
            ?>

            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <div>
                        <a href="admin/absensi/tambah.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah Absensi</a>
                        <a href="admin/absensi/rekap.php" class="btn btn-info"><i class="ti ti-report me-1"></i>Rekap Bulanan</a>
                        <?php
                        $cetakUrl = '/sdm_dispersip/admin/absensi/cetak.php?'
                            . 'f_status=' . urlencode($f_status)
                            . '&f_from=' . urlencode($f_from)
                            . '&f_to=' . urlencode($f_to)
                            . '&f_pegawai=' . urlencode($f_pegawai)
                            . '&f_q=' . urlencode($f_q);
                        ?>
                        <a href="<?php echo $cetakUrl; ?>" target="_blank" class="btn btn-secondary"><i class="ti ti-printer me-1"></i>Cetak</a>
                    </div>
                    <div>
                        <span class="text-muted">Total: <strong><?php echo $total; ?></strong> data</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" id="formBulk">
                        <div class="table-responsive-sm">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th width="30"><input type="checkbox" id="checkAll"></th>
                                        <th>No</th>
                                        <th><a class="link-reset" href="admin/absensi/absensi.php?sort=tanggal&order=<?php echo ($sort==='tanggal' && $order==='ASC')?'desc':'asc'; ?>">Tanggal<?php echo $sort==='tanggal' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                        <th><a class="link-reset" href="admin/absensi/absensi.php?sort=nip&order=<?php echo ($sort==='nip' && $order==='ASC')?'desc':'asc'; ?>">NIP<?php echo $sort==='nip' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                        <th><a class="link-reset" href="admin/absensi/absensi.php?sort=nama_lengkap&order=<?php echo ($sort==='nama_lengkap' && $order==='ASC')?'desc':'asc'; ?>">Nama Pegawai<?php echo $sort==='nama_lengkap' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                        <th><a class="link-reset" href="admin/absensi/absensi.php?sort=jam_masuk&order=<?php echo ($sort==='jam_masuk' && $order==='ASC')?'desc':'asc'; ?>">Jam Masuk<?php echo $sort==='jam_masuk' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                        <th><a class="link-reset" href="admin/absensi/absensi.php?sort=jam_pulang&order=<?php echo ($sort==='jam_pulang' && $order==='ASC')?'desc':'asc'; ?>">Jam Pulang<?php echo $sort==='jam_pulang' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                        <th><a class="link-reset" href="admin/absensi/absensi.php?sort=status_absensi&order=<?php echo ($sort==='status_absensi' && $order==='ASC')?'desc':'asc'; ?>">Status<?php echo $sort==='status_absensi' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                        <th>Keterangan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT a.*, p.nama_lengkap, p.nip FROM absensi a JOIN pegawai p ON a.pegawai_id = p.pegawai_id $whereSql ORDER BY $sortCol $order LIMIT $perPage OFFSET $offset";
                                    $res = mysqli_query($koneksi, $sql);
                                    if ($res && mysqli_num_rows($res) > 0) {
                                        $no = $offset + 1;
                                        while ($row = mysqli_fetch_assoc($res)) {
                                            $tgl = date('d/m/Y', strtotime($row['tanggal']));
                                            $status = $row['status_absensi'];
                                            $badgeMap = [
                                                'Hadir' => 'success',
                                                'Terlambat' => 'warning',
                                                'Tidak Hadir' => 'danger',
                                                'Izin' => 'info',
                                                'Sakit' => 'warning',
                                                'Cuti' => 'primary'
                                            ];
                                            $badge = $badgeMap[$status] ?? 'secondary';
                                            $keterangan = $row['keterangan'] ? substr($row['keterangan'], 0, 40) . (strlen($row['keterangan']) > 40 ? '…' : '') : '-';
                                            echo '<tr>' .
                                                '<td><input type="checkbox" name="selected_ids[]" value="' . $row['absensi_id'] . '" class="checkItem"></td>' .
                                                '<td>' . $no++ . '</td>' .
                                                '<td>' . htmlspecialchars($tgl) . '</td>' .
                                                '<td>' . htmlspecialchars($row['nip'] ?? '-') . '</td>' .
                                                '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>' .
                                                '<td>' . ($row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-') . '</td>' .
                                                '<td>' . ($row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-') . '</td>' .
                                                '<td><span class="badge bg-' . $badge . '">' . htmlspecialchars($status) . '</span></td>' .
                                                '<td>' . htmlspecialchars($keterangan) . '</td>' .
                                                '<td>' .
                                                    '<a href="admin/absensi/detail.php?id=' . $row['absensi_id'] . '" class="btn btn-sm btn-info me-1"><i class="ti ti-eye me-1"></i>Detail</a>' .
                                                    '<a href="admin/absensi/edit.php?id=' . $row['absensi_id'] . '" class="btn btn-sm btn-warning me-1"><i class="ti ti-pencil me-1"></i>Edit</a>' .
                                                    '<a href="admin/absensi/hapus.php?id=' . $row['absensi_id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Hapus data ini?\')"><i class="ti ti-trash me-1"></i>Hapus</a>' .
                                                '</td>' .
                                            '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="10" class="text-center">Tidak ada data</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="bulk_delete" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data yang dipilih?')">
                                <i class="ti ti-trash me-1"></i>Hapus Terpilih
                            </button>
                        </div>
                    </form>
                    
                    <?php
                    $totalPages = (int)ceil($total / $perPage);
                    if ($totalPages > 1):
                        $baseQS = 'admin/absensi/absensi.php?sort=' . urlencode($sort) . '&order=' . strtolower($order)
                            . '&f_status=' . urlencode($f_status)
                            . '&f_from=' . urlencode($f_from)
                            . '&f_to=' . urlencode($f_to)
                            . '&f_pegawai=' . urlencode($f_pegawai)
                            . '&f_q=' . urlencode($f_q);
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

<script>
document.getElementById('checkAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.checkItem');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

<?php include '../../includes/footer.php'; ?>
