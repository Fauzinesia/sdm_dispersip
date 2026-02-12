<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'verifikator') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$redirectBase = ($_SESSION['role'] === 'verifikator') ? '/sdm_dispersip/verifikator/penilaian_kinerja.php' : '/sdm_dispersip/admin/penilaian_kinerja/penilaian_kinerja.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pegawai_id = intval($_POST['pegawai_id'] ?? 0);
  $periode = trim($_POST['periode'] ?? '');
  $nilai_kuantitas = ($_POST['nilai_kuantitas'] !== '' ? floatval($_POST['nilai_kuantitas']) : 0);
  $nilai_kualitas = ($_POST['nilai_kualitas'] !== '' ? floatval($_POST['nilai_kualitas']) : 0);
  $nilai_perilaku = ($_POST['nilai_perilaku'] !== '' ? floatval($_POST['nilai_perilaku']) : 0);
  $komentar = trim($_POST['komentar'] ?? '');
  $penilai_user_id = intval($_SESSION['user_id']);

  if (!$pegawai_id || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
    $error = 'Harap isi pegawai dan periode (YYYY-MM) dengan benar.';
  } else {
    $cek = mysqli_prepare($koneksi, "SELECT COUNT(*) AS c FROM penilaian_kinerja WHERE pegawai_id=? AND periode=?");
    mysqli_stmt_bind_param($cek, 'is', $pegawai_id, $periode);
    mysqli_stmt_execute($cek);
    $cekRes = mysqli_stmt_get_result($cek);
    $exists = ($cekRes && (intval(mysqli_fetch_assoc($cekRes)['c'] ?? 0) > 0));
    mysqli_stmt_close($cek);
    if ($exists) {
      $error = 'Penilaian untuk pegawai dan periode ini sudah ada.';
    } else {
      $stmt = mysqli_prepare($koneksi, "INSERT INTO penilaian_kinerja (pegawai_id, periode, nilai_kuantitas, nilai_kualitas, nilai_perilaku, komentar, penilai_user_id) VALUES (?, ?, ?, ?, ?, NULLIF(?, ''), ?)");
      mysqli_stmt_bind_param($stmt, 'isssssi', $pegawai_id, $periode, $nilai_kuantitas, $nilai_kualitas, $nilai_perilaku, $komentar, $penilai_user_id);
      if (mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); header("Location: " . $redirectBase . "?msg=".urlencode('Data berhasil ditambah')."&type=success"); exit(); }
      mysqli_stmt_close($stmt); $error = 'Gagal menambah data.';
    }
  }
}

$pegawaiRes = mysqli_query($koneksi, "SELECT p.pegawai_id, p.nama_lengkap FROM pegawai p JOIN users u ON p.user_id = u.user_id WHERE p.status_aktif='Aktif' AND u.role='pegawai' ORDER BY p.nama_lengkap");
$hasPegawai = $pegawaiRes && mysqli_num_rows($pegawaiRes) > 0;
$page_title = "Tambah Penilaian Kinerja";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Tambah Penilaian Kinerja</h4></div></div></div>
<?php if (!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<div class="card"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Form Tambah Penilaian</h5><a href="<?php echo $redirectBase; ?>" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a></div>
<div class="card-body"><form method="post"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Pegawai <span class="text-danger">*</span></label><select name="pegawai_id" class="form-select" required <?php echo !$hasPegawai ? 'disabled' : ''; ?>><option value="">- Pilih Pegawai -</option><?php if ($hasPegawai) { while ($p = mysqli_fetch_assoc($pegawaiRes)) { echo '<option value="'.htmlspecialchars($p['pegawai_id']).'">'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?></select><?php if (!$hasPegawai): ?><small class="text-muted">Belum ada pegawai aktif. Tambahkan data di <a href="admin/pegawai/tambah.php">menu Pegawai</a>.</small><?php endif; ?></div>
<div class="col-md-3"><label class="form-label">Periode <span class="text-danger">*</span></label><input type="month" name="periode" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Kuantitas</label><input type="number" step="0.01" name="nilai_kuantitas" class="form-control" placeholder="0.00"></div>
<div class="col-md-3"><label class="form-label">Kualitas</label><input type="number" step="0.01" name="nilai_kualitas" class="form-control" placeholder="0.00"></div>
<div class="col-md-3"><label class="form-label">Perilaku</label><input type="number" step="0.01" name="nilai_perilaku" class="form-control" placeholder="0.00"></div>
<div class="col-md-12"><label class="form-label">Komentar</label><textarea name="komentar" class="form-control" rows="3" placeholder="Catatan penilai"></textarea></div>
</div><div class="mt-3"><button type="submit" class="btn btn-primary" <?php echo !$hasPegawai ? 'disabled' : ''; ?>><i class="ti ti-device-floppy me-1"></i>Simpan</button><a href="<?php echo $redirectBase; ?>" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div></form></div></div>
</div></div></div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['nilai_kuantitas', 'nilai_kualitas', 'nilai_perilaku'];
    const skorEl = document.getElementById('preview-skor');
    const predikatEl = document.getElementById('preview-predikat');

    function hitung() {
        let total = 0;
        let count = 0;
        inputs.forEach(name => {
            const val = parseFloat(document.querySelector(`input[name="${name}"]`).value) || 0;
            total += val;
            if (val > 0) count++; // Atau tetap bagi 3 jika wajib diisi semua
        });

        // Asumsi: Skor akhir adalah rata-rata dari 3 komponen
        // Jika belum diisi semua, tetap bagi 3 untuk melihat progress atau bagi jumlah yang diisi?
        // Biasanya penilaian kinerja membagi dengan total komponen (3)
        const skor = total / 3; 

        skorEl.textContent = skor.toFixed(2);

        let predikat = 'Sangat Kurang';
        let color = 'danger';

        if (skor >= 90) { predikat = 'Sangat Baik'; color = 'success'; }
        else if (skor >= 76) { predikat = 'Baik'; color = 'info'; }
        else if (skor >= 61) { predikat = 'Cukup'; color = 'warning'; }
        else if (skor >= 51) { predikat = 'Kurang'; color = 'danger'; }
        else { predikat = 'Sangat Kurang'; color = 'dark'; }

        predikatEl.textContent = predikat;
        predikatEl.className = `badge bg-${color}`;
    }

    inputs.forEach(name => {
        document.querySelector(`input[name="${name}"]`).addEventListener('input', hitung);
    });
});
</script>
<?php include '../../includes/footer.php'; ?>

