<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'pegawai') { header("Location: ../admin/dashboard.php"); exit(); }
require_once '../config/koneksi.php';
$page_title = "Slip Gaji";
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Slip Gaji</h4></div></div></div>
<div class="card"><div class="card-body">
<div class="table-responsive-sm"><table class="table table-striped mb-0"><thead><tr><th>Periode</th><th>Gaji Pokok</th><th>Tunjangan</th><th>Potongan</th><th>Total</th></tr></thead><tbody>
<?php $pid = intval($_SESSION['pegawai_id'] ?? 0); $res = mysqli_query($koneksi, "SELECT * FROM gaji WHERE pegawai_id=".$pid." ORDER BY created_at DESC"); if ($res && mysqli_num_rows($res)>0){ while($r=mysqli_fetch_assoc($res)){ echo '<tr>' . '<td>'.htmlspecialchars($r['periode']).'</td>' . '<td>'.htmlspecialchars($r['gaji_pokok']).'</td>' . '<td>'.htmlspecialchars($r['tunjangan']).'</td>' . '<td>'.htmlspecialchars($r['potongan']).'</td>' . '<td>'.htmlspecialchars($r['total_gaji']).'</td>' . '</tr>'; } } else { echo '<tr><td colspan="5" class="text-center">Belum ada data</td></tr>'; } ?>
</tbody></table></div>
</div></div>
</div></div></div>
<?php include '../includes/footer.php'; ?>

