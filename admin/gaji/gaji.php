<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$page_title = "Data Gaji";
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
        'periode' => 'g.periode',
        'gaji_pokok' => 'g.gaji_pokok',
        'tunjangan' => 'g.tunjangan',
        'potongan' => 'g.potongan',
        'total_gaji' => 'g.total_gaji',
        'created_at' => 'g.created_at'
      ];
      $sort = $_GET['sort'] ?? 'created_at';
      $order = strtolower($_GET['order'] ?? 'desc');
      $order = $order === 'asc' ? 'ASC' : 'DESC';
      $sortCol = $allowedSort[$sort] ?? $allowedSort['created_at'];
      $page = max(1, intval($_GET['page'] ?? 1));
      $perPage = 10;
      $offset = ($page - 1) * $perPage;

      $f_pegawai = intval($_GET['f_pegawai'] ?? 0);
      $f_year = preg_replace('/[^0-9]/', '', $_GET['f_year'] ?? '');
      $f_month = preg_replace('/[^0-9]/', '', $_GET['f_month'] ?? '');
      $f_q = trim($_GET['f_q'] ?? '');
      ?>
      <div class="row">
        <div class="col-12">
          <div class="page-title-box">
            <h4 class="page-title">Data Gaji</h4>
          </div>
        </div>
      </div>

      <?php $pegRes = mysqli_query($koneksi, "SELECT pegawai_id, nama_lengkap FROM pegawai ORDER BY nama_lengkap"); ?>
      <div class="card mb-3">
        <div class="card-header border-bottom border-dashed d-flex align-items-center"><h5 class="card-title mb-0">Filter Data</h5></div>
        <div class="card-body">
          <form method="get" action="admin/gaji/gaji.php" class="row g-3">
            <div class="col-md-3"><label class="form-label">Pegawai</label><select name="f_pegawai" class="form-select"><option value="0">- Semua -</option><?php if ($pegRes){ while($p=mysqli_fetch_assoc($pegRes)){ echo '<option value="'.htmlspecialchars($p['pegawai_id']).'" '.($f_pegawai==intval($p['pegawai_id'])?'selected':'').'>'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?></select></div>
            <div class="col-md-2"><label class="form-label">Tahun</label><input type="text" name="f_year" value="<?php echo htmlspecialchars($f_year); ?>" class="form-control" placeholder="YYYY"></div>
            <div class="col-md-2"><label class="form-label">Bulan</label><input type="text" name="f_month" value="<?php echo htmlspecialchars($f_month); ?>" class="form-control" placeholder="MM"></div>
            <div class="col-md-5"><label class="form-label">Cari Nama/Keterangan</label><input type="text" name="f_q" value="<?php echo htmlspecialchars($f_q); ?>" class="form-control" placeholder="Masukkan nama atau keterangan"></div>
            <div class="col-md-6 d-flex align-items-end gap-2"><button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button><a href="admin/gaji/gaji.php" class="btn btn-light">Reset</a></div>
          </form>
        </div>
      </div>

      <?php
      $where = [];
      if ($f_pegawai > 0) { $where[] = "g.pegawai_id = ".$f_pegawai; }
      if ($f_year && strlen($f_year)===4) { $where[] = "LEFT(g.periode,4) = '".mysqli_real_escape_string($koneksi,$f_year)."'"; }
      if ($f_month && strlen($f_month)===2) { $where[] = "RIGHT(g.periode,2) = '".mysqli_real_escape_string($koneksi,$f_month)."'"; }
      if ($f_q) { $q = mysqli_real_escape_string($koneksi, $f_q); $where[] = "(p.nama_lengkap LIKE '%$q%' OR g.keterangan LIKE '%$q%')"; }
      $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
      $countSql = "SELECT COUNT(*) AS c FROM gaji g JOIN pegawai p ON g.pegawai_id=p.pegawai_id $whereSql";
      $countRes = mysqli_query($koneksi, $countSql);
      $total = 0; if ($countRes) { $cr = mysqli_fetch_assoc($countRes); $total = intval($cr['c'] ?? 0); }
      ?>

      <div class="card">
        <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
          <div>
            <a href="admin/gaji/tambah.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</a>
            <?php
            $cetakUrl = '/sdm_dispersip/admin/gaji/cetak.php?'
              . 'f_pegawai=' . urlencode((string)$f_pegawai)
              . '&f_year=' . urlencode($f_year)
              . '&f_month=' . urlencode($f_month)
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
                  <th><a class="link-reset" href="admin/gaji/gaji.php?sort=nama_lengkap&order=<?php echo ($sort==='nama_lengkap' && $order==='ASC')?'desc':'asc'; ?>">Nama Pegawai<?php echo $sort==='nama_lengkap' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/gaji/gaji.php?sort=periode&order=<?php echo ($sort==='periode' && $order==='ASC')?'desc':'asc'; ?>">Periode<?php echo $sort==='periode' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/gaji/gaji.php?sort=gaji_pokok&order=<?php echo ($sort==='gaji_pokok' && $order==='ASC')?'desc':'asc'; ?>">Gaji Pokok<?php echo $sort==='gaji_pokok' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/gaji/gaji.php?sort=tunjangan&order=<?php echo ($sort==='tunjangan' && $order==='ASC')?'desc':'asc'; ?>">Tunjangan<?php echo $sort==='tunjangan' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/gaji/gaji.php?sort=potongan&order=<?php echo ($sort==='potongan' && $order==='ASC')?'desc':'asc'; ?>">Potongan<?php echo $sort==='potongan' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/gaji/gaji.php?sort=total_gaji&order=<?php echo ($sort==='total_gaji' && $order==='ASC')?'desc':'asc'; ?>">Total<?php echo $sort==='total_gaji' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sql = "SELECT g.*, p.nama_lengkap FROM gaji g JOIN pegawai p ON g.pegawai_id=p.pegawai_id $whereSql ORDER BY $sortCol $order LIMIT $perPage OFFSET $offset";
                $res = mysqli_query($koneksi, $sql);
                if ($res && mysqli_num_rows($res) > 0) {
                  $no = $offset + 1;
                  while ($row = mysqli_fetch_assoc($res)) {
                    echo '<tr>'
                      . '<td>'.($no++).'</td>'
                      . '<td>'.htmlspecialchars($row['nama_lengkap']).'</td>'
                      . '<td>'.htmlspecialchars($row['periode']).'</td>'
                      . '<td>'.number_format((float)$row['gaji_pokok'], 2).'</td>'
                      . '<td>'.number_format((float)$row['tunjangan'], 2).'</td>'
                      . '<td>'.number_format((float)$row['potongan'], 2).'</td>'
                      . '<td>'.number_format((float)$row['total_gaji'], 2).'</td>'
                      . '<td><div class="d-flex flex-wrap gap-1">'
                        . '<a href="admin/gaji/detail.php?id=' . htmlspecialchars($row['gaji_id']) . '" class="btn btn-sm btn-info"><i class="ti ti-eye me-1"></i>Detail</a>'
                        . '<a href="admin/gaji/edit.php?id=' . htmlspecialchars($row['gaji_id']) . '" class="btn btn-sm btn-warning"><i class="ti ti-pencil me-1"></i>Edit</a>'
                        . '<a href="admin/gaji/hapus.php?id=' . htmlspecialchars($row['gaji_id']) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Hapus data ini?\')"><i class="ti ti-trash me-1"></i>Hapus</a>'
                      . '</div></td>'
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
            $baseQS = 'admin/gaji/gaji.php?sort=' . urlencode($sort) . '&order=' . strtolower($order)
              . '&f_pegawai=' . urlencode((string)$f_pegawai)
              . '&f_year=' . urlencode($f_year)
              . '&f_month=' . urlencode($f_month)
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
