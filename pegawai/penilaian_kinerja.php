<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'pegawai') { header("Location: ../admin/dashboard.php"); exit(); }
require_once '../config/koneksi.php';
$page_title = "Penilaian Kinerja";
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';
$pid = intval($_SESSION['pegawai_id'] ?? 0);

// Cek status kepegawaian
$stmt_status = mysqli_prepare($koneksi, "SELECT status_kepegawaian FROM pegawai WHERE pegawai_id = ?");
mysqli_stmt_bind_param($stmt_status, 'i', $pid);
mysqli_stmt_execute($stmt_status);
$pegawai_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_status));
mysqli_stmt_close($stmt_status);

$allowed_status = ['Honorer', 'Kontrak'];
$can_access = in_array($pegawai_data['status_kepegawaian'] ?? '', $allowed_status);
$allowedSort = [ 'periode' => 'pk.periode', 'skor_akhir' => 'pk.skor_akhir', 'created_at' => 'pk.created_at' ];
$sort = $_GET['sort'] ?? 'periode';
$order = strtolower($_GET['order'] ?? 'desc');
$order = $order === 'asc' ? 'ASC' : 'DESC';
$sortCol = $allowedSort[$sort] ?? $allowedSort['periode'];
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$f_year = preg_replace('/[^0-9]/', '', $_GET['f_year'] ?? '');
$f_month = preg_replace('/[^0-9]/', '', $_GET['f_month'] ?? '');
$f_q = trim($_GET['f_q'] ?? '');
$where = ["pk.pegawai_id = ".$pid];
if ($f_year && strlen($f_year)===4) { $where[] = "LEFT(pk.periode,4) = '".mysqli_real_escape_string($koneksi,$f_year)."'"; }
if ($f_month && strlen($f_month)===2) { $where[] = "RIGHT(pk.periode,2) = '".mysqli_real_escape_string($koneksi,$f_month)."'"; }
if ($f_q) { $q = mysqli_real_escape_string($koneksi, $f_q); $where[] = "(pk.komentar LIKE '%$q%')"; }
$whereSql = 'WHERE '.implode(' AND ', $where);
$countSql = "SELECT COUNT(*) AS c FROM penilaian_kinerja pk $whereSql";
$countRes = mysqli_query($koneksi, $countSql);
$total = 0; if ($countRes) { $cr = mysqli_fetch_assoc($countRes); $total = intval($cr['c'] ?? 0); }
$sql = "SELECT pk.*, u.username AS penilai_username FROM penilaian_kinerja pk LEFT JOIN users u ON pk.penilai_user_id=u.user_id $whereSql ORDER BY $sortCol $order LIMIT $perPage OFFSET $offset";
$res = mysqli_query($koneksi, $sql);
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Penilaian Kinerja</h4></div></div></div>

<?php if (!$can_access): ?>
<div class="alert alert-info" role="alert">
    <h5 class="alert-heading"><i class="ti ti-info-circle me-2"></i>Informasi</h5>
    <p class="mb-0">Fitur Penilaian Kinerja hanya tersedia untuk pegawai dengan status kepegawaian <strong>Honorer</strong> dan <strong>Kontrak</strong>.</p>
    <p class="mb-0 mt-2">Status kepegawaian Anda: <strong><?php echo htmlspecialchars($pegawai_data['status_kepegawaian'] ?? '-'); ?></strong></p>
    <hr>
    <p class="mb-0"><small>Penilaian kinerja Anda akan dikelola oleh admin/verifikator. Silakan hubungi bagian kepegawaian untuk informasi lebih lanjut.</small></p>
