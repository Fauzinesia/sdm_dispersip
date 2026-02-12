<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

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

$where = [];
if ($f_plama > 0) { $where[] = "kp.pangkat_lama_id = ".$f_plama; }
if ($f_pbaru > 0) { $where[] = "kp.pangkat_baru_id = ".$f_pbaru; }
if ($f_from_sk) { $where[] = "kp.tanggal_sk >= '".mysqli_real_escape_string($koneksi, $f_from_sk)."'"; }
if ($f_to_sk) { $where[] = "kp.tanggal_sk <= '".mysqli_real_escape_string($koneksi, $f_to_sk)."'"; }
if ($f_from_tmt) { $where[] = "kp.tmt >= '".mysqli_real_escape_string($koneksi, $f_from_tmt)."'"; }
if ($f_to_tmt) { $where[] = "kp.tmt <= '".mysqli_real_escape_string($koneksi, $f_to_tmt)."'"; }
if ($f_q) { $q = mysqli_real_escape_string($koneksi, $f_q); $where[] = "(p.nama_lengkap LIKE '%$q%' OR kp.nomor_sk LIKE '%$q%')"; }
$whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';

$sql = "SELECT kp.*, p.nama_lengkap, mp1.nama_pangkat AS pangkat_lama, mp2.nama_pangkat AS pangkat_baru
        FROM kenaikan_pangkat kp
        JOIN pegawai p ON kp.pegawai_id = p.pegawai_id
        LEFT JOIN master_pangkat mp1 ON kp.pangkat_lama_id = mp1.pangkat_id
        LEFT JOIN master_pangkat mp2 ON kp.pangkat_baru_id = mp2.pangkat_id
        $whereSql
        ORDER BY kp.created_at DESC";
$result = mysqli_query($koneksi, $sql);
$total = $result ? mysqli_num_rows($result) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak Kenaikan Pangkat - Dinas Perpustakaan dan Kearsipan Kota Banjarmasin</title>
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

  <div class="judul"><h1>DAFTAR KENAIKAN PANGKAT</h1></div>

  <div class="info">
    <p><strong>Tanggal Cetak:</strong> <?php echo date('d F Y'); ?></p>
    <p><strong>Total Data:</strong> <?php echo $total; ?> Kenaikan Pangkat</p>
    <?php if ($f_q): ?><p><strong>Cari:</strong> <?php echo htmlspecialchars($f_q); ?></p><?php endif; ?>
    <?php if ($f_plama>0 || $f_pbaru>0): ?><p><strong>Filter Pangkat:</strong> Lama=<?php echo $f_plama ?: '-'; ?>, Baru=<?php echo $f_pbaru ?: '-'; ?></p><?php endif; ?>
    <?php if ($f_from_sk || $f_to_sk): ?><p><strong>Rentang Tgl SK:</strong> <?php echo htmlspecialchars($f_from_sk ?: '-'); ?> s/d <?php echo htmlspecialchars($f_to_sk ?: '-'); ?></p><?php endif; ?>
    <?php if ($f_from_tmt || $f_to_tmt): ?><p><strong>Rentang TMT:</strong> <?php echo htmlspecialchars($f_from_tmt ?: '-'); ?> s/d <?php echo htmlspecialchars($f_to_tmt ?: '-'); ?></p><?php endif; ?>
  </div>

  <table>
    <thead>
      <tr>
        <th width="5%">No</th>
        <th width="25%">Nama Pegawai</th>
        <th width="20%">Pangkat Lama</th>
        <th width="20%">Pangkat Baru</th>
        <th width="15%">No SK</th>
        <th width="15%">Tanggal SK</th>
        <th width="15%">TMT</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($total > 0) { $no = 1; mysqli_data_seek($result, 0); while ($r = mysqli_fetch_assoc($result)) { echo '<tr>'
        . '<td class="text-center">'.$no++.'</td>'
        . '<td>'.htmlspecialchars($r['nama_lengkap']).'</td>'
        . '<td class="text-center">'.htmlspecialchars($r['pangkat_lama'] ?? '-').'</td>'
        . '<td class="text-center">'.htmlspecialchars($r['pangkat_baru']).'</td>'
        . '<td class="text-center">'.htmlspecialchars($r['nomor_sk'] ?? '-').'</td>'
        . '<td class="text-center">'.($r['tanggal_sk'] ? date('d/m/Y', strtotime($r['tanggal_sk'])) : '-').'</td>'
        . '<td class="text-center">'.($r['tmt'] ? date('d/m/Y', strtotime($r['tmt'])) : '-').'</td>'
        . '</tr>'; } } else { echo '<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>'; } ?>
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

