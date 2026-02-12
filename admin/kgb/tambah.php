<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = intval($_POST['pegawai_id'] ?? 0);
    $nomor_sk = trim($_POST['nomor_sk'] ?? '');
    $tanggal_sk = $_POST['tanggal_sk'] ?? '';
    $tmt_mulai = $_POST['tmt_mulai'] ?? '';
    $gaji_lama = ($_POST['gaji_lama'] !== '' ? floatval($_POST['gaji_lama']) : 0);
    $gaji_baru = ($_POST['gaji_baru'] !== '' ? floatval($_POST['gaji_baru']) : 0);
    $jadwal_kgb_berikut = $_POST['jadwal_kgb_berikut'] ?? '';
    $status = $_POST['status'] ?? 'Draft';

    $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
    if (!$pegawai_id || !$tmt_mulai || !preg_match($dateRegex, $tmt_mulai)) {
        $error = 'Harap isi pegawai dan TMT Mulai dengan benar.';
    } else {
        $filePathRel = '';
        if (!empty($_FILES['file_sk']['name']) && isset($_FILES['file_sk']) && $_FILES['file_sk']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['pdf','jpg','jpeg','png'];
            $ext = strtolower(pathinfo($_FILES['file_sk']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed, true)) {
                $root = dirname(__DIR__, 2);
                $dirFS = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'kgb';
                if (!is_dir($dirFS)) { @mkdir($dirFS, 0777, true); }
                $basename = 'sk_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destFS = $dirFS . DIRECTORY_SEPARATOR . $basename;
                if (move_uploaded_file($_FILES['file_sk']['tmp_name'], $destFS)) {
                    $filePathRel = 'uploads/kgb/' . $basename;
                }
            }
        }

        $stmt = mysqli_prepare($koneksi, "INSERT INTO kgb (pegawai_id, nomor_sk, tanggal_sk, tmt_mulai, gaji_lama, gaji_baru, jadwal_kgb_berikut, file_sk, status) VALUES (?, NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?, 0), NULLIF(?, 0), NULLIF(?, ''), NULLIF(?, ''), ?)");
        mysqli_stmt_bind_param($stmt, 'isssddsss', $pegawai_id, $nomor_sk, $tanggal_sk, $tmt_mulai, $gaji_lama, $gaji_baru, $jadwal_kgb_berikut, $filePathRel, $status);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: /sdm_dispersip/admin/kgb/kgb.php?msg=".urlencode('Data berhasil ditambah')."&type=success");
            exit();
        }
        mysqli_stmt_close($stmt);
        $error = 'Gagal menambah data.';
    }
}

$pegawaiRes = mysqli_query($koneksi, "SELECT p.pegawai_id, p.nama_lengkap FROM pegawai p JOIN users u ON p.user_id = u.user_id WHERE p.status_aktif='Aktif' AND u.role='pegawai' ORDER BY p.nama_lengkap");
$hasPegawai = $pegawaiRes && mysqli_num_rows($pegawaiRes) > 0;
$page_title = "Tambah KGB";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container">
  <div class="page-content">
    <div class="container-xxl">
      <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Tambah KGB</h4></div></div></div>
      <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
      <?php endif; ?>
      <div class="card">
        <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Form Tambah KGB</h5>
          <a href="admin/kgb/kgb.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
        </div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data" action="admin/kgb/tambah.php">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Pegawai <span class="text-danger">*</span></label>
                <select name="pegawai_id" class="form-select" required <?php echo !$hasPegawai ? 'disabled' : ''; ?>>
                  <option value="">- Pilih Pegawai -</option>
                  <?php if ($hasPegawai) { while ($p = mysqli_fetch_assoc($pegawaiRes)) { echo '<option value="'.htmlspecialchars($p['pegawai_id']).'">'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?>
                </select>
              </div>
              <div class="col-md-3"><label class="form-label">Nomor SK</label><input type="text" name="nomor_sk" class="form-control" placeholder="Nomor SK"></div>
              <div class="col-md-3"><label class="form-label">Tanggal SK</label><input type="date" name="tanggal_sk" class="form-control"></div>
              <div class="col-md-3"><label class="form-label">TMT Mulai <span class="text-danger">*</span></label><input type="date" name="tmt_mulai" class="form-control" required></div>
              <div class="col-md-3">
                <label class="form-label">Gaji Lama</label>
                <div class="input-group">
                  <input type="number" step="0.01" name="gaji_lama" class="form-control" placeholder="0.00">
                  <button type="button" class="btn btn-outline-secondary" id="btnCekGaji" title="Ambil dari KGB Terakhir"><i class="ti ti-refresh"></i></button>
                </div>
                <small class="text-muted" id="gajiFeedback"></small>
              </div>
              <div class="col-md-3"><label class="form-label">Gaji Baru</label><input type="number" step="0.01" name="gaji_baru" class="form-control" placeholder="0.00"></div>
              <div class="col-md-3"><label class="form-label">Jadwal KGB Berikut</label><input type="date" name="jadwal_kgb_berikut" class="form-control"></div>
              <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="Draft">Draft</option><option value="Disahkan">Disahkan</option></select></div>
              <div class="col-md-6"><label class="form-label">File SK (pdf/jpg/png)</label><input type="file" name="file_sk" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
            </div>
            <div class="mt-3"><button type="submit" class="btn btn-primary" <?php echo !$hasPegawai ? 'disabled' : ''; ?>><i class="ti ti-device-floppy me-1"></i>Simpan</button><a href="admin/kgb/kgb.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a></div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pegawaiSelect = document.querySelector('select[name="pegawai_id"]');
    const gajiInput = document.querySelector('input[name="gaji_lama"]');
    const btnCekGaji = document.getElementById('btnCekGaji');
    const gajiFeedback = document.getElementById('gajiFeedback');

    function fetchGaji() {
        const id = pegawaiSelect.value;
        if (!id) {
            gajiFeedback.textContent = 'Pilih pegawai dulu';
            gajiFeedback.className = 'text-danger';
            return;
        }

        gajiFeedback.textContent = 'Mencari data...';
        gajiFeedback.className = 'text-info';
        
        // Karena ada <base href="/sdm_dispersip/"> di header.php
        // Kita harus menyertakan path lengkap dari root aplikasi
        fetch('admin/kgb/ajax_get_last_gaji.php?id=' + id)
            .then(response => {
                if (!response.ok) throw new Error('Network error: ' + response.statusText);
                return response.json();
            })
            .then(data => {
                if (data.gaji_lama > 0) {
                    gajiInput.value = data.gaji_lama;
                    gajiFeedback.textContent = 'Gaji Lama diisi dari KGB terakhir (' + new Intl.NumberFormat('id-ID').format(data.gaji_lama) + ')';
                    gajiFeedback.className = 'text-success';
                    gajiInput.classList.add('is-valid');
                    setTimeout(() => gajiInput.classList.remove('is-valid'), 2000);
                } else {
                    gajiFeedback.textContent = 'Tidak ada riwayat KGB ditemukan';
                    gajiFeedback.className = 'text-warning';
                }
            })
            .catch(err => {
                console.error(err);
                gajiFeedback.textContent = 'Gagal mengambil data';
                gajiFeedback.className = 'text-danger';
            });
    }

    if (pegawaiSelect && gajiInput) {
        pegawaiSelect.addEventListener('change', fetchGaji);
        if (btnCekGaji) {
            btnCekGaji.addEventListener('click', fetchGaji);
        }
    }
});
</script>
<?php include '../../includes/footer.php'; ?>

