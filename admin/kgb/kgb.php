<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_status'])) {
  $id = intval($_POST['kgb_id'] ?? 0);
  $newStatus = $_POST['new_status'] ?? '';
  $allowed = ['Draft','Disahkan'];
  if ($id > 0 && in_array($newStatus, $allowed, true)) {
    $stmt = mysqli_prepare($koneksi, "UPDATE kgb SET status=? WHERE kgb_id=?");
    mysqli_stmt_bind_param($stmt, 'si', $newStatus, $id);
    if (mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); header("Location: kgb.php?msg=".urlencode('Status diperbarui')."&type=success"); exit(); }
    mysqli_stmt_close($stmt); header("Location: kgb.php?msg=".urlencode('Gagal memperbarui status')."&type=danger"); exit();
  }
  header("Location: kgb.php?msg=".urlencode('Input tidak valid')."&type=danger");
  exit();
}
$page_title = "Kenaikan Gaji Berkala";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="page-container">
  <div class="page-content">
    <div class="container-xxl">
      <?php
      $allowedSort = [
        'nama_lengkap' => 'p.nama_lengkap',
        'nomor_sk' => 'k.nomor_sk',
        'tanggal_sk' => 'k.tanggal_sk',
        'tmt_mulai' => 'k.tmt_mulai',
        'gaji_lama' => 'k.gaji_lama',
        'gaji_baru' => 'k.gaji_baru',
        'jadwal_kgb_berikut' => 'k.jadwal_kgb_berikut',
        'status' => 'k.status',
        'created_at' => 'k.created_at'
      ];
      $sort = $_GET['sort'] ?? 'created_at';
      $order = strtolower($_GET['order'] ?? 'desc');
      $order = $order === 'asc' ? 'ASC' : 'DESC';
      $sortCol = $allowedSort[$sort] ?? $allowedSort['created_at'];
      $page = max(1, intval($_GET['page'] ?? 1));
      $perPage = 10;
      $offset = ($page - 1) * $perPage;

      $f_status = $_GET['f_status'] ?? '';
      $f_from_sk = $_GET['f_from_sk'] ?? '';
      $f_to_sk = $_GET['f_to_sk'] ?? '';
      $f_from_tmt = $_GET['f_from_tmt'] ?? '';
      $f_to_tmt = $_GET['f_to_tmt'] ?? '';
      $f_q = trim($_GET['f_q'] ?? '');
      $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
      if ($f_from_sk && !preg_match($dateRegex, $f_from_sk)) $f_from_sk = '';
      if ($f_to_sk && !preg_match($dateRegex, $f_to_sk)) $f_to_sk = '';
      if ($f_from_tmt && !preg_match($dateRegex, $f_from_tmt)) $f_from_tmt = '';
      if ($f_to_tmt && !preg_match($dateRegex, $f_to_tmt)) $f_to_tmt = '';
      ?>
      <div class="row">
        <div class="col-12">
          <div class="page-title-box">
            <h4 class="page-title">Kenaikan Gaji Berkala</h4>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header border-bottom border-dashed d-flex align-items-center"><h5 class="card-title mb-0">Filter Data</h5></div>
        <div class="card-body">
          <form method="get" action="kgb.php" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="f_status" class="form-select">
                <option value="">- Semua -</option>
                <option value="Draft" <?php echo $f_status==='Draft'?'selected':''; ?>>Draft</option>
                <option value="Disahkan" <?php echo $f_status==='Disahkan'?'selected':''; ?>>Disahkan</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tanggal SK (dari)</label>
              <input type="date" name="f_from_sk" value="<?php echo htmlspecialchars($f_from_sk); ?>" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">Tanggal SK (sampai)</label>
              <input type="date" name="f_to_sk" value="<?php echo htmlspecialchars($f_to_sk); ?>" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">TMT Mulai (dari)</label>
              <input type="date" name="f_from_tmt" value="<?php echo htmlspecialchars($f_from_tmt); ?>" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">TMT Mulai (sampai)</label>
              <input type="date" name="f_to_tmt" value="<?php echo htmlspecialchars($f_to_tmt); ?>" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Cari Nama/No SK</label>
              <input type="text" name="f_q" value="<?php echo htmlspecialchars($f_q); ?>" class="form-control" placeholder="Masukkan nama atau nomor SK">
            </div>
            <div class="col-md-6 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button>
              <a href="admin/kgb/kgb.php" class="btn btn-light">Reset</a>
            </div>
          </form>
        </div>
      </div>

      <?php
      $where = [];
      if ($f_status) { $where[] = "k.status = '".mysqli_real_escape_string($koneksi, $f_status)."'"; }
      if ($f_from_sk) { $where[] = "k.tanggal_sk >= '".$f_from_sk."'"; }
      if ($f_to_sk) { $where[] = "k.tanggal_sk <= '".$f_to_sk."'"; }
      if ($f_from_tmt) { $where[] = "k.tmt_mulai >= '".$f_from_tmt."'"; }
      if ($f_to_tmt) { $where[] = "k.tmt_mulai <= '".$f_to_tmt."'"; }
      if ($f_q) { $q = mysqli_real_escape_string($koneksi, $f_q); $where[] = "(p.nama_lengkap LIKE '%$q%' OR k.nomor_sk LIKE '%$q%')"; }
      $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
      $countSql = "SELECT COUNT(*) AS c FROM kgb k JOIN pegawai p ON k.pegawai_id=p.pegawai_id $whereSql";
      $countRes = mysqli_query($koneksi, $countSql);
      $total = 0; if ($countRes) { $cr = mysqli_fetch_assoc($countRes); $total = intval($cr['c'] ?? 0); }
      ?>

      <div class="card">
        <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
            <div>
              <a href="admin/kgb/tambah.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</a>
              <?php
              $cetakUrl = '/sdm_dispersip/admin/kgb/cetak.php?'
              . 'f_status=' . urlencode($f_status)
              . '&f_from_sk=' . urlencode($f_from_sk)
              . '&f_to_sk=' . urlencode($f_to_sk)
              . '&f_from_tmt=' . urlencode($f_from_tmt)
              . '&f_to_tmt=' . urlencode($f_to_tmt)
              . '&f_q=' . urlencode($f_q);
            ?>
            <a href="<?php echo $cetakUrl; ?>" target="_blank" class="btn btn-secondary"><i class="ti ti-printer me-1"></i>Cetak</a>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive-sm">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>No</th>
                  <th><a class="link-reset" href="admin/kgb/kgb.php?sort=nama_lengkap&order=<?php echo ($sort==='nama_lengkap' && $order==='ASC')?'desc':'asc'; ?>">Nama Pegawai<?php echo $sort==='nama_lengkap' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/kgb/kgb.php?sort=nomor_sk&order=<?php echo ($sort==='nomor_sk' && $order==='ASC')?'desc':'asc'; ?>">No SK<?php echo $sort==='nomor_sk' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/kgb/kgb.php?sort=tanggal_sk&order=<?php echo ($sort==='tanggal_sk' && $order==='ASC')?'desc':'asc'; ?>">Tanggal SK<?php echo $sort==='tanggal_sk' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/kgb/kgb.php?sort=tmt_mulai&order=<?php echo ($sort==='tmt_mulai' && $order==='ASC')?'desc':'asc'; ?>">TMT Mulai<?php echo $sort==='tmt_mulai' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/kgb/kgb.php?sort=gaji_lama&order=<?php echo ($sort==='gaji_lama' && $order==='ASC')?'desc':'asc'; ?>">Gaji Lama<?php echo $sort==='gaji_lama' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/kgb/kgb.php?sort=gaji_baru&order=<?php echo ($sort==='gaji_baru' && $order==='ASC')?'desc':'asc'; ?>">Gaji Baru<?php echo $sort==='gaji_baru' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/kgb/kgb.php?sort=jadwal_kgb_berikut&order=<?php echo ($sort==='jadwal_kgb_berikut' && $order==='ASC')?'desc':'asc'; ?>">Jadwal Berikut<?php echo $sort==='jadwal_kgb_berikut' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/kgb/kgb.php?sort=status&order=<?php echo ($sort==='status' && $order==='ASC')?'desc':'asc'; ?>">Status<?php echo $sort==='status' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sql = "SELECT k.*, p.nama_lengkap FROM kgb k JOIN pegawai p ON k.pegawai_id=p.pegawai_id $whereSql ORDER BY $sortCol $order LIMIT $perPage OFFSET $offset";
                $res = mysqli_query($koneksi, $sql);
                if ($res && mysqli_num_rows($res) > 0) {
                  $no = $offset + 1;
                  while ($row = mysqli_fetch_assoc($res)) {
                    echo '<tr>'
                      . '<td>'.($no++).'</td>'
                      . '<td>'.htmlspecialchars($row['nama_lengkap']).'</td>'
                      . '<td>'.htmlspecialchars($row['nomor_sk'] ?? '-').'</td>'
                      . '<td>'.($row['tanggal_sk'] ? date('d/m/Y', strtotime($row['tanggal_sk'])) : '-').'</td>'
                      . '<td>'.($row['tmt_mulai'] ? date('d/m/Y', strtotime($row['tmt_mulai'])) : '-').'</td>'
                      . '<td>'.(is_null($row['gaji_lama']) ? '-' : number_format((float)$row['gaji_lama'], 2)).'</td>'
                      . '<td>'.(is_null($row['gaji_baru']) ? '-' : number_format((float)$row['gaji_baru'], 2)).'</td>'
                      . '<td>'.($row['jadwal_kgb_berikut'] ? date('d/m/Y', strtotime($row['jadwal_kgb_berikut'])) : '-').'</td>'
                      . '<td>'.htmlspecialchars($row['status']).'</td>'
                      . '<td>'
                        . '<a href="admin/kgb/detail.php?id=' . htmlspecialchars($row['kgb_id']) . '" class="btn btn-sm btn-info me-1"><i class="ti ti-eye me-1"></i>Detail</a>'
                        . '<a href="admin/kgb/edit.php?id=' . htmlspecialchars($row['kgb_id']) . '" class="btn btn-sm btn-warning me-1"><i class="ti ti-pencil me-1"></i>Edit</a>'
                        . '<a href="admin/kgb/hapus.php?id=' . htmlspecialchars($row['kgb_id']) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Hapus data ini?\')"><i class="ti ti-trash me-1"></i>Hapus</a>'
                        . (($row['status']==='Draft')
                          ? ('<form method="post" class="d-inline ms-1"><input type="hidden" name="kgb_id" value="'.htmlspecialchars($row['kgb_id']).'"><input type="hidden" name="new_status" value="Disahkan"><button type="submit" name="set_status" class="btn btn-sm btn-success"><i class="ti ti-check me-1"></i>Sahkan</button></form>')
                          : '')
                      . '</td>'
                      . '</tr>';
                  }
                } else {
                  echo '<tr><td colspan="11" class="text-center">Tidak ada data</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
          <?php
          $totalPages = (int)ceil($total / $perPage);
          if ($totalPages > 1):
            $baseQS = 'admin/kgb/kgb.php?sort=' . urlencode($sort) . '&order=' . strtolower($order)
              . '&f_status=' . urlencode($f_status)
              . '&f_from_sk=' . urlencode($f_from_sk)
              . '&f_to_sk=' . urlencode($f_to_sk)
              . '&f_from_tmt=' . urlencode($f_from_tmt)
              . '&f_to_tmt=' . urlencode($f_to_tmt)
              . '&f_q=' . urlencode($f_q);
          ?>
          <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination mb-0">
              <li class="page-item <?php echo ($page<=1)?'disabled':''; ?>">
                <a class="page-link" href="<?php echo $baseQS . '&page=' . max(1, $page-1); ?>">Prev</a>
              </li>
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?php echo ($i===$page)?'active':''; ?>">
                <a class="page-link" href="<?php echo $baseQS . '&page=' . $i; ?>"><?php echo $i; ?></a>
              </li>
              <?php endfor; ?>
              <li class="page-item <?php echo ($page>=$totalPages)?'disabled':''; ?>">
                <a class="page-link" href="<?php echo $baseQS . '&page=' . min($totalPages, $page+1); ?>">Next</a>
              </li>
            </ul>
          </nav>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include '../../includes/footer.php'; ?>
