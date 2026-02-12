<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'verifikator') { header("Location: ../admin/dashboard.php"); exit(); }
require_once '../config/koneksi.php';
$page_title = "Validasi KGB";
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['approve_kgb'])){ $id=intval($_POST['kgb_id']??0); if($id>0){ $st=mysqli_prepare($koneksi,"UPDATE kgb SET status='Disahkan' WHERE kgb_id=?"); mysqli_stmt_bind_param($st,'i',$id); mysqli_stmt_execute($st); mysqli_stmt_close($st); } }
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Validasi KGB</h4></div></div></div>
<div class="card"><div class="card-body">
<div class="table-responsive-sm"><table class="table table-striped mb-0"><thead><tr><th>Nama Pegawai</th><th>No SK</th><th>Tanggal SK</th><th>TMT Mulai</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php $res=mysqli_query($koneksi,"SELECT k.*, p.nama_lengkap FROM kgb k JOIN pegawai p ON k.pegawai_id=p.pegawai_id WHERE k.status='Draft' ORDER BY k.created_at DESC"); if($res && mysqli_num_rows($res)>0){ while($r=mysqli_fetch_assoc($res)){ echo '<tr>' . '<td>'.htmlspecialchars($r['nama_lengkap']).'</td>' . '<td>'.htmlspecialchars($r['nomor_sk'] ?? '-').'</td>' . '<td>'.($r['tanggal_sk']?date('d/m/Y', strtotime($r['tanggal_sk'])):'-').'</td>' . '<td>'.date('d/m/Y', strtotime($r['tmt_mulai'])).'</td>' . '<td><span class="badge bg-secondary">Draft</span></td>' . '<td><a href="admin/kgb/detail.php?id='.htmlspecialchars($r['kgb_id']).'" class="btn btn-sm btn-info me-1"><i class="ti ti-eye me-1"></i>Detail</a><form method="post" style="display:inline"><input type="hidden" name="kgb_id" value="'.htmlspecialchars($r['kgb_id']).'"><button class="btn btn-sm btn-success" name="approve_kgb"><i class="ti ti-check me-1"></i>Sahkan</button></form></td>' . '</tr>'; } } else { echo '<tr><td colspan="6" class="text-center">Tidak ada KGB draft</td></tr>'; } ?>
</tbody></table></div>
</div></div>
</div></div></div>
<?php include '../includes/footer.php'; ?>

