<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $role = $_POST['role'] ?? 'pegawai';
  $status = $_POST['status'] ?? 'Aktif';
  $pegawai_id = intval($_POST['pegawai_id'] ?? 0);
  $allowedRole = ['admin','pegawai','verifikator'];
  $allowedStatus = ['Aktif','Nonaktif'];
  if ($username === '') { $error = 'Username wajib diisi.'; }
  elseif (!in_array($role, $allowedRole, true)) { $error = 'Role tidak valid.'; }
  elseif (!in_array($status, $allowedStatus, true)) { $error = 'Status tidak valid.'; }
  if (empty($error)) {
    $cek = mysqli_prepare($koneksi, "SELECT COUNT(*) AS c FROM users WHERE username=?");
    mysqli_stmt_bind_param($cek, 's', $username);
    mysqli_stmt_execute($cek);
    $res = mysqli_stmt_get_result($cek);
    $exists = ($res && (intval(mysqli_fetch_assoc($res)['c'] ?? 0) > 0));
    mysqli_stmt_close($cek);
    if ($exists) { $error = 'Username sudah terdaftar.'; }
  }
  if (empty($error)) {
    if ($password === '') { $password = 'admin123'; }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($koneksi, "INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssss', $username, $hash, $role, $status);
    if (mysqli_stmt_execute($stmt)) {
      $newUserId = mysqli_insert_id($koneksi);
      mysqli_stmt_close($stmt);
      if ($pegawai_id > 0) {
        $cekPeg = mysqli_prepare($koneksi, "SELECT user_id FROM pegawai WHERE pegawai_id=?");
        mysqli_stmt_bind_param($cekPeg, 'i', $pegawai_id);
        mysqli_stmt_execute($cekPeg);
        $resPeg = mysqli_stmt_get_result($cekPeg);
        $peg = $resPeg ? mysqli_fetch_assoc($resPeg) : null;
        mysqli_stmt_close($cekPeg);
        if ($peg && empty($peg['user_id'])) {
          $up = mysqli_prepare($koneksi, "UPDATE pegawai SET user_id=? WHERE pegawai_id=?");
          mysqli_stmt_bind_param($up, 'ii', $newUserId, $pegawai_id);
          mysqli_stmt_execute($up);
          mysqli_stmt_close($up);
        }
      }
      header("Location: /sdm_dispersip/admin/users/users.php?msg=".urlencode('User berhasil ditambah')."&type=success"); exit();
    }
    mysqli_stmt_close($stmt); $error = 'Gagal menambah user.';
  }
}

$page_title = "Tambah User";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Tambah User</h4></div></div></div>
<?php if (!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<div class="card"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Form Tambah User</h5><a href="admin/users/users.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a></div>
<div class="card-body"><form method="post"><div class="row g-3">
<div class="col-md-4"><label class="form-label">Username <span class="text-danger">*</span></label><input type="text" name="username" class="form-control" required placeholder="Username"></div>
<div class="col-md-4"><label class="form-label">Password</label><input type="password" name="password" class="form-control" placeholder="Kosongkan untuk default"></div>
<div class="col-md-4"><label class="form-label">Role</label><select name="role" class="form-select"><option value="pegawai">Pegawai</option><option value="verifikator">Verifikator</option><option value="admin">Admin</option></select></div>
<div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option></select></div>
<?php $pegRes = mysqli_query($koneksi, "SELECT pegawai_id, nama_lengkap FROM pegawai WHERE user_id IS NULL ORDER BY nama_lengkap"); $hasPeg = $pegRes && mysqli_num_rows($pegRes)>0; ?>
<div class="col-md-8"><label class="form-label">Hubungkan ke Pegawai</label><select name="pegawai_id" class="form-select" <?php echo !$hasPeg ? 'disabled' : ''; ?>><option value="0">- Tidak dihubungkan -</option><?php if ($hasPeg) { while($p=mysqli_fetch_assoc($pegRes)){ echo '<option value="'.htmlspecialchars($p['pegawai_id']).'">'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?></select><?php if (!$hasPeg): ?><small class="text-muted">Semua pegawai sudah terhubung user.</small><?php endif; ?></div>
</div><div class="mt-3"><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button><a href="admin/users/users.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div></form></div></div>
</div></div></div>
<?php include '../../includes/footer.php'; ?>
