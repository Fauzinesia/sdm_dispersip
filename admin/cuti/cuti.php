<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$page_title = "Data Cuti";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
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
                
                if ($id > 0 && in_array($new, ['Disetujui','Ditolak'], true)) {
                    if ($new === 'Disetujui') {
                        $disposisi = $_POST['disposisi'] ?? '';
                        $stmt = mysqli_prepare($koneksi, "UPDATE cuti SET status=?, disposisi=?, verifikator_user_id=?, verified_at=? WHERE cuti_id=?");
                        mysqli_stmt_bind_param($stmt, 'ssisi', $new, $disposisi, $verifikator_id, $now, $id);
                    } else {
                        $alasan = $_POST['alasan_ditolak'] ?? '';
                        $stmt = mysqli_prepare($koneksi, "UPDATE cuti SET status=?, alasan_ditolak=?, verifikator_user_id=?, verified_at=? WHERE cuti_id=?");
                        mysqli_stmt_bind_param($stmt, 'ssisi', $new, $alasan, $verifikator_id, $now, $id);
                    }
                    
                    if (mysqli_stmt_execute($stmt)) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Status cuti diperbarui menjadi ' . htmlspecialchars($new) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    } else {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Gagal memperbarui status<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    }
                    mysqli_stmt_close($stmt);
                }
            }
            $allowedSort = [
                'nama_lengkap' => 'p.nama_lengkap',
                'jenis_cuti' => 'c.jenis_cuti',
                'tgl_mulai' => 'c.tgl_mulai',
                'tgl_selesai' => 'c.tgl_selesai',
                'lama_hari' => 'c.lama_hari',
                'status' => 'c.status',
                'created_at' => 'c.created_at',
                'updated_at' => 'c.updated_at'
            ];
            $sort = $_GET['sort'] ?? 'tgl_mulai';
            $order = strtolower($_GET['order'] ?? 'desc');
            $order = $order === 'asc' ? 'ASC' : 'DESC';
            $sortCol = $allowedSort[$sort] ?? $allowedSort['tgl_mulai'];
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = 10;
            $offset = ($page - 1) * $perPage;

            $f_jenis = $_GET['f_jenis'] ?? '';
            $f_status = $_GET['f_status'] ?? '';
            $f_from = $_GET['f_from'] ?? '';
            $f_to = $_GET['f_to'] ?? '';
            $f_q = trim($_GET['f_q'] ?? '');
            $allowedJenis = ['','Tahunan','Sakit','Melahirkan','Penting','Besar'];
            $allowedStatus = ['','Menunggu','Disetujui','Ditolak'];
            if (!in_array($f_jenis, $allowedJenis, true)) $f_jenis = '';
            if (!in_array($f_status, $allowedStatus, true)) $f_status = '';
            $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
            if ($f_from && !preg_match($dateRegex, $f_from)) $f_from = '';
            if ($f_to && !preg_match($dateRegex, $f_to)) $f_to = '';
            ?>
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Data Cuti</h4>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header border-bottom border-dashed d-flex align-items-center">
                    <h5 class="card-title mb-0">Filter Data</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="admin/cuti/cuti.php" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Jenis Cuti</label>
                            <select name="f_jenis" class="form-select">
                                <?php foreach ($allowedJenis as $j): ?>
                                    <option value="<?php echo $j; ?>" <?php echo ($f_jenis===$j)?'selected':''; ?>><?php echo $j ?: '- Semua -'; ?></option>
                                <?php endforeach; ?>
                            </select>
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
                            <label class="form-label">Mulai (dari)</label>
                            <input type="date" name="f_from" value="<?php echo htmlspecialchars($f_from); ?>" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mulai (sampai)</label>
                            <input type="date" name="f_to" value="<?php echo htmlspecialchars($f_to); ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cari Nama Pegawai</label>
                            <input type="text" name="f_q" value="<?php echo htmlspecialchars($f_q); ?>" class="form-control" placeholder="Masukkan nama pegawai">
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button>
                            <a href="admin/cuti/cuti.php" class="btn btn-light">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            $where = [];
            if ($f_jenis) { $where[] = "c.jenis_cuti = '".mysqli_real_escape_string($koneksi,$f_jenis)."'"; }
            if ($f_status) { $where[] = "c.status = '".mysqli_real_escape_string($koneksi,$f_status)."'"; }
            if ($f_from) { $where[] = "c.tgl_mulai >= '".$f_from."'"; }
            if ($f_to) { $where[] = "c.tgl_mulai <= '".$f_to."'"; }
            if ($f_q) { $q = mysqli_real_escape_string($koneksi, $f_q); $where[] = "p.nama_lengkap LIKE '%$q%'"; }
            $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
            $total = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM cuti c JOIN pegawai p ON c.pegawai_id=p.pegawai_id $whereSql"))['c'];
            ?>

            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <div>
                        <a href="admin/cuti/tambah.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah Cuti</a>
                        <?php
                        $cetakUrl = '/sdm_dispersip/admin/cuti/cetak.php?'
                            . 'f_jenis=' . urlencode($f_jenis)
                            . '&f_status=' . urlencode($f_status)
                            . '&f_from=' . urlencode($f_from)
                            . '&f_to=' . urlencode($f_to)
                            . '&f_q=' . urlencode($f_q);
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
                                    <th><a class="link-reset" href="admin/cuti/cuti.php?sort=nama_lengkap&order=<?php echo ($sort==='nama_lengkap' && $order==='ASC')?'desc':'asc'; ?>">Nama Pegawai<?php echo $sort==='nama_lengkap' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/cuti/cuti.php?sort=jenis_cuti&order=<?php echo ($sort==='jenis_cuti' && $order==='ASC')?'desc':'asc'; ?>">Jenis Cuti<?php echo $sort==='jenis_cuti' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/cuti/cuti.php?sort=tgl_mulai&order=<?php echo ($sort==='tgl_mulai' && $order==='ASC')?'desc':'asc'; ?>">Mulai<?php echo $sort==='tgl_mulai' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/cuti/cuti.php?sort=tgl_selesai&order=<?php echo ($sort==='tgl_selesai' && $order==='ASC')?'desc':'asc'; ?>">Selesai<?php echo $sort==='tgl_selesai' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/cuti/cuti.php?sort=lama_hari&order=<?php echo ($sort==='lama_hari' && $order==='ASC')?'desc':'asc'; ?>">Lama (hari)<?php echo $sort==='lama_hari' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/cuti/cuti.php?sort=status&order=<?php echo ($sort==='status' && $order==='ASC')?'desc':'asc'; ?>">Status<?php echo $sort==='status' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th>Alasan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $modals = '';
                                $sql = "SELECT c.*, p.nama_lengkap FROM cuti c JOIN pegawai p ON c.pegawai_id = p.pegawai_id $whereSql ORDER BY $sortCol $order LIMIT $perPage OFFSET $offset";
                                $res = mysqli_query($koneksi, $sql);
                                if ($res && mysqli_num_rows($res) > 0) {
                                    $no = $offset + 1;
                                    while ($row = mysqli_fetch_assoc($res)) {
                                        $mulai = date('d/m/Y', strtotime($row['tgl_mulai']));
                                        $selesai = date('d/m/Y', strtotime($row['tgl_selesai']));
                                        $status = $row['status'];
                                        $badge = $status === 'Disetujui' ? 'success' : ($status === 'Ditolak' ? 'danger' : 'warning');
                                        $alasan = $row['alasan'] ? substr($row['alasan'], 0, 60) . (strlen($row['alasan']) > 60 ? '…' : '') : '-';
                                        
                                        $modalApprove = 'modalApprove'.$row['cuti_id'];
                                        $modalReject = 'modalReject'.$row['cuti_id'];
                                        
                                        $actionButtons = '';
                                        if ($status === 'Menunggu') {
                                            $actionButtons .= '<button type="button" class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#'.$modalApprove.'"><i class="ti ti-check me-1"></i>Setujui</button>';
                                            $actionButtons .= '<button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#'.$modalReject.'"><i class="ti ti-x me-1"></i>Tolak</button>';
                                            
                                            // Modal Approve
                                            $modals .= '<div class="modal fade" id="'.$modalApprove.'" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <form method="post" class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Setujui Cuti</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="cuti_id" value="'.htmlspecialchars($row['cuti_id']).'">
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
                                                    <form method="post" class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Tolak Cuti</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="cuti_id" value="'.htmlspecialchars($row['cuti_id']).'">
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
                                        }

                                        echo '<tr>' .
                                            '<td>' . $no++ . '</td>' .
                                            '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>' .
                                            '<td>' . htmlspecialchars($row['jenis_cuti']) . '</td>' .
                                            '<td>' . htmlspecialchars($mulai) . '</td>' .
                                            '<td>' . htmlspecialchars($selesai) . '</td>' .
                                            '<td>' . htmlspecialchars($row['lama_hari']) . '</td>' .
                                            '<td><span class="badge bg-' . $badge . '">' . htmlspecialchars($status) . '</span></td>' .
                                            '<td>' . htmlspecialchars($alasan) . '</td>' .
                                            '<td>' .
                                                '<a href="admin/cuti/detail.php?id=' . htmlspecialchars($row['cuti_id']) . '" class="btn btn-sm btn-info me-1"><i class="ti ti-eye me-1"></i>Detail</a>' .
                                                '<a href="admin/cuti/edit.php?id=' . htmlspecialchars($row['cuti_id']) . '" class="btn btn-sm btn-warning me-1"><i class="ti ti-pencil me-1"></i>Edit</a>' .
                                                '<a href="admin/cuti/hapus.php?id=' . htmlspecialchars($row['cuti_id']) . '" class="btn btn-sm btn-danger me-1" onclick="return confirm(\'Hapus data ini?\')"><i class="ti ti-trash me-1"></i>Hapus</a>' .
                                                $actionButtons
                                            . '</td>' .
                                        '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php echo $modals; ?>
                    <?php
                    $totalPages = (int)ceil($total / $perPage);
                    if ($totalPages > 1):
                        $baseQS = 'admin/cuti/cuti.php?sort=' . urlencode($sort) . '&order=' . strtolower($order)
                            . '&f_jenis=' . urlencode($f_jenis)
                            . '&f_status=' . urlencode($f_status)
                            . '&f_from=' . urlencode($f_from)
                            . '&f_to=' . urlencode($f_to)
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

<?php include '../../includes/footer.php'; ?>
