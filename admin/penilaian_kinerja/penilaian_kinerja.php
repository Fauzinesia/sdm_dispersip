<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';
$page_title = "Penilaian Kinerja";
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
        'periode' => 'pk.periode',
        'nilai_kuantitas' => 'pk.nilai_kuantitas',
        'nilai_kualitas' => 'pk.nilai_kualitas',
        'nilai_perilaku' => 'pk.nilai_perilaku',
        'skor_akhir' => 'pk.skor_akhir',
        'created_at' => 'pk.created_at'
      ];
      $sort = $_GET['sort'] ?? 'created_at';
      $order = strtolower($_GET['order'] ?? 'desc');
      $order = $order === 'asc' ? 'ASC' : 'DESC';
      $sortCol = $allowedSort[$sort] ?? $allowedSort['created_at'];
      $page = max(1, intval($_GET['page'] ?? 1));
      $perPage = 10;
      $offset = ($page - 1) * $perPage;

      $f_year = preg_replace('/[^0-9]/', '', $_GET['f_year'] ?? '');
      $f_month = preg_replace('/[^0-9]/', '', $_GET['f_month'] ?? '');
      $f_penilai = intval($_GET['f_penilai'] ?? 0);
      $f_min = trim($_GET['f_min'] ?? '');
      $f_max = trim($_GET['f_max'] ?? '');
      $f_q = trim($_GET['f_q'] ?? '');
      ?>
      <div class="row">
        <div class="col-12">
          <div class="page-title-box">
            <h4 class="page-title">Penilaian Kinerja</h4>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header border-bottom border-dashed d-flex align-items-center"><h5 class="card-title mb-0">Filter Data</h5></div>
        <div class="card-body">
          <form method="get" action="admin/penilaian_kinerja/penilaian_kinerja.php" class="row g-3">
            <div class="col-md-2"><label class="form-label">Tahun</label><input type="text" name="f_year" value="<?php echo htmlspecialchars($f_year); ?>" class="form-control" placeholder="YYYY"></div>
            <div class="col-md-2"><label class="form-label">Bulan</label><input type="text" name="f_month" value="<?php echo htmlspecialchars($f_month); ?>" class="form-control" placeholder="MM"></div>
            <?php $penilaiRes = mysqli_query($koneksi, "SELECT user_id, username FROM users ORDER BY username"); ?>
            <div class="col-md-3"><label class="form-label">Penilai</label><select name="f_penilai" class="form-select"><option value="0">- Semua -</option><?php if ($penilaiRes){ while($u=mysqli_fetch_assoc($penilaiRes)){ echo '<option value="'.htmlspecialchars($u['user_id']).'" '.($f_penilai==intval($u['user_id'])?'selected':'').'>'.htmlspecialchars($u['username']).'</option>'; } } ?></select></div>
            <div class="col-md-2"><label class="form-label">Skor Min</label><input type="number" step="0.01" name="f_min" value="<?php echo htmlspecialchars($f_min); ?>" class="form-control" placeholder="0"></div>
            <div class="col-md-2"><label class="form-label">Skor Max</label><input type="number" step="0.01" name="f_max" value="<?php echo htmlspecialchars($f_max); ?>" class="form-control" placeholder="100"></div>
            <div class="col-md-6"><label class="form-label">Cari Nama/Komentar</label><input type="text" name="f_q" value="<?php echo htmlspecialchars($f_q); ?>" class="form-control" placeholder="Masukkan nama atau komentar"></div>
            <div class="col-md-6 d-flex align-items-end gap-2"><button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button><a href="admin/penilaian_kinerja/penilaian_kinerja.php" class="btn btn-light">Reset</a></div>
          </form>
        </div>
      </div>

      <?php
      $where = [];
      if ($f_year && strlen($f_year)===4) { $where[] = "LEFT(pk.periode,4) = '".mysqli_real_escape_string($koneksi,$f_year)."'"; }
      if ($f_month && strlen($f_month)===2) { $where[] = "RIGHT(pk.periode,2) = '".mysqli_real_escape_string($koneksi,$f_month)."'"; }
      if ($f_penilai > 0) { $where[] = "pk.penilai_user_id = ".$f_penilai; }
      if ($f_min !== '' && is_numeric($f_min)) { $where[] = "pk.skor_akhir >= ".floatval($f_min); }
      if ($f_max !== '' && is_numeric($f_max)) { $where[] = "pk.skor_akhir <= ".floatval($f_max); }
      if ($f_q) { $q = mysqli_real_escape_string($koneksi, $f_q); $where[] = "(p.nama_lengkap LIKE '%$q%' OR pk.komentar LIKE '%$q%')"; }
      $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
      $countSql = "SELECT COUNT(*) AS c FROM penilaian_kinerja pk JOIN pegawai p ON pk.pegawai_id=p.pegawai_id LEFT JOIN users u ON pk.penilai_user_id=u.user_id $whereSql";
      $countRes = mysqli_query($koneksi, $countSql);
      $total = 0; if ($countRes) { $cr = mysqli_fetch_assoc($countRes); $total = intval($cr['c'] ?? 0); }
      ?>

      <div class="card">
        <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
          <div>
            <?php
            $cetakUrl = '/sdm_dispersip/admin/penilaian_kinerja/cetak.php?'
              . 'f_year=' . urlencode($f_year)
              . '&f_month=' . urlencode($f_month)
              . '&f_penilai=' . urlencode((string)$f_penilai)
              . '&f_min=' . urlencode($f_min)
              . '&f_max=' . urlencode($f_max)
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
                  <th><a class="link-reset" href="admin/penilaian_kinerja/penilaian_kinerja.php?sort=nama_lengkap&order=<?php echo ($sort==='nama_lengkap' && $order==='ASC')?'desc':'asc'; ?>">Nama Pegawai<?php echo $sort==='nama_lengkap' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/penilaian_kinerja/penilaian_kinerja.php?sort=periode&order=<?php echo ($sort==='periode' && $order==='ASC')?'desc':'asc'; ?>">Periode<?php echo $sort==='periode' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/penilaian_kinerja/penilaian_kinerja.php?sort=nilai_kuantitas&order=<?php echo ($sort==='nilai_kuantitas' && $order==='ASC')?'desc':'asc'; ?>">Kuantitas<?php echo $sort==='nilai_kuantitas' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/penilaian_kinerja/penilaian_kinerja.php?sort=nilai_kualitas&order=<?php echo ($sort==='nilai_kualitas' && $order==='ASC')?'desc':'asc'; ?>">Kualitas<?php echo $sort==='nilai_kualitas' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/penilaian_kinerja/penilaian_kinerja.php?sort=nilai_perilaku&order=<?php echo ($sort==='nilai_perilaku' && $order==='ASC')?'desc':'asc'; ?>">Perilaku<?php echo $sort==='nilai_perilaku' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="admin/penilaian_kinerja/penilaian_kinerja.php?sort=skor_akhir&order=<?php echo ($sort==='skor_akhir' && $order==='ASC')?'desc':'asc'; ?>">Skor Akhir<?php echo $sort==='skor_akhir' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th>Predikat</th>
                  <th>Penilai</th>
                  <th><a class="link-reset" href="admin/penilaian_kinerja/penilaian_kinerja.php?sort=created_at&order=<?php echo ($sort==='created_at' && $order==='ASC')?'desc':'asc'; ?>">Dibuat<?php echo $sort==='created_at' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sql = "SELECT pk.*, p.nama_lengkap, u.username AS penilai_username
                        FROM penilaian_kinerja pk
                        JOIN pegawai p ON pk.pegawai_id=p.pegawai_id
                        LEFT JOIN users u ON pk.penilai_user_id=u.user_id
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
                      . '<td>'.htmlspecialchars($row['periode']).'</td>'
                      . '<td>'.htmlspecialchars(number_format((float)$row['nilai_kuantitas'],2)).'</td>'
                      . '<td>'.htmlspecialchars(number_format((float)$row['nilai_kualitas'],2)).'</td>'
                      . '<td>'.htmlspecialchars(number_format((float)$row['nilai_perilaku'],2)).'</td>'
                      . '<td>'.htmlspecialchars(number_format((float)$row['skor_akhir'],2)).'</td>'
