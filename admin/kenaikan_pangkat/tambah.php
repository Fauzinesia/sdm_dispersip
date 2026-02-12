<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = intval($_POST['pegawai_id'] ?? 0);
    $pangkat_lama_id = intval($_POST['pangkat_lama_id'] ?? 0);
    $pangkat_baru_id = intval($_POST['pangkat_baru_id'] ?? 0);
    $nomor_sk = trim($_POST['nomor_sk'] ?? '');
    $tanggal_sk = $_POST['tanggal_sk'] ?? '';
    $tmt = $_POST['tmt'] ?? '';

    $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
    if (!$pegawai_id || !$pangkat_baru_id || !$tmt || !preg_match($dateRegex, $tmt)) {
        $error = 'Harap isi pegawai, pangkat baru, dan TMT dengan benar.';
    } else {
        $filePathRel = '';
        if (!empty($_FILES['file_sk']['name']) && isset($_FILES['file_sk']) && $_FILES['file_sk']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['pdf','jpg','jpeg','png'];
            $ext = strtolower(pathinfo($_FILES['file_sk']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed, true)) {
                $root = dirname(__DIR__, 2);
                $dirFS = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'kp';
                if (!is_dir($dirFS)) { @mkdir($dirFS, 0777, true); }
                $basename = 'sk_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destFS = $dirFS . DIRECTORY_SEPARATOR . $basename;
                if (move_uploaded_file($_FILES['file_sk']['tmp_name'], $destFS)) {
                    $filePathRel = 'uploads/kp/' . $basename;
                }
            }
        }

        $stmt = mysqli_prepare($koneksi, "INSERT INTO kenaikan_pangkat (pegawai_id, pangkat_lama_id, pangkat_baru_id, nomor_sk, tanggal_sk, tmt, file_sk) VALUES (?, NULLIF(?, 0), ?, NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?, ''))");
        mysqli_stmt_bind_param($stmt, 'iiissss', $pegawai_id, $pangkat_lama_id, $pangkat_baru_id, $nomor_sk, $tanggal_sk, $tmt, $filePathRel);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            
            // Update pangkat di tabel pegawai
            $updatePegawai = mysqli_prepare($koneksi, "UPDATE pegawai SET pangkat_id = ? WHERE pegawai_id = ?");
            mysqli_stmt_bind_param($updatePegawai, 'ii', $pangkat_baru_id, $pegawai_id);
            mysqli_stmt_execute($updatePegawai);
            mysqli_stmt_close($updatePegawai);

            header("Location: /sdm_dispersip/admin/kenaikan_pangkat/kenaikan_pangkat.php?msg=".urlencode('Data berhasil ditambah')."&type=success");
            exit();
        }
        mysqli_stmt_close($stmt);
        $error = 'Gagal menambah data.';
    }
}

$pegawaiQuery = "SELECT p.pegawai_id, p.nama_lengkap, p.tgl_mulai_kerja, p.pangkat_id as current_pangkat_id,
                (SELECT MAX(tmt) FROM kenaikan_pangkat WHERE pegawai_id = p.pegawai_id) as tmt_terakhir_kp 
                FROM pegawai p 
                WHERE p.status_aktif='Aktif' 
                ORDER BY p.nama_lengkap";
$pegawaiRes = mysqli_query($koneksi, $pegawaiQuery);
$hasPegawai = $pegawaiRes && mysqli_num_rows($pegawaiRes) > 0;
$pangkatRes = mysqli_query($koneksi, "SELECT pangkat_id, nama_pangkat FROM master_pangkat ORDER BY nama_pangkat");
$hasPangkat = $pangkatRes && mysqli_num_rows($pangkatRes) > 0;

