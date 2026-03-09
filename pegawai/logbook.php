<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'pegawai') { header("Location: ../admin/dashboard.php"); exit(); }
require_once '../config/koneksi.php';
$page_title = "Logbook Harian";
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';

$error=''; $success='';
$pegawai_id = intval($_SESSION['pegawai_id'] ?? 0);

// Handle Post (Tambah/Edit/Hapus)
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $kegiatan = $_POST['kegiatan'] ?? '';
    $hasil = $_POST['hasil'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    if ($action === 'tambah') {
        if (!empty(trim($kegiatan))) {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO logbook (pegawai_id, tanggal, kegiatan, hasil, status) VALUES (?,?,?,?, 'Pending')");
            mysqli_stmt_bind_param($stmt, 'isss', $pegawai_id, $tanggal, $kegiatan, $hasil);
            if (mysqli_stmt_execute($stmt)) { $success = 'Logbook berhasil ditambahkan'; } else { $error = 'Gagal menambahkan logbook'; }
            mysqli_stmt_close($stmt);
        } else { $error = 'Kegiatan wajib diisi'; }
    } elseif ($action === 'edit') {
        if ($id > 0 && !empty(trim($kegiatan))) {
            // Cek status, hanya bisa edit jika Pending atau Ditolak
            $check = mysqli_query($koneksi, "SELECT status FROM logbook WHERE logbook_id=$id AND pegawai_id=$pegawai_id");
            $log = mysqli_fetch_assoc($check);
            if ($log && $log['status'] !== 'Disetujui') {
                $stmt = mysqli_prepare($koneksi, "UPDATE logbook SET tanggal=?, kegiatan=?, hasil=?, status='Pending' WHERE logbook_id=? AND pegawai_id=?");
                mysqli_stmt_bind_param($stmt, 'sssii', $tanggal, $kegiatan, $hasil, $id, $pegawai_id);
                if (mysqli_stmt_execute($stmt)) { $success = 'Logbook berhasil diupdate'; } else { $error = 'Gagal update logbook'; }
                mysqli_stmt_close($stmt);
            } else { $error = 'Logbook yang sudah disetujui tidak dapat diubah'; }
        } else { $error = 'Data tidak lengkap'; }
    } elseif ($action === 'hapus') {
        if ($id > 0) {
            $check = mysqli_query($koneksi, "SELECT status FROM logbook WHERE logbook_id=$id AND pegawai_id=$pegawai_id");
            $log = mysqli_fetch_assoc($check);
            if ($log && $log['status'] !== 'Disetujui') {
                if (mysqli_query($koneksi, "DELETE FROM logbook WHERE logbook_id=$id AND pegawai_id=$pegawai_id")) {
                    $success = 'Logbook berhasil dihapus';
                } else { $error = 'Gagal menghapus logbook'; }
            } else { $error = 'Logbook yang sudah disetujui tidak dapat dihapus'; }
        }
    }
}