. '<td><span class="badge bg-info">'.htmlspecialchars(getPredikatKinerja((float)$row['skor_akhir'])).'</span></td>'
. '<td>'.htmlspecialchars($row['penilai_username'] ?? '-').'</td>'
                      . '<td>'.($row['created_at'] ? date('d/m/Y', strtotime($row['created_at'])) : '-').'</td>'
                      . '<td>'
                        . '<a href="admin/penilaian_kinerja/detail.php?id=' . htmlspecialchars($row['penilaian_id']) . '" class="btn btn-sm btn-info me-1"><i class="ti ti-eye me-1"></i>Detail</a>'
                        . '<a href="admin/penilaian_kinerja/edit.php?id=' . htmlspecialchars($row['penilaian_id']) . '" class="btn btn-sm btn-warning me-1"><i class="ti ti-pencil me-1"></i>Edit</a>'
                        . '<a href="admin/penilaian_kinerja/hapus.php?id=' . htmlspecialchars($row['penilaian_id']) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Hapus data ini?\')"><i class="ti ti-trash me-1"></i>Hapus</a>'
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
            $baseQS = 'admin/penilaian_kinerja/penilaian_kinerja.php?sort=' . urlencode($sort) . '&order=' . strtolower($order)
              . '&f_year=' . urlencode($f_year)
              . '&f_month=' . urlencode($f_month)
              . '&f_penilai=' . urlencode((string)$f_penilai)
              . '&f_min=' . urlencode($f_min)
              . '&f_max=' . urlencode($f_max)
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

