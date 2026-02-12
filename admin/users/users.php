<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$page_title = "Data Pengguna";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_pw'])) {
                $uid = intval($_POST['user_id'] ?? 0);
                $new = trim($_POST['new_password'] ?? '');
                if ($uid > 0) {
                    if ($new === '') { $new = 'admin123'; }
                    $hash = password_hash($new, PASSWORD_DEFAULT);
                    $up = mysqli_prepare($koneksi, "UPDATE users SET password=?, updated_at=NOW() WHERE user_id=?");
                    mysqli_stmt_bind_param($up, 'si', $hash, $uid);
                    if (mysqli_stmt_execute($up)) {
                        $msg = 'Password user berhasil direset';
                        $type = 'success';
                    } else {
                        $msg = 'Gagal mereset password';
                        $type = 'danger';
                    }
                    mysqli_stmt_close($up);
                }
            }
            $msg = $_GET['msg'] ?? '';
            $type = $_GET['type'] ?? 'success';
            if ($msg) {
                echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($msg) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            $allowedSort = [
                'username' => 'u.username',
                'role' => 'u.role',
                'status' => 'u.status',
                'created_at' => 'u.created_at',
                'updated_at' => 'u.updated_at'
            ];
            $sort = $_GET['sort'] ?? 'username';
            $order = strtolower($_GET['order'] ?? 'asc');
            $order = $order === 'desc' ? 'DESC' : 'ASC';
            $sortCol = $allowedSort[$sort] ?? $allowedSort['username'];
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            $f_role = $_GET['f_role'] ?? '';
            $f_status = $_GET['f_status'] ?? '';
            $f_q = trim($_GET['f_q'] ?? '');
            $allowedRole = ['','admin','pegawai','verifikator'];
            $allowedStatus = ['','Aktif','Nonaktif'];
            if (!in_array($f_role, $allowedRole, true)) $f_role = '';
            if (!in_array($f_status, $allowedStatus, true)) $f_status = '';
            ?>
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Data Pengguna</h4>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header border-bottom border-dashed d-flex align-items-center">
                    <h5 class="card-title mb-0">Filter Data</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="admin/users/users.php" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Role</label>
                            <select name="f_role" class="form-select">
                                <?php foreach ($allowedRole as $r): ?>
                                    <option value="<?php echo $r; ?>" <?php echo ($f_role===$r)?'selected':''; ?>><?php echo $r ?: '- Semua -'; ?></option>
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
                        <div class="col-md-6">
                            <label class="form-label">Cari Username</label>
                            <input type="text" name="f_q" value="<?php echo htmlspecialchars($f_q); ?>" class="form-control" placeholder="Masukkan username">
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button>
                            <a href="admin/users/users.php" class="btn btn-light">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            $where = [];
            if ($f_role) { $where[] = "u.role = '".mysqli_real_escape_string($koneksi,$f_role)."'"; }
            if ($f_status) { $where[] = "u.status = '".mysqli_real_escape_string($koneksi,$f_status)."'"; }
            if ($f_q) { $q = mysqli_real_escape_string($koneksi, $f_q); $where[] = "u.username LIKE '%$q%'"; }
            $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
            $total = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM users u $whereSql"))['c'];
            ?>

            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <div>
                        <a href="admin/users/tambah.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah User</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive-sm">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th><a class="link-reset" href="admin/users/users.php?sort=username&order=<?php echo ($sort==='username' && $order==='ASC')?'desc':'asc'; ?>">Username<?php echo $sort==='username' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/users/users.php?sort=role&order=<?php echo ($sort==='role' && $order==='ASC')?'desc':'asc'; ?>">Role<?php echo $sort==='role' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th>Pegawai</th>
                                    <th><a class="link-reset" href="admin/users/users.php?sort=status&order=<?php echo ($sort==='status' && $order==='ASC')?'desc':'asc'; ?>">Status<?php echo $sort==='status' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/users/users.php?sort=created_at&order=<?php echo ($sort==='created_at' && $order==='ASC')?'desc':'asc'; ?>">Dibuat<?php echo $sort==='created_at' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th><a class="link-reset" href="admin/users/users.php?sort=updated_at&order=<?php echo ($sort==='updated_at' && $order==='ASC')?'desc':'asc'; ?>">Diubah<?php echo $sort==='updated_at' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT u.*, p.nama_lengkap AS pegawai_nama FROM users u LEFT JOIN pegawai p ON p.user_id=u.user_id $whereSql ORDER BY $sortCol $order LIMIT $perPage OFFSET $offset";
                                $res = mysqli_query($koneksi, $sql);
                                if ($res && mysqli_num_rows($res) > 0) {
                                    $no = $offset + 1;
                                    while ($row = mysqli_fetch_assoc($res)) {
                                        $role = $row['role'];
                                        $status = $row['status'];
                                        echo '<tr>' .
                                            '<td>' . $no++ . '</td>' .
                                            '<td>' . htmlspecialchars($row['username']) . '</td>' .
                                            '<td><span class="badge bg-info">' . htmlspecialchars($role) . '</span></td>' .
                                            '<td>' . htmlspecialchars($row['pegawai_nama'] ?? '-') . '</td>' .
                                            '<td><span class="badge bg-' . ($status === 'Aktif' ? 'success' : 'secondary') . '">' . htmlspecialchars($status) . '</span></td>' .
                                            '<td>' . htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at']))) . '</td>' .
                                            '<td>' . htmlspecialchars(date('d/m/Y H:i', strtotime($row['updated_at']))) . '</td>' .
                                            '<td>' .
                                                '<a href="admin/users/edit.php?id=' . htmlspecialchars($row['user_id']) . '" class="btn btn-sm btn-warning me-1"><i class="ti ti-pencil me-1"></i>Edit</a>' .
                                                '<a href="admin/users/hapus.php?id=' . htmlspecialchars($row['user_id']) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Hapus user ini?\')"><i class="ti ti-trash me-1"></i>Hapus</a>' .
                                                '<form method="post" style="display:inline" class="ms-1">' .
                                                    '<input type="hidden" name="user_id" value="' . htmlspecialchars($row['user_id']) . '">' .
                                                    '<input type="hidden" name="new_password" value="admin123">' .
                                                    '<button type="submit" name="reset_pw" class="btn btn-sm btn-secondary" onclick="return confirm(\'Reset password ke default?\')"><i class="ti ti-key me-1"></i>Reset PW</button>' .
                                                '</form>' .
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
                        $baseQS = 'admin/users/users.php?sort=' . urlencode($sort) . '&order=' . strtolower($order)
                            . '&f_role=' . urlencode($f_role)
                            . '&f_status=' . urlencode($f_status)
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
