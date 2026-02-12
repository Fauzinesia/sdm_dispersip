<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dok'])) {
  $dok_id = intval($_POST['dok_id'] ?? 0);
  if ($dok_id > 0) {
    $get = mysqli_prepare($koneksi, "SELECT file_path FROM arsip_dokumen WHERE dok_id=?");
    mysqli_stmt_bind_param($get, 'i', $dok_id);
    mysqli_stmt_execute($get);
    $res = mysqli_stmt_get_result($get);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($get);
    if ($row) {
      if (!empty($row['file_path'])) {
        $fs = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $row['file_path']);
        if (is_file($fs)) { @unlink($fs); }
      }
      $del = mysqli_prepare($koneksi, "DELETE FROM arsip_dokumen WHERE dok_id=?");
      mysqli_stmt_bind_param($del, 'i', $dok_id);
      if (mysqli_stmt_execute($del)) { mysqli_stmt_close($del); header("Location: /sdm_dispersip/admin/arsip/arsip.php?msg=".urlencode('Dokumen dihapus')."&type=success"); exit(); }
      mysqli_stmt_close($del);
      header("Location: /sdm_dispersip/admin/arsip/arsip.php?msg=".urlencode('Gagal menghapus dokumen')."&type=danger"); exit();
    }
  }
  header("Location: /sdm_dispersip/admin/arsip/arsip.php?msg=".urlencode('Data tidak ditemukan')."&type=danger"); exit();
}

