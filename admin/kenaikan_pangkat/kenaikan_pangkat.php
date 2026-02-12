<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$page_title = "Kenaikan Pangkat";
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
        'pangkat_lama' => 'mp1.nama_pangkat',
        'pangkat_baru' => 'mp2.nama_pangkat',
        'nomor_sk' => 'kp.nomor_sk',
        'tanggal_sk' => 'kp.tanggal_sk',
        'tmt' => 'kp.tmt',
        'created_at' => 'kp.created_at'
      ];
      $sort = $_GET['sort'] ?? 'created_at';
      $order = strtolower($_GET['order'] ?? 'desc');
      $order = $order === 'asc' ? 'ASC' : 'DESC';
      $sortCol = $allowedSort[$sort] ?? $allowedSort['created_at'];
      $page = max(1, intval($_GET['page'] ?? 1));
      $perPage = 10;
      $offset = ($page - 1) * $perPage;

      $f_plama = intval($_GET['f_plama'] ?? 0);
      $f_pbaru = intval($_GET['f_pbaru'] ?? 0);
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
      $optPangkat = mysqli_query($koneksi, "SELECT pangkat_id, nama_pangkat FROM master_pangkat ORDER BY nama_pangkat");
      ?>
      <div class="row">
        <div class="col-12">
          <div class="page-title-box">
            <h4 class="page-title">Kenaikan Pangkat</h4>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header border-bottom border-dashed d-flex align-items-center">
          <h5 class="card-title mb-0">Filter Data</h5>
        </div>
        <div class="card-body">
          <form method="get" action="admin/kenaikan_pangkat/kenaikan_pangkat.php" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Pangkat Lama</label>
              <select name="f_plama" class="form-select">
                <option value="0">- Semua -</option>
                <?php if ($optPangkat) { while ($p = mysqli_fetch_assoc($optPangkat)) { ?>
                  <option value="<?php echo htmlspecialchars($p['pangkat_id']); ?>" <?php echo ($f_plama==intval($p['pangkat_id']))?'selected':''; ?>><?php echo htmlspecialchars($p['nama_pangkat']); ?></option>
                <?php } } ?>
              </select>
            </div>
            <?php $optPangkat2 = mysqli_query($koneksi, "SELECT pangkat_id, nama_pangkat FROM master_pangkat ORDER BY nama_pangkat"); ?>
            <div class="col-md-3">
              <label class="form-label">Pangkat Baru</label>
              <select name="f_pbaru" class="form-select">
                <option value="0">- Semua -</option>
                <?php if ($optPangkat2) { while ($p2 = mysqli_fetch_assoc($optPangkat2)) { ?>
                  <option value="<?php echo htmlspecialchars($p2['pangkat_id']); ?>" <?php echo ($f_pbaru==intval($p2['pangkat_id']))?'selected':''; ?>><?php echo htmlspecialchars($p2['nama_pangkat']); ?></option>
                <?php } } ?>
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
              <label class="form-label">TMT (dari)</label>
              <input type="date" name="f_from_tmt" value="<?php echo htmlspecialchars($f_from_tmt); ?>" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">TMT (sampai)</label>
              <input type="date" name="f_to_tmt" value="<?php echo htmlspecialchars($f_to_tmt); ?>" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Cari Nama/No SK</label>
              <input type="text" name="f_q" value="<?php echo htmlspecialchars($f_q); ?>" class="form-control" placeholder="Masukkan nama atau nomor SK">
            </div>
            <div class="col-md-6 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button>
              <a href="admin/kenaikan_pangkat/kenaikan_pangkat.php" class="btn btn-light">Reset</a>
            </div>
          </form>
        </div>
      </div>

      <?php
      $where = [];
      if ($f_plama > 0) { $where[] = "kp.pangkat_lama_id = ".$f_plama; }
      if ($f_pbaru > 0) { $where[] = "kp.pangkat_baru_id = ".$f_pbaru; }
      if ($f_from_sk) { $where[] = "kp.tanggal_sk >= '".$f_from_sk."'"; }
      if ($f_to_sk) { $where[] = "kp.tanggal_sk <= '".$f_to_sk."'"; }
      if ($f_from_tmt) { $where[] = "kp.tmt >= '".$f_from_tmt."'"; }
      if ($f_to_tmt) { $where[] = "kp.tmt <= '".$f_to_tmt."'"; }
      if ($f_q) {
        $q = mysqli_real_escape_string($koneksi, $f_q);
        $where[] = "(p.nama_lengkap LIKE '%$q%' OR kp.nomor_sk LIKE '%$q%')";
      }
      $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
      $countSql = "SELECT COUNT(*) AS c FROM kenaikan_pangkat kp JOIN pegawai p ON kp.pegawai_id=p.pegawai_id LEFT JOIN master_pangkat mp1 ON kp.pangkat_lama_id=mp1.pangkat_id LEFT JOIN master_pangkat mp2 ON kp.pangkat_baru_id=mp2.pangkat_id $whereSql";
      $countRes = mysqli_query($koneksi, $countSql);
      $total = 0;
      if ($countRes) { $countRow = mysqli_fetch_assoc($countRes); $total = intval($countRow['c'] ?? 0); }
      ?>

      <div class="card">
        <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
          <div>
            <a href="admin/kenaikan_pangkat/tambah.php" class="btn btn-primary"><i class="ti 
