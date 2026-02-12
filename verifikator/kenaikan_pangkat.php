<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'verifikator') { header("Location: ../admin/dashboard.php"); exit(); }
require_once '../config/koneksi.php';
$page_title = "Kenaikan Pangkat";
include '../includes/header.php';
include '../includes/sidebar.php';
$join = "SELECT kp.*, p.nama_lengkap, mp1.nama_pangkat AS pangkat_lama, mp2.nama_pangkat AS pangkat_baru FROM kenaikan_pangkat kp JOIN pegawai p ON kp.pegawai_id=p.pegawai_id LEFT JOIN master_pangkat mp1 ON kp.pangkat_lama_id=mp1.pangkat_id LEFT JOIN master_pangkat mp2 ON kp.pangkat_baru_id=mp2.pangkat_id ORDER BY kp.created_at DESC";
include '../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Kenaikan Pangkat</h4></div></div></div>
<div class="card"><div class="card-body">
<div class="table-responsive-sm"><table class="table table-striped mb-0"><thead><tr><th>Nama Pegawai</th><th>Pangkat Lama</th><th>Pangkat Baru</th><th>No SK</th><th>Tanggal SK</th><th>TMT</th><th>Aksi</th></tr></thead><tbody>
<?php $res=mysqli_query($koneksi,$join); if($res && mysqli_num_rows($res)>0){ while($r=mysqli_fetch_assoc($res)){ echo '<tr>' . '<td>'.htmlspecialchars($r['nama_lengkap']).'</td>' . '<td>'.htmlspecialchars($r['pangkat_lama'] ?? '-').'</td>' . '<td>'.htmlspecialchars($r['pangkat_baru']).'</td>' . '<td>'.htmlspecialchars($r['nomor_sk'] ?? '-').'</td>' . '<td>'.($r['tanggal_sk']?date('d/m/Y', strtotime($r['tanggal_sk'])):'-').'</td>' . '<td>'.date('d/m/Y', strtotime($r['tmt'])).'</td>' . '<td><a href="admin/kenaikan_pangkat/detail.php?id='.htmlspecialchars($r['kp_id']).'" class="btn btn-sm btn-info"><i class="ti ti-eye me-1"></i>Detail</a></td>' . '</tr>'; } } else { echo '<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>'; } ?>
</tbody></table></div>
</div></div>
</div></div></div>
<?php include '../includes/footer.php'; ?>

