<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'verifikator') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$f_year = preg_replace('/[^0-9]/', '', $_GET['f_year'] ?? '');
$f_month = preg_replace('/[^0-9]/', '', $_GET['f_month'] ?? '');
$f_penilai = intval($_GET['f_penilai'] ?? 0);
$f_min = trim($_GET['f_min'] ?? '');
$f_max = trim($_GET['f_max'] ?? '');
$f_q = trim($_GET['f_q'] ?? '');

$where = [];
if ($f_year && strlen($f_year)===4) { $where[] = "LEFT(pk.periode,4) = '".mysqli_real_escape_string($koneksi,$f_year)."'"; }
if ($f_month && strlen($f_month)===2) { $where[] = "RIGHT(pk.periode,2) = '".mysqli_real_escape_string($koneksi,$f_month)."'"; }
if ($f_penilai > 0) { $where[] = "pk.penilai_user_id = ".$f_penilai; }
if ($f_min !== '' && is_numeric($f_min)) { $where[] = "pk.skor_akhir >= ".floatval($f_min); }
if ($f_max !== '' && is_numeric($f_max)) { $where[] = "pk.skor_akhir <= ".floatval($f_max); }
if ($f_q) { $q = mysqli_real_escape_string($koneksi, $f_q); $where[] = "(p.nama_lengkap LIKE '%$q%' OR pk.komentar LIKE '%$q%')"; }
$whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';

