<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

// Handle bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $ids = $_POST['selected_ids'] ?? [];
    if (is_array($ids) && count($ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = mysqli_prepare($koneksi, "DELETE FROM hari_libur WHERE libur_id IN ($placeholders)");
        $types = str_repeat('i', count($ids));
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = 'Berhasil menghapus ' . count($ids) . ' data hari libur';
        }
        mysqli_stmt_close($stmt);
    }
}

// Filters
$f_tahun = $_GET['f_tahun'] ?? date('Y');
$f_jenis = $_GET['f_jenis'] ?? '';
$f_q = trim($_GET['f_q'] ?? '');

$allowed_jenis = ['', 'Nasional', 'Cuti Bersama', 'Khusus'];
if (!in_array($f_jenis, $allowed_jenis, true)) $f_jenis = '';

// Build WHERE clause
$where = ["YEAR(tanggal) = " . intval($f_tahun)];
if ($f_jenis) { 
    $where[] = "jenis = '" . mysqli_real_escape_string($koneksi, $f_jenis) . "'"; 
}
if ($f_q) { 
    $q = mysqli_real_escape_string($koneksi, $f_q); 
    $where[] = "(nama_libur LIKE '%$q%' OR keterangan LIKE '%$q%')"; 
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

// Get data
$query = "SELECT * FROM hari_libur $whereSql ORDER BY tanggal ASC";
$result = mysqli_query($koneksi, $query);
$total = mysqli_num_rows($result);

// Get available years
$years_query = mysqli_query($koneksi, "SELECT DISTINCT YEAR(tanggal) as tahun FROM hari_libur ORDER BY tahun DESC");

$page_title = "Kelola Hari Libur";
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
                        <h4 class="page-title">Kelola Hari Libur</h4>
                    </div>
                </div>
            </div>

            <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-<?php echo $_GET['type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Filter Card -->
            <div class="card mb-3">
                <div class="card-header border-bottom border-dashed">
                    <h5 class="card-title mb-0">Filter Data</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="admin/hari_libur/hari_libur.php" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tahun</label>
                            <select name="f_tahun" class="form-select">
                                <?php 
                                $current_year = date('Y');
                                for ($y = $current_year - 1; $y <= $current_year + 2; $y++): 
                                ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($f_tahun == $y) ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Jenis</label>
                            <select name="f_jenis" class="form-select">
                                <?php foreach ($allowed_jenis as $j): ?>
                                    <option value="<?php echo $j; ?>" <?php echo ($f_jenis === $j) ? 'selected' : ''; ?>>
                                        <?php echo $j ?: '- Semua Jenis -'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cari Nama Libur</label>
                            <input type="text" name="f_q" value="<?php echo htmlspecialchars($f_q); ?>" class="form-control" placeholder="Masukkan nama libur">
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                            <a href="admin/hari_libur/hari_libur.php" class="btn btn-light">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Data Card -->
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <div>
                        <a href="admin/hari_libur/tambah.php" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>Tambah Hari Libur
                        </a>
                    </div>
                    <div>
                        <span class="text-muted">Total: <strong><?php echo $total; ?></strong> hari libur</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" id="formBulk">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th width="30"><input type="checkbox" id="checkAll"></th>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Hari</th>
                                        <th>Nama Libur</th>
                                        <th>Jenis</th>
                                        <th>Keterangan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result && mysqli_num_rows($result) > 0) {
                                        $no = 1;
                                        $hari_indo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $tanggal_obj = new DateTime($row['tanggal']);
                                            $hari = $hari_indo[$tanggal_obj->format('w')];
                                            $tanggal_format = $tanggal_obj->format('d/m/Y');
                                            
                                            $badge_jenis = [
                                                'Nasional' => 'danger',
                                                'Cuti Bersama' => 'warning',
                                                'Khusus' => 'info'
                                            ];
                                            $badge = $badge_jenis[$row['jenis']] ?? 'secondary';
                                            
                                            echo '<tr>';
                                            echo '<td><input type="checkbox" name="selected_ids[]" value="' . $row['libur_id'] . '" class="checkItem"></td>';
                                            echo '<td>' . $no++ . '</td>';
                                            echo '<td>' . htmlspecialchars($tanggal_format) . '</td>';
                                            echo '<td><strong>' . $hari . '</strong></td>';
                                            echo '<td>' . htmlspecialchars($row['nama_libur']) . '</td>';
                                            echo '<td><span class="badge bg-' . $badge . '">' . htmlspecialchars($row['jenis']) . '</span></td>';
                                            echo '<td>' . htmlspecialchars($row['keterangan'] ?? '-') . '</td>';
                                            echo '<td>' . ($row['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>') . '</td>';
                                            echo '<td>';
                                            echo '<a href="admin/hari_libur/edit.php?id=' . $row['libur_id'] . '" class="btn btn-sm btn-warning me-1"><i class="ti ti-pencil me-1"></i>Edit</a>';
                                            echo '<a href="admin/hari_libur/hapus.php?id=' . $row['libur_id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Hapus data ini?\')"><i class="ti ti-trash me-1"></i>Hapus</a>';
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="9" class="text-center">Tidak ada data hari libur</td></tr>';
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