// Filter
$f_start = $_GET['f_start'] ?? date('Y-m-01');
$f_end = $_GET['f_end'] ?? date('Y-m-t');
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Logbook Harian</h4></div></div></div>
            
            <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header border-bottom border-dashed"><h5 class="card-title mb-0" id="formTitle">Tambah Logbook</h5></div>
                        <div class="card-body">
                            <form method="post" id="logbookForm">
                                <input type="hidden" name="action" id="formAction" value="tambah">
                                <input type="hidden" name="id" id="logbookId" value="">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal</label>
                                    <input type="date" name="tanggal" id="formTanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kegiatan <span class="text-danger">*</span></label>
                                    <textarea name="kegiatan" id="formKegiatan" class="form-control" rows="4" placeholder="Apa yang Anda kerjakan hari ini?" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Hasil/Output</label>
                                    <input type="text" name="hasil" id="formHasil" class="form-control" placeholder="Misal: 1 Dokumen Laporan, 5 Berkas, dll">
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100" id="submitBtn"><i class="ti ti-plus me-1"></i>Simpan</button>
                                    <button type="button" class="btn btn-light w-100" id="cancelBtn" style="display:none;" onclick="resetForm()">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header border-bottom border-dashed"><h5 class="card-title mb-0">Filter & Riwayat</h5></div>
                        <div class="card-body">
                            <form method="get" class="row g-2 mb-3">
                                <div class="col-md-4"><input type="date" name="f_start" class="form-control" value="<?php echo $f_start; ?>"></div>
                                <div class="col-md-4"><input type="date" name="f_end" class="form-control" value="<?php echo $f_end; ?>"></div>
                                <div class="col-md-4"><button type="submit" class="btn btn-info w-100"><i class="ti ti-filter me-1"></i>Filter</button></div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Kegiatan</th>
                                            <th>Hasil</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT * FROM logbook WHERE pegawai_id=$pegawai_id AND tanggal BETWEEN '$f_start' AND '$f_end' ORDER BY tanggal DESC";
                                        $res = mysqli_query($koneksi, $query);
                                        if ($res && mysqli_num_rows($res) > 0) {
                                            while ($r = mysqli_fetch_assoc($res)) {
                                                $badge = $r['status']==='Disetujui'?'success':($r['status']==='Ditolak'?'danger':'warning');
                                                $can_edit = $r['status'] !== 'Disetujui';
                                                
                                                echo '<tr>';
                                                echo '<td>'.date('d/m/y', strtotime($r['tanggal'])).'</td>';
                                                echo '<td>'.nl2br(htmlspecialchars($r['kegiatan'])).'</td>';
                                                echo '<td>'.htmlspecialchars($r['hasil'] ?: '-').'</td>';
                                                echo '<td>';
                                                echo '<span class="badge bg-'.$badge.'">'.$r['status'].'</span>';
                                                if ($r['status'] === 'Ditolak' && $r['komentar_verifikator']) {
                                                    echo '<br><small class="text-danger">Ket: '.htmlspecialchars($r['komentar_verifikator']).'</small>';
                                                }
                                                echo '</td>';
                                                echo '<td>';
                                                if ($can_edit) {
                                                    echo '<button class="btn btn-sm btn-warning me-1" onclick=\'editLog('.json_encode($r).')\'><i class="ti ti-pencil"></i></button>';
                                                    echo '<form method="post" class="d-inline" onsubmit="return confirm(\'Hapus logbook ini?\')">';
                                                    echo '<input type="hidden" name="action" value="hapus"><input type="hidden" name="id" value="'.$r['logbook_id'].'">';
                                                    echo '<button type="submit" class="btn btn-sm btn-danger"><i class="ti ti-trash"></i></button>';
                                                    echo '</form>';
                                                } else {
                                                    echo '<span class="text-muted"><i class="ti ti-lock"></i></span>';
                                                }
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                        } else { echo '<tr><td colspan="5" class="text-center text-muted">Belum ada logbook di periode ini</td></tr>'; }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editLog(data) {
    document.getElementById('formTitle').innerText = 'Edit Logbook';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('logbookId').value = data.logbook_id;
    document.getElementById('formTanggal').value = data.tanggal;
    document.getElementById('formKegiatan').value = data.kegiatan;
    document.getElementById('formHasil').value = data.hasil;
    document.getElementById('submitBtn').innerHTML = '<i class="ti ti-device-floppy me-1"></i>Update';
    document.getElementById('cancelBtn').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('formTitle').innerText = 'Tambah Logbook';
    document.getElementById('formAction').value = 'tambah';
    document.getElementById('logbookId').value = '';
    document.getElementById('formTanggal').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('formKegiatan').value = '';
    document.getElementById('formHasil').value = '';
    document.getElementById('submitBtn').innerHTML = '<i class="ti ti-plus me-1"></i>Simpan';
    document.getElementById('cancelBtn').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>