<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$page_title = "Data Pensiun";
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
        'jenis' => 'ps.jenis',
        'nomor_sk' => 'ps.nomor_sk',
        'tanggal_sk' => 'ps.tanggal_sk',
        'tmt' => 'ps.tmt',
        'created_at' => 'ps.created_at'
      ];
      $sort = $_GET['sort'] ?? 'created_at';
      $order = strtolower($_GET['order'] ?? 'desc');
      $order = $order === 'asc' ? 'ASC' : 'DESC';
      $sortCol = $allowedSort[$sort] ?? $allowedSort['created_at'];
      $page = max(1, intval($_GET['page'] ?? 1));
      $perPage = 10;
      $offset = ($page - 1) * $perPage;

      $f_jenis = $_GET['f_jenis'] ?? '';
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
            <h4 class="page-title">Data Pensiun</h4>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header border-bottom border-dashed d-flex align-items-center"><h5 class="card-title mb-0">Filter Data</h5></div>
        <div class="card-body">
          <form method="get" action="admin/pensiun/pensiun.php" class="row g-3">
            <div class="col-md-3"><label class="form-label">Jenis</label><select name="f_jenis" class="form-select"><option value="">- Semua -</option><option value="BUP" <?php echo $f_jenis==='BUP'?'selected':''; ?>>BUP</option><option value="Dini" <?php echo $f_jenis==='Dini'?'selected':''; ?>>Dini</option><option value="Lainnya" <?php echo $f_jenis==='Lainnya'?'selected':''; ?>>Lainnya</option></select></div>
            <div class="col-md-3"><label class="form-label">Tanggal SK (dari)</label><input type="date" name="f_from_sk" value="<?php echo htmlspecialchars($f_from_sk); ?>" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Tanggal SK (sampai)</label><input type="date" name="f_to_sk" value="<?php echo htmlspecialchars($f_to_sk); ?>" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">TMT (dari)</label><input type="date" name="f_from_tmt" value="<?php echo htmlspecialchars($f_from_tmt); ?>" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">TMT (sampai)</label><input type="date" name="f_to_tmt" value="<?php echo htmlspecialchars($f_to_tmt); ?>" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Cari Nama/No SK</label><input type="text" name="f_q" value="<?php echo htmlspecialchars($f_q); ?>" class="form-control" placeholder="Masukkan nama atau nomor SK"></div>
            <div class="col-md-6 d-flex align-items-end gap-2"><button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button><a href="admin/pensiun/pensiun.php" class="btn btn-light">Reset</a></div>
          </form>
        </div>
      </div>

      <?php
      $where = [];
      if ($f_jenis) { $where[] = "ps.jenis = '".mysqli_real_escape_string($koneksi, $f_jenis)."'"; }
      if ($f_from_sk) { $where[] = "ps.tanggal_sk >= '".$f_from_sk."'"; }
      if ($f_to_sk) { $where[] = "ps.tanggal_sk <= '".$f_to_sk."'"; }
      if ($f_from_tmt) { $where[] = "ps.tmt >= '".$f_from_tmt."'"; }
      if ($f_to_tmt) { $where[] = "ps.tmt <= '".$f_to_tmt."'"; }
      if ($f_q) { $q = mysqli_real_escape_string($koneksi, $f_q); $where[] = "(p.nama_lengkap LIKE '%$q%' OR ps.nomor_sk LIKE '%$q%')"; }
      $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
      $countSql = "SELECT COUNT(*) AS c FROM pensiun ps JOIN pegawai p ON ps.pegawai_id=p.pegawai_id $whereSql";
      $countRes = mysqli_query($koneksi, $countSql);
      $total = 0; if ($countRes) { $cr = mysqli_fetch_assoc($countRes); $total = intval($cr['c'] ?? 0); }
      ?>

      <div class="card">
        <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
          <div>
            <a href="admin/pensiun/tambah.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</a>
            <?php
            $cetakUrl = '/sdm_dispersip/admin/pensiun/cetak.php?'
              . 'f_jenis=' . urlencode($f_jenis)
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
                  <th><a class="link-reset" href="admin/pensiun/pensiun.php?sort=nama_lengkap&order=<?php echo ($sort==='nama_lengkap' && $order==='ASC')?'desc':'asc'; ?>">Nama Pegawai<?php echo $sort==='nama_lengkap' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/pensiun/pensiun.php?sort=jenis&order=<?php echo ($sort==='jenis' && $order==='ASC')?'desc':'asc'; ?>">Jenis<?php echo $sort==='jenis' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/pensiun/pensiun.php?sort=nomor_sk&order=<?php echo ($sort==='nomor_sk' && $order==='ASC')?'desc':'asc'; ?>">No SK<?php echo $sort==='nomor_sk' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/pensiun/pensiun.php?sort=tanggal_sk&order=<?php echo ($sort==='tanggal_sk' && $order==='ASC')?'desc':'asc'; ?>">Tanggal SK<?php echo $sort==='tanggal_sk' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/pensiun/pensiun.php?sort=tmt&order=<?php echo ($sort==='tmt' && $order==='ASC')?'desc':'asc'; ?>">TMT<?php echo $sort==='tmt' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sql = "SELECT ps.*, p.nama_lengkap FROM pensiun ps JOIN pegawai p ON ps.pegawai_id=p.pegawai_id $whereSql ORDER BY $sortCol $order LIMIT $perPage OFFSET $offset";
                $res = mysqli_query($koneksi, $sql);
                if ($res && mysqli_num_rows($res) > 0) {
                  $no = $offset + 1;
                  while ($row = mysqli_fetch_assoc($res)) {
                    echo '<tr>'
                      . '<td>'.($no++).'</td>'
                      . '<td>'.htmlspecialchars($row['nama_lengkap']).'</td>'
                      . '<td>'.htmlspecialchars($row['jenis']).'</td>'
                      . '<td>'.htmlspecialchars($row['nomor_sk'] ?? '-').'</td>'
                      . '<td>'.($row['tanggal_sk'] ? date('d/m/Y', strtotime($row['tanggal_sk'])) : '-').'</td>'
                      . '<td>'.($row['tmt'] ? date('d/m/Y', strtotime($row['tmt'])) : '-').'</td>'
                      . '<td>'
                        . '<a href="admin/pensiun/detail.php?id=' . htmlspecialchars($row['pensiun_id']) . '" class="btn btn-sm btn-info me-1"><i class="ti ti-eye me-1"></i>Detail</a>'
                        . '<a href="admin/pensiun/edit.php?id=' . htmlspecialchars($row['pensiun_id']) . '" class="btn btn-sm btn-warning me-1"><i class="ti ti-pencil me-1"></i>Edit</a>'
                        . '<a href="admin/pensiun/hapus.php?id=' . htmlspecialchars($row['pensiun_id']) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Hapus data ini?\')"><i class="ti ti-trash me-1"></i>Hapus</a>'
                      . '</td>'
                      . '</tr>';
                  }
                } else {
                  echo '<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
          <?php
          $totalPages = (int)ceil($total / $perPage);
          if ($totalPages > 1):
            $baseQS = 'admin/pensiun/pensiun.php?sort=' . urlencode($sort) . '&order=' . strtolower($order)
              . '&f_jenis=' . urlencode($f_jenis)
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
