<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'verifikator') { header("Location: ../admin/dashboard.php"); exit(); }
require_once '../config/koneksi.php';
$page_title = "Persetujuan Cuti";
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_status'])) {
    $id = intval($_POST['cuti_id'] ?? 0);
    $new = $_POST['new_status'] ?? '';
    $verifikator_id = intval($_SESSION['user_id']);
    $now = date('Y-m-d H:i:s');

    if ($id > 0 && in_array($new, ['Disetujui', 'Ditolak'], true)) {
        if ($new === 'Disetujui') {
            $disposisi = $_POST['disposisi'] ?? '';
            $st = mysqli_prepare($koneksi, "UPDATE cuti SET status=?, disposisi=?, verifikator_user_id=?, verified_at=? WHERE cuti_id=?");
            mysqli_stmt_bind_param($st, 'ssisi', $new, $disposisi, $verifikator_id, $now, $id);
        } else {
            $alasan = $_POST['alasan_ditolak'] ?? '';
            $st = mysqli_prepare($koneksi, "UPDATE cuti SET status=?, alasan_ditolak=?, verifikator_user_id=?, verified_at=? WHERE cuti_id=?");
            mysqli_stmt_bind_param($st, 'ssisi', $new, $alasan, $verifikator_id, $now, $id);
        }
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
    }
}
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Persetujuan Cuti</h4></div></div></div>
<div class="card"><div class="card-body">
<div class="table-responsive-sm"><table class="table table-striped mb-0"><thead><tr><th>Nama Pegawai</th><th>Jenis</th><th>Periode</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php 
$modals = '';
$res = mysqli_query($koneksi, "SELECT c.*, p.nama_lengkap FROM cuti c JOIN pegawai p ON c.pegawai_id=p.pegawai_id WHERE c.status='Menunggu' ORDER BY c.created_at DESC"); 
if ($res && mysqli_num_rows($res) > 0) { 
    while ($r = mysqli_fetch_assoc($res)) { 
        $modalApprove = 'modalApprove'.$r['cuti_id'];
        $modalReject = 'modalReject'.$r['cuti_id'];
        echo '<tr>' 
        . '<td>'.htmlspecialchars($r['nama_lengkap']).'</td>' 
        . '<td>'.htmlspecialchars($r['jenis_cuti']).'</td>' 
        . '<td>'.date('d/m/Y', strtotime($r['tgl_mulai'])).' - '.date('d/m/Y', strtotime($r['tgl_selesai'])).'</td>' 
        . '<td><span class="badge bg-warning">Menunggu</span></td>' 
        . '<td>' 
        . '<a href="admin/cuti/detail.php?id='.htmlspecialchars($r['cuti_id']).'" class="btn btn-sm btn-info me-1"><i class="ti ti-eye me-1"></i>Detail</a>'
        . '<button type="button" class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#'.$modalApprove.'"><i class="ti ti-check me-1"></i>Setujui</button>'
        . '<button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#'.$modalReject.'"><i class="ti ti-x me-1"></i>Tolak</button>'
        . '</td>' 
        . '</tr>';
        
        // Modal Approve
        $modals .= '<div class="modal fade" id="'.$modalApprove.'" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="post" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Setujui Cuti</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="cuti_id" value="'.htmlspecialchars($r['cuti_id']).'">
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
                        <input type="hidden" name="cuti_id" value="'.htmlspecialchars($r['cuti_id']).'">
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
} else { 
    echo '<tr><td colspan="5" class="text-center">Tidak ada pengajuan menunggu</td></tr>'; 
} 
?>
</tbody></table></div>
<?php echo $modals; ?>
</div></div>
</div></div></div>
<?php include '../includes/footer.php'; ?>