$page_title = "Tambah Kenaikan Pangkat";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container">
  <div class="page-content">
    <div class="container-xxl">
      <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Tambah Kenaikan Pangkat</h4></div></div></div>
      <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
      <?php endif; ?>
      <div class="card">
        <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Form Tambah Kenaikan Pangkat</h5>
          <a href="admin/kenaikan_pangkat/kenaikan_pangkat.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
        </div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Pegawai <span class="text-danger">*</span></label>
                <select name="pegawai_id" class="form-select" required <?php echo !$hasPegawai ? 'disabled' : ''; ?>>
                  <option value="">- Pilih Pegawai -</option>
                  <?php if ($hasPegawai) { 
                      while ($p = mysqli_fetch_assoc($pegawaiRes)) { 
                           $currentTmt = $p['tmt_terakhir_kp'] ? $p['tmt_terakhir_kp'] : $p['tgl_mulai_kerja'];
                           $nextTmt = ($currentTmt) ? date('Y-m-d', strtotime('+4 years', strtotime($currentTmt))) : '';
                           $pangkatLamaId = $p['current_pangkat_id'] ? $p['current_pangkat_id'] : 0;
                           echo '<option value="'.htmlspecialchars($p['pegawai_id']).'" data-next-tmt="'.$nextTmt.'" data-pangkat-lama="'.$pangkatLamaId.'">'.htmlspecialchars($p['nama_lengkap']).'</option>'; 
                      } 
                  } ?>
                </select>
                <?php if (!$hasPegawai): ?>
                  <small class="text-muted">Belum ada pegawai aktif. Tambahkan data di <a href="admin/pegawai/tambah.php">menu Pegawai</a>.</small>
                <?php endif; ?>
              </div>
              <div class="col-md-3">
                <label class="form-label">Pangkat Lama</label>
                <select name="pangkat_lama_id" class="form-select" <?php echo !$hasPangkat ? 'disabled' : ''; ?>>
                  <option value="0">- Pilih Pangkat Lama -</option>
                  <?php if ($hasPangkat) { mysqli_data_seek($pangkatRes, 0); while ($pg = mysqli_fetch_assoc($pangkatRes)) { echo '<option value="'.htmlspecialchars($pg['pangkat_id']).'">'.htmlspecialchars($pg['nama_pangkat']).'</option>'; } } ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Pangkat Baru <span class="text-danger">*</span></label>
                <select name="pangkat_baru_id" class="form-select" required <?php echo !$hasPangkat ? 'disabled' : ''; ?>>
                  <option value="">- Pilih Pangkat Baru -</option>
                  <?php if ($hasPangkat) { mysqli_data_seek($pangkatRes, 0); while ($pg2 = mysqli_fetch_assoc($pangkatRes)) { echo '<option value="'.htmlspecialchars($pg2['pangkat_id']).'">'.htmlspecialchars($pg2['nama_pangkat']).'</option>'; } } ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Nomor SK</label>
                <input type="text" name="nomor_sk" class="form-control" placeholder="Nomor SK">
              </div>
              <div class="col-md-4">
                <label class="form-label">Tanggal SK</label>
                <input type="date" name="tanggal_sk" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">TMT <span class="text-danger">*</span></label>
                <input type="date" name="tmt" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">File SK (pdf/jpg/png)</label>
                <input type="file" name="file_sk" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
              </div>
            </div>
            <div class="mt-3">
              <button type="submit" class="btn btn-primary" <?php echo (!$hasPegawai || !$hasPangkat) ? 'disabled' : ''; ?>><i class="ti ti-device-floppy me-1"></i>Simpan</button>
              <a href="admin/kenaikan_pangkat/kenaikan_pangkat.php" class="btn btn-light ms-2"><i class="ti ti-x me-1"></i>Batal</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pegawaiSelect = document.querySelector('select[name="pegawai_id"]');
    const tmtInput = document.querySelector('input[name="tmt"]');
    const pangkatLamaSelect = document.querySelector('select[name="pangkat_lama_id"]');

    if (pegawaiSelect) {
        pegawaiSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            // Auto-fill TMT
            if (tmtInput) {
                const nextTmt = selectedOption.getAttribute('data-next-tmt');
                if (nextTmt) {
                    tmtInput.value = nextTmt;
                } else {
                    tmtInput.value = '';
                }
            }

            // Auto-select Pangkat Lama
            if (pangkatLamaSelect) {
                const pangkatLamaId = selectedOption.getAttribute('data-pangkat-lama');
                if (pangkatLamaId && pangkatLamaId !== '0') {
                    pangkatLamaSelect.value = pangkatLamaId;
                    // Optional: Trigger change event on pangkatLamaSelect if needed
                    pangkatLamaSelect.dispatchEvent(new Event('change'));
                } else {
                    pangkatLamaSelect.value = '0';
                }
            }
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>

