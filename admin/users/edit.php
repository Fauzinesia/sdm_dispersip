<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: /sdm_dispersip/admin/users/users.php?msg=".urlencode('ID tidak valid').'&type=danger'); exit(); }

$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE user_id=".(int)$id));
if (!$user) { header("Location: /sdm_dispersip/admin/users/users.php?msg=".urlencode('User tidak ditemukan').'&type=danger'); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['link_pegawai'])) {
        $pegawai_id = intval($_POST['pegawai_id'] ?? 0);
        if ($pegawai_id > 0) {
            $cekPeg = mysqli_prepare($koneksi, "SELECT user_id FROM pegawai WHERE pegawai_id=?");
            mysqli_stmt_bind_param($cekPeg, 'i', $pegawai_id);
            mysqli_stmt_execute($cekPeg);
            $resPeg = mysqli_stmt_get_result($cekPeg);
            $peg = $resPeg ? mysqli_fetch_assoc($resPeg) : null;
            mysqli_stmt_close($cekPeg);
            if ($peg && empty($peg['user_id'])) {
                $up = mysqli_prepare($koneksi, "UPDATE pegawai SET user_id=? WHERE pegawai_id=?");
                mysqli_stmt_bind_param($up, 'ii', $id, $pegawai_id);
                if (mysqli_stmt_execute($up)) { mysqli_stmt_close($up); $msg = urlencode('User terhubung ke pegawai'); header("Location: /sdm_dispersip/admin/users/users.php?msg=".$msg."&type=success"); exit(); }
                mysqli_stmt_close($up);
                $error = 'Gagal menghubungkan ke pegawai';
            } else {
                $error = 'Pegawai sudah terhubung user';
            }
        } else {
            $error = 'Pilih pegawai untuk dihubungkan';
        }
    } elseif (isset($_POST['unlink_pegawai'])) {
        $cekCurr = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT pegawai_id FROM pegawai WHERE user_id=".(int)$id));
        if ($cekCurr && intval($cekCurr['pegawai_id'])>0) {
            $up = mysqli_prepare($koneksi, "UPDATE pegawai SET user_id=NULL WHERE pegawai_id=?");
            $pid = intval($cekCurr['pegawai_id']);
            mysqli_stmt_bind_param($up, 'i', $pid);
            if (mysqli_stmt_execute($up)) { mysqli_stmt_close($up); $msg = urlencode('Koneksi user-pegawai dilepas'); header("Location: /sdm_dispersip/admin/users/users.php?msg=".$msg."&type=success"); exit(); }
            mysqli_stmt_close($up);
            $error = 'Gagal melepas koneksi user-pegawai';
        } else {
            $error = 'User belum terhubung dengan pegawai';
        }
    } else {
        $new = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');
        if ($new === '') {
            $error = 'Password baru tidak boleh kosong';
        } elseif ($new !== $confirm) {
            $error = 'Konfirmasi password tidak sama';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($koneksi, "UPDATE users SET password=?, updated_at=NOW() WHERE user_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $hash, $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                $msg = urlencode('Password berhasil diubah');
                header("Location: /sdm_dispersip/admin/users/users.php?msg=".$msg."&type=success");
                exit();
            }
            mysqli_stmt_close($stmt);
            $error = 'Gagal mengubah password';
        }
    }
}

$page_title = "Ubah Password User";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Ubah Password User</h4></div></div></div>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Edit Password: <?php echo htmlspecialchars($user['username']); ?></h5>
                    <a href="admin/users/users.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="new_password" class="form-control" required placeholder="Minimal 6 karakter">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" name="confirm_password" class="form-control" required placeholder="Ulangi password">
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Role</p>
                                <span class="badge bg-info"><?php echo htmlspecialchars($user['role']); ?></span>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Status</p>
                                <span class="badge bg-<?php echo ($user['status']==='Aktif')?'success':'secondary'; ?>"><?php echo htmlspecialchars($user['status']); ?></span>
                            </div>
                        </div>
                        <div class="mt-3"><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button><a href="admin/users/users.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div>
                    </form>
                </div>
            </div>
            <?php $linked = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT pegawai_id, nama_lengkap FROM pegawai WHERE user_id=".(int)$id)); $pegAvail = mysqli_query($koneksi, "SELECT pegawai_id AS pegawai_id, nama_lengkap FROM pegawai WHERE user_id IS NULL ORDER BY nama_lengkap"); ?>
            <div class="card mt-3">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Koneksi ke Pegawai</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Saat ini</label>
                            <p class="mb-0"><?php echo $linked ? htmlspecialchars($linked['nama_lengkap']) : '-'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <form method="post" class="d-flex align-items-end gap-2">
                                <div class="flex-grow-1">
                                    <label class="form-label">Hubungkan ke Pegawai</label>
                                    <select name="pegawai_id" class="form-select">
                                        <option value="0">- Pilih Pegawai -</option>
                                        <?php if ($pegAvail){ while($p=mysqli_fetch_assoc($pegAvail)){ echo '<option value="'.htmlspecialchars($p['pegawai_id']).'">'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?>
                                    </select>
                                </div>
                                <div>
                                    <button type="submit" name="link_pegawai" class="btn btn-secondary"><i class="ti ti-link me-1"></i>Hubungkan</button>
                                    <button type="submit" name="unlink_pegawai" class="btn btn-outline-danger" onclick="return confirm('Lepas koneksi user-pegawai?')"><i class="ti ti-unlink me-1"></i>Lepas</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
