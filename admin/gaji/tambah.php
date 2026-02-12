<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pegawai_id = intval($_POST['pegawai_id'] ?? 0);
  $periode = trim($_POST['periode'] ?? '');
  $gaji_pokok = ($_POST['gaji_pokok'] !== '' ? floatval($_POST['gaji_pokok']) : 0);
  $tunjangan = ($_POST['tunjangan'] !== '' ? floatval($_POST['tunjangan']) : 0);
  $potongan = ($_POST['potongan'] !== '' ? floatval($_POST['potongan']) : 0);
  $keterangan = trim($_POST['keterangan'] ?? '');
  if (!$pegawai_id || !preg_match('/^\d{4}-\d{2}$/', $periode)) { $error = 'Harap isi pegawai dan periode (YYYY-MM) dengan benar.'; }
  if (empty($error)) {
    $cek = mysqli_prepare($koneksi, "SELECT COUNT(*) AS c FROM gaji WHERE pegawai_id=? AND periode=?");
    mysqli_stmt_bind_param($cek, 'is', $pegawai_id, $periode);
    mysqli_stmt_execute($cek);
    $res = mysqli_stmt_get_result($cek);
    $exists = ($res && (intval(mysqli_fetch_assoc($res)['c'] ?? 0) > 0));
    mysqli_stmt_close($cek);
    if ($exists) { $error = 'Data gaji untuk pegawai dan periode ini sudah ada.'; }
  }
  if (empty($error)) {
    $stmt = mysqli_prepare($koneksi, "INSERT INTO gaji (pegawai_id, periode, gaji_pokok, tunjangan, potongan, keterangan) VALUES (?, ?, ?, ?, ?, NULLIF(?, ''))");
    mysqli_stmt_bind_param($stmt, 'issdds', $pegawai_id, $periode, $gaji_pokok, $tunjangan, $potongan, $keterangan);
    if (mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); header("Location: /sdm_dispersip/admin/gaji/gaji.php?msg=".urlencode('Data berhasil ditambah')."&type=success"); exit(); }
    mysqli_stmt_close($stmt); $error = 'Gagal menambah data gaji.';
  }
}

$pegRes = mysqli_query($koneksi, "SELECT p.pegawai_id, p.nama_lengkap FROM pegawai p JOIN users u ON p.user_id = u.user_id WHERE p.status_aktif='Aktif' AND u.role='pegawai' ORDER BY p.nama_lengkap");
$hasPegawai = $pegRes && mysqli_num_rows($pegRes) > 0;
$page_title = "Tambah Data Gaji";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container"><div class="page-content"><div class="container-xxl">
<div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Tambah Data Gaji</h4></div></div></div>
<?php if (!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<div class="card"><div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Form Tambah Gaji</h5><a href="admin/gaji/gaji.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a></div>
<div class="card-body"><form method="post"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Pegawai <span class="text-danger">*</span></label><select name="pegawai_id" class="form-select" required <?php echo !$hasPegawai ? 'disabled' : ''; ?>><option value="">- Pilih Pegawai -</option><?php if ($hasPegawai) { while ($p = mysqli_fetch_assoc($pegRes)) { echo '<option value="'.htmlspecialchars($p['pegawai_id']).'">'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?></select></div>
<div class="col-md-3"><label class="form-label">Periode <span class="text-danger">*</span></label><input type="month" name="periode" class="form-control" required></div>
<div class="col-md-3">
    <label class="form-label">Gaji Pokok</label>
    <div class="input-group">
        <input type="number" step="0.01" name="gaji_pokok" class="form-control" placeholder="0.00">
        <button type="button" class="btn btn-outline-secondary" id="btnCekGaji" title="Ambil dari KGB Terakhir"><i class="ti ti-refresh"></i></button>
    </div>
    <small class="text-muted" id="gajiFeedback"></small>
</div>
<div class="col-md-3"><label class="form-label">Tunjangan</label><input type="number" step="0.01" name="tunjangan" class="form-control" placeholder="0.00"></div>
<div class="col-md-3"><label class="form-label">Potongan</label><input type="number" step="0.01" name="potongan" class="form-control" placeholder="0.00"></div>
<div class="col-md-12"><label class="form-label">Keterangan</label><textarea name="keterangan" class="form-control" rows="3" placeholder="Catatan"></textarea></div>
</div><div class="mt-3"><button type="submit" class="btn btn-primary" <?php echo !$hasPegawai ? 'disabled' : ''; ?>><i class="ti ti-device-floppy me-1"></i>Simpan</button><a href="admin/gaji/gaji.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div></form></div></div>
</div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pegawaiSelect = document.querySelector('select[name="pegawai_id"]');
    const gajiInput = document.querySelector('input[name="gaji_pokok"]');
    const btnCekGaji = document.getElementById('btnCekGaji');
    const gajiFeedback = document.getElementById('gajiFeedback');

    function fetchGaji() {
        const id = pegawaiSelect.value;
        if (!id) {
            gajiFeedback.textContent = 'Pilih pegawai dulu';
            gajiFeedback.className = 'text-danger';
            return;
        }

        gajiFeedback.textContent = 'Mencari data KGB...';
        gajiFeedback.className = 'text-info';
        
        // Karena ada <base href="/sdm_dispersip/"> di header.php
        // Path harus relative terhadap root sdm_dispersip
        fetch('admin/gaji/ajax_get_gaji.php?id=' + id)
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                if (data.gaji_pokok > 0) {
                    gajiInput.value = data.gaji_pokok;
                    gajiFeedback.textContent = 'Gaji diupdate dari KGB (' + new Intl.NumberFormat('id-ID').format(data.gaji_pokok) + ')';
                    gajiFeedback.className = 'text-success';
                    // Animasi highlight
                    gajiInput.classList.add('is-valid');
                    setTimeout(() => gajiInput.classList.remove('is-valid'), 2000);
                } else {
                    gajiFeedback.textContent = 'Tidak ada data KGB "Disahkan" ditemukan';
                    gajiFeedback.className = 'text-warning';
                    // Jangan kosongkan input jika user sudah isi manual
                    if (!gajiInput.value) gajiInput.value = '';
                }
            })
            .catch(err => {
                console.error(err);
                gajiFeedback.textContent = 'Gagal mengambil data';
                gajiFeedback.className = 'text-danger';
            });
    }

    if (pegawaiSelect && gajiInput) {
        // Auto trigger saat ganti pegawai
        pegawaiSelect.addEventListener('change', fetchGaji);
        
        // Manual trigger via tombol
        if (btnCekGaji) {
            btnCekGaji.addEventListener('click', fetchGaji);
        }
    }
});
</script>
<?php include '../../includes/footer.php'; ?>

