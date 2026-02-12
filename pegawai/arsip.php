<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'pegawai') { header("Location: ../admin/dashboard.php"); exit(); }
require_once '../config/koneksi.php';
$page_title = "Arsip Dokumen Saya";
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';
$error = '';
$success = '';
$pid = intval($_SESSION['pegawai_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_dokumen'] ?? '');
    $jenis = trim($_POST['jenis_dokumen'] ?? '');
    if ($pid <= 0) {
        $error = 'Pegawai tidak valid';
    } elseif ($nama === '' || !isset($_FILES['file_dokumen'])) {
        $error = 'Nama dokumen dan file wajib diisi';
    } else {
        $allowed = ['pdf','jpg','jpeg','png','doc','docx','xls','xlsx'];
        $maxSize = 5 * 1024 * 1024;
        $file = $_FILES['file_dokumen'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Gagal mengunggah file';
        } elseif ($file['size'] > $maxSize) {
            $error = 'Ukuran file melebihi batas 5MB';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $error = 'Tipe file tidak diizinkan';
            } else {
                $safeBase = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
                $newName = date('Ymd_His') . '_' . $safeBase . '.' . $ext;
                $dirFS = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pegawai_' . $pid;
                $dirWeb = 'uploads/pegawai_' . $pid;
                if (!is_dir($dirFS)) { @mkdir($dirFS, 0775, true); }
                $destFS = $dirFS . DIRECTORY_SEPARATOR . $newName;
                $destWeb = $dirWeb . '/' . $newName;
                if (move_uploaded_file($file['tmp_name'], $destFS)) {
                    $stmt = mysqli_prepare($koneksi, "INSERT INTO arsip_dokumen (pegawai_id, jenis_dokumen, nama_dokumen, file_path, uploaded_by) VALUES (?,?,?,?,?)");
                    $uploadedBy = intval($_SESSION['user_id']);
                    mysqli_stmt_bind_param($stmt, 'isssi', $pid, $jenis, $nama, $destWeb, $uploadedBy);
                    if (mysqli_stmt_execute($stmt)) { $success = 'Dokumen berhasil ditambahkan'; } else { $error = 'Gagal menyimpan ke database'; }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = 'Gagal memindahkan file';
                }
            }
        }
    }
}
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Arsip Dokumen Saya</h4></div></div></div>
<?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<div class="card mb-3"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Tambah Dokumen</h5></div><div class="card-body">
<form method="post" enctype="multipart/form-data">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nama Dokumen</label><input type="text" name="nama_dokumen" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Jenis</label><input type="text" name="jenis_dokumen" class="form-control" placeholder="Misal: SK, Sertifikat"></div>
        <div class="col-md-3"><label class="form-label">File</label><input type="file" name="file_dokumen" class="form-control" required></div>
    </div>
    <div class="mt-3"><button type="submit" class="btn btn-primary"><i class="ti ti-upload me-1"></i>Unggah</button></div>
    <p class="text-muted mt-2">Format diizinkan: pdf, jpg, png, doc, docx, xls, xlsx. Maks 5MB.</p>
</form>
</div></div>
<div class="card"><div class="card-body">
<div class="table-responsive-sm"><table class="table table-striped mb-0"><thead><tr><th>Nama Dokumen</th><th>Jenis</th><th>File</th><th>Diunggah</th></tr></thead><tbody>
<?php $res = mysqli_query($koneksi, "SELECT * FROM arsip_dokumen WHERE pegawai_id=".$pid." ORDER BY created_at DESC"); if ($res && mysqli_num_rows($res)>0){ while($r=mysqli_fetch_assoc($res)){ echo '<tr>' . '<td>'.htmlspecialchars($r['nama_dokumen']).'</td>' . '<td>'.htmlspecialchars($r['jenis_dokumen'] ?? '-').'</td>' . '<td><a href="'.htmlspecialchars($r['file_path']).'" target="_blank">Lihat</a></td>' . '<td>'.htmlspecialchars($r['created_at']).'</td>' . '</tr>'; } } else { echo '<tr><td colspan="4" class="text-center">Belum ada dokumen</td></tr>'; } ?>
</tbody></table></div>
</div></div>
</div></div></div>
<?php include '../includes/footer.php'; ?>