$page_title = "Arsip Dokumen";
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
        'jenis_dokumen' => 'ad.jenis_dokumen',
        'nama_dokumen' => 'ad.nama_dokumen',
        'uploaded_by' => 'u.username',
        'created_at' => 'ad.created_at'
      ];
      $sort = $_GET['sort'] ?? 'created_at';
      $order = strtolower($_GET['order'] ?? 'desc');
      $order = $order === 'asc' ? 'ASC' : 'DESC';
      $sortCol = $allowedSort[$sort] ?? $allowedSort['created_at'];
      $page = max(1, intval($_GET['page'] ?? 1));
      $perPage = 10;
      $offset = ($page - 1) * $perPage;

      $f_pegawai = intval($_GET['f_pegawai'] ?? 0);
      $f_jenis = trim($_GET['f_jenis'] ?? '');
      $f_from = $_GET['f_from'] ?? '';
      $f_to = $_GET['f_to'] ?? '';
      $f_q = trim($_GET['f_q'] ?? '');
      $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
      if ($f_from && !preg_match($dateRegex, $f_from)) $f_from = '';
      if ($f_to && !preg_match($dateRegex, $f_to)) $f_to = '';
      ?>
      <div class="row">
        <div class="col-12">
          <div class="page-title-box">
            <h4 class="page-title">Arsip Dokumen</h4>
          </div>
        </div>
      </div>

      <?php $pegRes = mysqli_query($koneksi, "SELECT pegawai_id, nama_lengkap FROM pegawai ORDER BY nama_lengkap"); ?>
      <div class="card mb-3">
        <div class="card-header border-bottom border-dashed d-flex align-items-center"><h5 class="card-title mb-0">Filter Data</h5></div>
        <div class="card-body">
          <form method="get" action="arsip.php" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Pegawai</label>
              <select name="f_pegawai" class="form-select">
                <option value="0">- Semua -</option>
                <?php if ($pegRes) { while ($p = mysqli_fetch_assoc($pegRes)) { echo '<option value="'.htmlspecialchars($p['pegawai_id']).'" '.($f_pegawai==intval($p['pegawai_id'])?'selected':'').'>'.htmlspecialchars($p['nama_lengkap']).'</option>'; } } ?>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Jenis Dokumen</label><input type="text" name="f_jenis" value="<?php echo htmlspecialchars($f_jenis); ?>" class="form-control" placeholder="Contoh: SK, Sertifikat"></div>
            <div class="col-md-3"><label class="form-label">Tanggal (dari)</label><input type="date" name="f_from" value="<?php echo htmlspecialchars($f_from); ?>" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Tanggal (sampai)</label><input type="date" name="f_to" value="<?php echo htmlspecialchars($f_to); ?>" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Cari Nama/Upload By</label><input type="text" name="f_q" value="<?php echo htmlspecialchars($f_q); ?>" class="form-control" placeholder="Masukkan nama dokumen/pegawai/uploader"></div>
            <div class="col-md-6 d-flex align-items-end gap-2"><button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Terapkan Filter</button><a href="arsip.php" class="btn btn-light">Reset</a></div>
          </form>
        </div>
      </div>

      <?php
      $where = [];
      if ($f_pegawai > 0) { $where[] = "ad.pegawai_id = ".$f_pegawai; }
      if ($f_jenis) { $where[] = "ad.jenis_dokumen LIKE '%".mysqli_real_escape_string($koneksi, $f_jenis)."%'"; }
      if ($f_from) { $where[] = "ad.created_at >= '".$f_from."'"; }
      if ($f_to) { $where[] = "ad.created_at <= '".$f_to."'"; }
      if ($f_q) { $q = mysqli_real_escape_string($koneksi, $f_q); $where[] = "(ad.nama_dokumen LIKE '%$q%' OR p.nama_lengkap LIKE '%$q%' OR u.username LIKE '%$q%')"; }
      $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
      $countSql = "SELECT COUNT(*) AS c FROM arsip_dokumen ad JOIN pegawai p ON ad.pegawai_id=p.pegawai_id LEFT JOIN users u ON ad.uploaded_by=u.user_id $whereSql";
      $countRes = mysqli_query($koneksi, $countSql);
      $total = 0; if ($countRes) { $cr = mysqli_fetch_assoc($countRes); $total = intval($cr['c'] ?? 0); }
      ?>

      <div class="card">
        <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
          <div>
            <a href="admin/arsip/tambah.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</a>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive-sm">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>No</th>
                  <th><a class="link-reset" href="arsip.php?sort=nama_lengkap&order=<?php echo ($sort==='nama_lengkap' && $order==='ASC')?'desc':'asc'; ?>">Pegawai<?php echo $sort==='nama_lengkap' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="arsip.php?sort=jenis_dokumen&order=<?php echo ($sort==='jenis_dokumen' && $order==='ASC')?'desc':'asc'; ?>">Jenis<?php echo $sort==='jenis_dokumen' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="arsip.php?sort=nama_dokumen&order=<?php echo ($sort==='nama_dokumen' && $order==='ASC')?'desc':'asc'; ?>">Nama Dokumen<?php echo $sort==='nama_dokumen' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th>File</th>
                  <th><a class="link-reset" href="arsip.php?sort=uploaded_by&order=<?php echo ($sort==='uploaded_by' && $order==='ASC')?'desc':'asc'; ?>">Uploader<?php echo $sort==='uploaded_by' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th><a class="link-reset" href="arsip.php?sort=created_at&order=<?php echo ($sort==='created_at' && $order==='ASC')?'desc':'asc'; ?>">Diupload<?php echo $sort==='created_at' ? ($order==='ASC'?' ▲':' ▼') : ''; ?></a></th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sql = "SELECT ad.*, p.nama_lengkap, u.username AS uploader
                        FROM arsip_dokumen ad
                        JOIN pegawai p ON ad.pegawai_id=p.pegawai_id
                        LEFT JOIN users u ON ad.uploaded_by=u.user_id
                        $whereSql
                        ORDER BY $sortCol $order
                        LIMIT $perPage OFFSET $offset";
                $res = mysqli_query($koneksi, $sql);
                if ($res && mysqli_num_rows($res) > 0) {
                  $no = $offset + 1;
                  while ($row = mysqli_fetch_assoc($res)) {
                    $fileUrl = !empty($row['file_path']) ? ('/sdm_dispersip/'.htmlspecialchars($row['file_path'])) : '';
                    echo '<tr>'
                      . '<td>'.($no++).'</td>'
                      . '<td>'.htmlspecialchars($row['nama_lengkap']).'</td>'
                      . '<td>'.htmlspecialchars($row['jenis_dokumen'] ?? '-').'</td>'
                      . '<td>'.htmlspecialchars($row['nama_dokumen']).'</td>'
                      . '<td>' . ($fileUrl ? ('<a href="'.$fileUrl.'" target="_blank" class="btn btn-sm btn-info"><i class="ti ti-file-text me-1"></i>Lihat</a>') : '-') . '</td>'
                      . '<td>'.htmlspecialchars($row['uploader'] ?? '-').'</td>'
                      . '<td>'.($row['created_at'] ? date('d/m/Y', strtotime($row['created_at'])) : '-').'</td>'
                      . '<td>'
                        . '<form method="post" class="d-inline" onsubmit="return confirm(\'Hapus dokumen ini?\')">'
                        . '<input type="hidden" name="dok_id" value="'.htmlspecialchars($row['dok_id']).'">'
                        . '<button type="submit" name="delete_dok" class="btn btn-sm btn-danger"><i class="ti ti-trash me-1"></i>Hapus</button>'
                        . '</form>'
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
            $baseQS = 'arsip.php?sort=' . urlencode($sort) . '&order=' . strtolower($order)
              . '&f_pegawai=' . urlencode((string)$f_pegawai)
              . '&f_jenis=' . urlencode($f_jenis)
              . '&f_from=' . urlencode($f_from)
              . '&f_to=' . urlencode($f_to)
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