$sql = "SELECT pk.*, p.nama_lengkap, u.username AS penilai_username FROM penilaian_kinerja pk JOIN pegawai p ON pk.pegawai_id=p.pegawai_id LEFT JOIN users u ON pk.penilai_user_id=u.user_id $whereSql ORDER BY pk.created_at DESC";
$result = mysqli_query($koneksi, $sql);
$total = $result ? mysqli_num_rows($result) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak Penilaian Kinerja - Dinas Perpustakaan dan Kearsipan Kota Banjarmasin</title>
  <style>
    @media print { .no-print { display: none; } @page { margin: 1cm; } }
    body { font-family: 'Times New Roman', Times, serif; margin:0; padding:20px; font-size:12pt; }
    .kop-surat { text-align:center; border-bottom:3px solid #000; padding-bottom:10px; margin-bottom:20px; }
    .kop-surat img { height:80px; float:left; margin-right:20px; }
    .kop-surat .header-text { text-align:center; line-height:1.3; }
    .kop-surat h3 { margin:0; font-size:16pt; font-weight:bold; text-transform:uppercase; }
    .kop-surat h2 { margin:0; font-size:18pt; font-weight:bold; text-transform:uppercase; }
    .kop-surat p { margin:2px 0; font-size:10pt; }
    .clearfix::after { content:""; display:table; clear:both; }
    .judul { text-align:center; margin:30px 0 20px 0; }
    .judul h1 { margin:0; font-size:14pt; text-decoration:underline; font-weight:bold; }
    .info { margin-bottom:20px; font-size:11pt; }
    table { width:100%; border-collapse:collapse; margin-bottom:20px; }
    table, th, td { border:1px solid #000; }
    th { background-color:#f0f0f0; padding:8px; text-align:center; font-weight:bold; font-size:10pt; }
    td { padding:6px; font-size:10pt; }
    .text-center { text-align:center; }
    .ttd { margin-top:40px; float:right; width:300px; text-align:center; }
    .ttd p { margin:5px 0; }
    .ttd .nama { margin-top:60px; font-weight:bold; text-decoration:underline; }
    .btn-print { background-color:#007bff; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; font-size:14px; margin-bottom:20px; }
    .btn-print:hover { background-color:#0056b3; }
  </style>
</head>
<body>
  <div class="no-print">
    <button onclick="window.print()" class="btn-print">🖨️ Cetak Dokumen</button>
    <button onclick="window.close()" class="btn-print" style="background-color:#6c757d;">❌ Tutup</button>
  </div>

  <div class="kop-surat clearfix">
    <img src="../../assets/images/logo_bjm.png" alt="Logo Banjarmasin">
    <div class="header-text">
      <h3>PEMERINTAH KOTA BANJARMASIN</h3>
      <h2>DINAS PERPUSTAKAAN DAN KEARSIPAN</h2>
      <p>Jalan K.S Tubun RT.05 RW.01 Banjarmasin Kode Pos 70241</p>
      <p>Telepon (0511) 3362523 Faks. (0511) 3362523</p>
      <p>Laman: dispersip.banjarmasinkota.go.id Pos-el: arsipusdok@gmail.com</p>
    </div>
  </div>

  <div class="judul"><h1>DAFTAR PENILAIAN KINERJA</h1></div>

  <div class="info">
    <p><strong>Tanggal Cetak:</strong> <?php echo date('d F Y'); ?></p>
    <p><strong>Total Data:</strong> <?php echo $total; ?> Penilaian</p>
    <?php if ($f_year): ?><p><strong>Tahun:</strong> <?php echo htmlspecialchars($f_year); ?></p><?php endif; ?>
    <?php if ($f_month): ?><p><strong>Bulan:</strong> <?php echo htmlspecialchars($f_month); ?></p><?php endif; ?>
    <?php if ($f_penilai>0): ?><p><strong>Penilai:</strong> <?php echo htmlspecialchars($f_penilai); ?></p><?php endif; ?>
    <?php if ($f_min!=='' || $f_max!==''): ?><p><strong>Rentang Skor:</strong> <?php echo htmlspecialchars($f_min ?: '0'); ?> s/d <?php echo htmlspecialchars($f_max ?: '100'); ?></p><?php endif; ?>
    <?php if ($f_q): ?><p><strong>Pencarian:</strong> <?php echo htmlspecialchars($f_q); ?></p><?php endif; ?>
  </div>

  <table>
    <thead>
      <tr>
        <th width="5%">No</th>
        <th width="20%">Nama Pegawai</th>
        <th width="10%">Periode</th>
        <th width="10%">Kuantitas</th>
        <th width="10%">Kualitas</th>
        <th width="10%">Perilaku</th>
        <th width="10%">Skor Akhir</th>
        <th width="10%">Predikat</th>
        <th width="15%">Penilai</th>
        <th width="10%">Dibuat</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($total > 0) { $no = 1; mysqli_data_seek($result, 0); while ($r = mysqli_fetch_assoc($result)) { echo '<tr>'
        . '<td class="text-center">'.$no++.'</td>'
        . '<td>'.htmlspecialchars($r['nama_lengkap']).'</td>'
        . '<td class="text-center">'.htmlspecialchars($r['periode']).'</td>'
        . '<td class="text-center">'.htmlspecialchars(number_format((float)$r['nilai_kuantitas'],2)).'</td>'
        . '<td class="text-center">'.htmlspecialchars(number_format((float)$r['nilai_kualitas'],2)).'</td>'
        . '<td class="text-center">'.htmlspecialchars(number_format((float)$r['nilai_perilaku'],2)).'</td>'
        . '<td class="text-center">'.htmlspecialchars(number_format((float)$r['skor_akhir'],2)).'</td>'
. '<td class="text-center">'.htmlspecialchars(getPredikatKinerja((float)$r['skor_akhir'])).'</td>'
. '<td class="text-center">'.htmlspecialchars($r['penilai_username'] ?? '-').'</td>'
        . '<td class="text-center">'.($r['created_at'] ? date('d/m/Y', strtotime($r['created_at'])) : '-').'</td>'
        . '</tr>'; } } else { echo '<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>'; } ?>
    </tbody>
  </table>

  <div class="ttd">
    <p>Banjarmasin, <?php echo date('d F Y'); ?></p>
    <p><strong>Kepala Dinas,</strong></p>
    <p class="nama">Drs. Muhammad Ikhsan Alhak, M.Si</p>
    <p>Pembina Utama Muda (IV/c)</p>
    <p>NIP. 19660916 198602 1 002</p>
  </div>

</body>
</html>

