<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
$role = $_SESSION['role'] ?? '';
if ($role !== 'admin' && $role !== 'verifikator') { 
    $redirect = ($role === 'pegawai') ? '../../pegawai/dashboard.php' : '../../login.php';
    header("Location: $redirect"); 
    exit(); 
}

require_once '../../config/koneksi.php';
$page_title = "Verifikasi Logbook";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';

// Handle Verifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi'])) {
    $id = intval($_POST['logbook_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $komentar = $_POST['komentar'] ?? '';
    $verifikator_id = intval($_SESSION['user_id']);

    if ($id > 0 && in_array($status, ['Disetujui', 'Ditolak'])) {
        $stmt = mysqli_prepare($koneksi, "UPDATE logbook SET status=?, komentar_verifikator=?, verifikator_id=? WHERE logbook_id=?");
        mysqli_stmt_bind_param($stmt, 'ssii', $status, $komentar, $verifikator_id, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Filter
$f_start = $_GET['f_start'] ?? date('Y-m-01');
$f_end = $_GET['f_end'] ?? date('Y-m-t');
$f_pegawai = intval($_GET['f_pegawai'] ?? 0);
$f_status = $_GET['f_status'] ?? 'Pending';
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Verifikasi Logbook Pegawai</h4></div></div></div>
            
            <div class="card mb-3">
                <div class="card-header border-bottom border-dashed"><h5 class="card-title mb-0">Filter Logbook</h5></div>
                <div class="card-body">
                    <form method="get" class="row g-2">
                        <div class="col-md-2"><label class="form-label">Mulai</label><input type="date" name="f_start" class="form-control" value="<?php echo $f_start; ?>"></div>
                        <div class="col-md-2"><label class="form-label">Selesai</label><input type="date" name="f_end" class="form-control" value="<?php echo $f_end; ?>"></div>
                        <div class="col-md-3">
                            <label class="form-label">Pegawai</label>
                            <select name="f_pegawai" class="form-select">
                                <option value="0">- Semua Pegawai -</option>
                                <?php
                                $pegs = mysqli_query($koneksi, "SELECT pegawai_id, nama_lengkap FROM pegawai WHERE status_aktif='Aktif' ORDER BY nama_lengkap");
                                while($p = mysqli_fetch_assoc($pegs)) {
                                    $sel = ($f_pegawai == $p['pegawai_id']) ? 'selected' : '';
                                    echo '<option value="'.$p['pegawai_id'].'" '.$sel.'>'.htmlspecialchars($p['nama_lengkap']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="f_status" class="form-select">
                                <option value="">- Semua -</option>
                                <option value="Pending" <?php echo ($f_status==='Pending'?'selected':''); ?>>Pending</option>
                                <option value="Disetujui" <?php echo ($f_status==='Disetujui'?'selected':''); ?>>Disetujui</option>
                                <option value="Ditolak" <?php echo ($f_status==='Ditolak'?'selected':''); ?>>Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>Tampilkan</button></div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Pegawai</th>
                                    <th>Tanggal</th>
                                    <th>Kegiatan</th>
                                    <th>Hasil</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $where = "WHERE l.tanggal BETWEEN '$f_start' AND '$f_end'";
                                if ($f_pegawai > 0) $where .= " AND l.pegawai_id = $f_pegawai";
                                if ($f_status) $where .= " AND l.status = '$f_status'";
                                
                                $query = "SELECT l.*, p.nama_lengkap FROM logbook l JOIN pegawai p ON l.pegawai_id=p.pegawai_id $where ORDER BY l.tanggal DESC, l.created_at DESC";
                                $res = mysqli_query($koneksi, $query);
                                $modals = '';
                                if ($res && mysqli_num_rows($res) > 0) {
                                    while ($r = mysqli_fetch_assoc($res)) {
                                        $badge = $r['status']==='Disetujui'?'success':($r['status']==='Ditolak'?'danger':'warning');
                                        $modalId = 'modalVerif'.$r['logbook_id'];
                                        
                                        echo '<tr>';
                                        echo '<td><strong>'.htmlspecialchars($r['nama_lengkap']).'</strong></td>';
                                        echo '<td>'.date('d/m/Y', strtotime($r['tanggal'])).'</td>';
                                        echo '<td>'.nl2br(htmlspecialchars($r['kegiatan'])).'</td>';
                                        echo '<td>'.htmlspecialchars($r['hasil'] ?: '-').'</td>';
                                        echo '<td><span class="badge bg-'.$badge.'">'.$r['status'].'</span></td>';
                                        echo '<td>';
                                        if ($r['status'] === 'Pending') {
                                            echo '<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#'.$modalId.'"><i class="ti ti-check me-1"></i>Verifikasi</button>';
                                        } else {
                                            echo '<button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#'.$modalId.'"><i class="ti ti-eye"></i></button>';
                                        }
                                        echo '</td>';
                                        echo '</tr>';

                                        // Modal Verifikasi
                                        $modals .= '
                                        <div class="modal fade" id="'.$modalId.'" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <form method="post" class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Verifikasi Logbook</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="logbook_id" value="'.$r['logbook_id'].'">
                                                        <div class="mb-3">
                                                            <label class="form-label">Pegawai</label>
                                                            <p class="fw-bold">'.htmlspecialchars($r['nama_lengkap']).'</p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Kegiatan</label>
                                                            <p class="bg-light p-2 rounded">'.nl2br(htmlspecialchars($r['kegiatan'])).'</p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Status Verifikasi</label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="Disetujui" '.($r['status']==='Disetujui'?'selected':'').'>Setujui</option>
                                                                <option value="Ditolak" '.($r['status']==='Ditolak'?'selected':'').'>Tolak</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Komentar/Catatan</label>
                                                            <textarea name="komentar" class="form-control" rows="3" placeholder="Masukkan alasan jika ditolak atau catatan tambahan...">'.htmlspecialchars($r['komentar_verifikator'] ?? '').'</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                                                        <button type="submit" name="verifikasi" class="btn btn-primary">Simpan Verifikasi</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>';
                                    }
                                } else { echo '<tr><td colspan="6" class="text-center text-muted">Tidak ada data logbook yang ditemukan</td></tr>'; }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo $modals; ?>
<?php include '../../includes/footer.php'; ?>