<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'pegawai') { header("Location: ../admin/dashboard.php"); exit(); }
require_once '../config/koneksi.php';
$page_title = "Pengajuan Cuti";
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';

$error=''; $success='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $pegawai_id = intval($_SESSION['pegawai_id'] ?? 0);
    $jenis_cuti = $_POST['jenis_cuti'] ?? 'Tahunan';
    $tgl_mulai = $_POST['tgl_mulai'] ?? null;
    $tgl_selesai = $_POST['tgl_selesai'] ?? null;
    $alasan = $_POST['alasan'] ?? null;
    $lama_hari = 0;
    if ($pegawai_id > 0 && $tgl_mulai && $tgl_selesai && !empty(trim($alasan))) {
        // Validasi Tahun Berjalan
        $curYear = date('Y');
        $y1 = date('Y', strtotime($tgl_mulai));
        $y2 = date('Y', strtotime($tgl_selesai));

        if ($y1 != $curYear || $y2 != $curYear) {
            $error = "Tanggal cuti harus berada di tahun berjalan ($curYear).";
        } else {
            $lama_hari = hitungHariKerja($koneksi, $tgl_mulai, $tgl_selesai);
            $stmt = mysqli_prepare($koneksi, "INSERT INTO cuti (pegawai_id, jenis_cuti, tgl_mulai, tgl_selesai, lama_hari, alasan, status) VALUES (?,?,?,?,?,?, 'Menunggu')");
            mysqli_stmt_bind_param($stmt, 'isssis', $pegawai_id, $jenis_cuti, $tgl_mulai, $tgl_selesai, $lama_hari, $alasan);
            if (mysqli_stmt_execute($stmt)) { $success = 'Pengajuan cuti dikirim'; } else { $error = 'Gagal mengajukan cuti'; }
            mysqli_stmt_close($stmt);
        }
    } else {
        if (empty(trim($alasan))) $error = 'Alasan cuti wajib diisi';
        else $error = 'Data tidak lengkap';
    }
}
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Pengajuan Cuti</h4></div></div></div>
            <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
            <div class="card mb-3">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Form Pengajuan</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">Jenis Cuti</label><select name="jenis_cuti" class="form-select"><option>Tahunan</option><option>Sakit</option><option>Melahirkan</option><option>Penting</option><option>Besar</option></select></div>
                            <?php $minDate = date('Y-01-01'); $maxDate = date('Y-12-31'); ?>
                            <div class="col-md-3"><label class="form-label">Mulai</label><input type="date" name="tgl_mulai" class="form-control" min="<?php echo $minDate; ?>" max="<?php echo $maxDate; ?>" required></div>
                            <div class="col-md-3"><label class="form-label">Selesai</label><input type="date" name="tgl_selesai" class="form-control" min="<?php echo $minDate; ?>" max="<?php echo $maxDate; ?>" required></div>
                            <div class="col-md-12"><label class="form-label">Alasan <span class="text-danger">*</span></label><textarea name="alasan" class="form-control" rows="2" placeholder="Alasan cuti wajib diisi" required></textarea></div>
                        </div>
                        <div class="mt-3"><button type="submit" class="btn btn-primary"><i class="ti ti-send me-1"></i>Kirim Pengajuan</button></div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex align-items-center"><h5 class="card-title mb-0">Riwayat Pengajuan</h5></div>
                <div class="card-body">
                    <div class="table-responsive-sm">
                        <table class="table table-striped mb-0"><thead><tr><th>Jenis</th><th>Mulai</th><th>Selesai</th><th>Lama</th><th>Status</th><th>Catatan</th></tr></thead><tbody>
                        <?php
                        $pegawai_id = intval($_SESSION['pegawai_id'] ?? 0);
                        $res = mysqli_query($koneksi, "SELECT * FROM cuti WHERE pegawai_id=".$pegawai_id." ORDER BY created_at DESC");
                        if ($res && mysqli_num_rows($res)>0) {
                            while ($r = mysqli_fetch_assoc($res)) {
                                $badge = $r['status']==='Disetujui'?'success':($r['status']==='Ditolak'?'danger':'warning');
                                $catatan = '-';
                                if ($r['status'] === 'Ditolak' && !empty($r['alasan_ditolak'])) {
                                    $catatan = '<span class="text-danger">'.htmlspecialchars($r['alasan_ditolak']).'</span>';
                                } elseif ($r['status'] === 'Disetujui' && !empty($r['disposisi'])) {
                                    $catatan = '<span class="text-success">'.htmlspecialchars($r['disposisi']).'</span>';
                                }

                                $btnCetak = '';
                                if ($r['status'] === 'Disetujui') {
                                    $btnCetak .= '<a href="pegawai/cetak_cuti.php?id='.$r['cuti_id'].'" target="_blank" class="btn btn-sm btn-secondary ms-1" title="Cetak Surat Izin"><i class="ti ti-printer"></i> Surat</a>';
                                }
                                // Tombol cetak formulir
                                $btnCetak .= '<a href="pegawai/cetak_form_cuti.php?id='.$r['cuti_id'].'" target="_blank" class="btn btn-sm btn-info ms-1" title="Cetak Formulir"><i class="ti ti-file-text"></i> Form</a>';

                                echo '<tr>'
                                    . '<td>'.htmlspecialchars($r['jenis_cuti']).'</td>'
                                    . '<td>'.htmlspecialchars(date('d/m/Y', strtotime($r['tgl_mulai']))).'</td>'
                                    . '<td>'.htmlspecialchars(date('d/m/Y', strtotime($r['tgl_selesai']))).'</td>'
                                    . '<td>'.(int)$r['lama_hari'].' hari</td>'
                                    . '<td><span class="badge bg-'.$badge.'">'.htmlspecialchars($r['status']).'</span>'.$btnCetak.'</td>'
                                    . '<td>'.$catatan.'</td>'
                                    . '</tr>';
                            }
                        } else { echo '<tr><td colspan="6" class="text-center">Belum ada data</td></tr>'; }
                        ?>
                        </tbody></table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