</div>
<?php else: ?>
<div class="card mb-3"><div class="card-header border-bottom border-dashed d-flex align-items-center"><h5 class="card-title mb-0">Filter Data</h5></div><div class="card-body">
<form method="get" action="pegawai/penilaian_kinerja.php" class="row g-3">
<div class="col-md-3"><label class="form-label">Tahun</label><input type="text" name="f_year" value="<?php echo htmlspecialchars($f_year); ?>" class="form-control" placeholder="YYYY"></div>
<div class="col-md-3"><label class="form-label">Bulan</label><input type="text" name="f_month" value="<?php echo htmlspecialchars($f_month); ?>" class="form-control" placeholder="MM"></div>
<div class="col-md-6"><label class="form-label">Cari Komentar</label><input type="text" name="f_q" value="<?php echo htmlspecialchars($f_q); ?>" class="form-control" placeholder="Masukkan kata kunci"></div>
<div class="col-md-6 d-flex align-items-end gap-2"><button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button><a href="pegawai/penilaian_kinerja.php" class="btn btn-light">Reset</a></div>
</form></div></div>
<div class="card"><div class="card-body"><div class="table-responsive-sm">
<table class="table table-striped mb-0"><thead><tr>
<th>Periode</th>
<th>Kuantitas</th>
<th>Kualitas</th>
<th>Perilaku</th>
<th><a class="link-reset" href="pegawai/penilaian_kinerja.php?sort=skor_akhir&order=<?php echo ($sort==='skor_akhir' && $order==='ASC')?'desc':'asc'; ?>">Skor Akhir<?php echo $sort==='skor_akhir' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
<th>Predikat</th>
<th>Penilai</th>
<th><a class="link-reset" href="pegawai/penilaian_kinerja.php?sort=created_at&order=<?php echo ($sort==='created_at' && $order==='ASC')?'desc':'asc'; ?>">Dibuat<?php echo $sort==='created_at' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
</tr></thead><tbody>
<?php if ($res && mysqli_num_rows($res)>0){ while($r=mysqli_fetch_assoc($res)){ echo '<tr>'
. '<td>'.htmlspecialchars($r['periode']).'</td>'
. '<td>'.htmlspecialchars(number_format((float)$r['nilai_kuantitas'],2)).'</td>'
. '<td>'.htmlspecialchars(number_format((float)$r['nilai_kualitas'],2)).'</td>'
. '<td>'.htmlspecialchars(number_format((float)$r['nilai_perilaku'],2)).'</td>'
. '<td>'.htmlspecialchars(number_format((float)$r['skor_akhir'],2)).'</td>'
. '<td><span class="badge bg-info">'.htmlspecialchars(getPredikatKinerja((float)$r['skor_akhir'])).'</span></td>'
. '<td>'.htmlspecialchars($r['penilai_username'] ?? '-').'</td>'
. '<td>'.($r['created_at'] ? date('d/m/Y', strtotime($r['created_at'])) : '-').'</td>'
. '</tr>'; } } else { echo '<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>'; } ?>
</tbody></table></div>
<?php $totalPages = (int)ceil($total / $perPage); if ($totalPages>1){ $baseQS = 'pegawai/penilaian_kinerja.php?sort=' . urlencode($sort) . '&order=' . strtolower($order) . '&f_year=' . urlencode($f_year) . '&f_month=' . urlencode($f_month) . '&f_q=' . urlencode($f_q); ?>
<nav aria-label="Page navigation" class="mt-3"><ul class="pagination mb-0">
<li class="page-item <?php echo ($page<=1)?'disabled':''; ?>"><a class="page-link" href="<?php echo $baseQS . '&page=' . max(1, $page-1); ?>">Prev</a></li>
<?php for ($i=1;$i<=$totalPages;$i++): ?>
<li class="page-item <?php echo ($i===$page)?'active':''; ?>"><a class="page-link" href="<?php echo $baseQS . '&page=' . $i; ?>"><?php echo $i; ?></a></li>
<?php endfor; ?>
<li class="page-item <?php echo ($page>=$totalPages)?'disabled':''; ?>"><a class="page-link" href="<?php echo $baseQS . '&page=' . min($totalPages, $page+1); ?>">Next</a></li>
</ul></nav>
<?php } ?>
</div></div>
<?php endif; ?>
</div></div></div>
<?php include '../includes/footer.php'; ?>