ti-plus me-1"></i>Tambah</a>
            <?php
            $cetakUrl = '/sdm_dispersip/admin/kenaikan_pangkat/cetak.php?'
              . 'f_plama=' . urlencode((string)$f_plama)
              . '&f_pbaru=' . urlencode((string)$f_pbaru)
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
                  <th><a class="link-reset" href="admin/kenaikan_pangkat/kenaikan_pangkat.php?sort=nama_lengkap&order=<?php echo ($sort==='nama_lengkap' && $order==='ASC')?'desc':'asc'; ?>">Nama Pegawai<?php echo $sort==='nama_lengkap' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/kenaikan_pangkat/kenaikan_pangkat.php?sort=pangkat_lama&order=<?php echo ($sort==='pangkat_lama' && $order==='ASC')?'desc':'asc'; ?>">Pangkat Lama<?php echo $sort==='pangkat_lama' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/kenaikan_pangkat/kenaikan_pangkat.php?sort=pangkat_baru&order=<?php echo ($sort==='pangkat_baru' && $order==='ASC')?'desc':'asc'; ?>">Pangkat Baru<?php echo $sort==='pangkat_baru' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/kenaikan_pangkat/kenaikan_pangkat.php?sort=nomor_sk&order=<?php echo ($sort==='nomor_sk' && $order==='ASC')?'desc':'asc'; ?>">No SK<?php echo $sort==='nomor_sk' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/kenaikan_pangkat/kenaikan_pangkat.php?sort=tanggal_sk&order=<?php echo ($sort==='tanggal_sk' && $order==='ASC')?'desc':'asc'; ?>">Tanggal SK<?php echo $sort==='tanggal_sk' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/kenaikan_pangkat/kenaikan_pangkat.php?sort=tmt&order=<?php echo ($sort==='tmt' && $order==='ASC')?'desc':'asc'; ?>">TMT<?php echo $sort==='tmt' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sql = "SELECT kp.*, p.nama_lengkap, mp1.nama_pangkat AS pangkat_lama, mp2.nama_pangkat AS pangkat_baru
                        FROM kenaikan_pangkat kp
                        JOIN pegawai p ON kp.pegawai_id = p.pegawai_id
                        LEFT JOIN master_pangkat mp1 ON kp.pangkat_lama_id = mp1.pangkat_id
                        LEFT JOIN master_pangkat mp2 ON kp.pangkat_baru_id = mp2.pangkat_id
                        $whereSql
                        ORDER BY $sortCol $order
                        LIMIT $perPage OFFSET $offset";
                $res = mysqli_query($koneksi, $sql);
                if ($res && mysqli_num_rows($res) > 0) {
                  $no = $offset + 1;
                  while ($row = mysqli_fetch_assoc($res)) {
                    echo '<tr>'
                      . '<td>'.($no++).'</td>'
                      . '<td>'.htmlspecialchars($row['nama_lengkap']).'</td>'
                      . '<td>'.htmlspecialchars($row['pangkat_lama'] ?? '-').'</td>'
                      . '<td>'.htmlspecialchars($row['pangkat_baru']).'</td>'
                      . '<td>'.htmlspecialchars($row['nomor_sk'] ?? '-').'</td>'
                      . '<td>'.($row['tanggal_sk'] ? date('d/m/Y', strtotime($row['tanggal_sk'])) : '-').'</td>'
                      . '<td>'.date('d/m/Y', strtotime($row['tmt'])).'</td>'
                      . '<td>'
                        . '<a href="admin/kenaikan_pangkat/detail.php?id=' . htmlspecialchars($row['kp_id']) . '" class="btn btn-sm btn-info me-1"><i class="ti ti-eye me-1"></i>Detail</a>'
                        . '<a href="admin/kenaikan_pangkat/edit.php?id=' . htmlspecialchars($row['kp_id']) . '" class="btn btn-sm btn-warning me-1"><i class="ti ti-pencil me-1"></i>Edit</a>'
                        . '<a href="admin/kenaikan_pangkat/hapus.php?id=' . htmlspecialchars($row['kp_id']) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Hapus data ini?\')"><i class="ti ti-trash me-1"></i>Hapus</a>'
                      . '</td>'
                      . '</tr>';
                  }
                } else {
                  echo '<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
          <?php
          $totalPages = (int)ceil($total / $perPage);
          if ($totalPages > 1):
            $baseQS = 'admin/kenaikan_pangkat/kenaikan_pangkat.php?sort=' . urlencode($sort) . '&order=' . strtolower($order)
              . '&f_plama=' . urlencode((string)$f_plama)
              . '&f_pbaru=' . urlencode((string)$f_pbaru)
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

