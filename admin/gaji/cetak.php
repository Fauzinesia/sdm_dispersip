<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$f_pegawai = intval($_GET['f_pegawai'] ?? 0);
$f_year = preg_replace('/[^0-9]/', '', $_GET['f_year'] ?? '');
$f_month = preg_replace('/[^0-9]/', '', $_GET['f_month'] ?? '');
$f_q = trim($_GET['f_q'] ?? '');

$where = [];
if ($f_pegawai > 0) { $where[] = "g.pegawai_id = ".$f_pegawai; }
if ($f_year && strlen($f_year)===4) { $where[] = "LEFT(g.periode,4) = '".mysqli_real_escape_string($koneksi,$f_year)."'"; }
if ($f_month && strlen($f_month)===2) { $where[] = "RIGHT(g.periode,2) = '".mysqli_real_escape_string($koneksi,$f_month)."'"; }
if ($f_q) { $q = mysqli_real_escape_string($koneksi, $f_q); $where[] = "(p.nama_lengkap LIKE '%$q%' OR g.keterangan LIKE '%$q%')"; }
$whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';

// Updated query to fetch NIP and Jabatan
$sql = "SELECT g.*, p.nama_lengkap, p.nip, mj.nama_jabatan 
        FROM gaji g 
        JOIN pegawai p ON g.pegawai_id=p.pegawai_id 
        LEFT JOIN master_jabatan mj ON p.jabatan_id = mj.jabatan_id 
        $whereSql 
        ORDER BY g.created_at DESC";
$result = mysqli_query($koneksi, $sql);
$total = $result ? mysqli_num_rows($result) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak Slip Gaji - Dinas Perpustakaan dan Kearsipan Kota Banjarmasin</title>
  <style>
    @media print { 
        .no-print { display: none; } 
        @page { margin: 1cm; size: auto; }
        body { margin: 0; }
    }
    body { font-family: 'Times New Roman', Times, serif; margin:0; padding:20px; font-size:11pt; }
    
    .btn-print { background-color:#007bff; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; font-size:14px; margin-bottom:20px; }
    .btn-print:hover { background-color:#0056b3; }
    
    .clearfix::after { content:""; display:table; clear:both; }
    
    /* Kop Surat Global (Optional to keep or put in every slip) - Keeping global as per "Laporan" context */
    .kop-surat { text-align:center; border-bottom:3px solid #000; padding-bottom:10px; margin-bottom:20px; }
    .kop-surat img { height:80px; float:left; margin-right:20px; }
    .kop-surat .header-text { text-align:center; line-height:1.3; }
    .kop-surat h3 { margin:0; font-size:16pt; font-weight:bold; text-transform:uppercase; }
    .kop-surat h2 { margin:0; font-size:18pt; font-weight:bold; text-transform:uppercase; }
    .kop-surat p { margin:2px 0; font-size:10pt; }

    .judul { text-align:center; margin:20px 0; }
    .judul h1 { margin:0; font-size:14pt; text-decoration:underline; font-weight:bold; }
    
    /* Slip Styles */
    .slip-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .slip-box {
        border: 1px solid #000;
        padding: 20px;
        page-break-inside: avoid;
        margin-bottom: 20px;
        background: #fff;
    }
    .slip-header {
        border-bottom: 2px double #000;
        margin-bottom: 15px;
        padding-bottom: 5px;
        text-align: center;
        font-weight: bold;
        font-size: 12pt;
    }
    .slip-content {
        width: 100%;
    }
    .slip-table {
        width: 100%;
        border-collapse: collapse;
        border: none;
    }
    .slip-table td {
        padding: 4px;
        vertical-align: top;
        border: none;
    }
    .text-right { text-align: right; }
    .text-bold { font-weight: bold; }
    .line-separator {
        border-top: 1px dashed #000;
        margin: 10px 0;
    }
    .slip-footer {
        margin-top: 15px;
        display: flex;
        justify-content: flex-end;
    }
    .slip-ttd {
        text-align: center;
        width: 200px;
    }
    .slip-ttd p { margin: 5px 0; }
    .slip-ttd .nama { margin-top: 50px; font-weight: bold; text-decoration: underline; }

    /* Global TTD at the end of report */
    .global-ttd {
        margin-top:40px; float:right; width:300px; text-align:center;
        page-break-inside: avoid;
    }
    .global-ttd p { margin:5px 0; }
    .global-ttd .nama { margin-top:60px; font-weight:bold; text-decoration:underline; }
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

  <div class="judul"><h1>LAPORAN GAJI PEGAWAI (FORMAT SLIP)</h1></div>

  <div class="info" style="margin-bottom:20px;">
    <p><strong>Tanggal Cetak:</strong> <?php echo date('d F Y'); ?></p>
    <?php if ($f_year): ?><p><strong>Tahun:</strong> <?php echo htmlspecialchars($f_year); ?></p><?php endif; ?>
    <?php if ($f_month): ?><p><strong>Bulan:</strong> <?php echo htmlspecialchars($f_month); ?></p><?php endif; ?>
  </div>

  <div class="slip-container">
      <?php if ($total > 0): ?>
          <?php while ($r = mysqli_fetch_assoc($result)): ?>
            <div class="slip-box">
                 <div class="slip-header">SLIP GAJI - PERIODE <?php echo htmlspecialchars($r['periode']); ?></div>
                 
                 <table class="slip-table">
                    <tr>
                        <td width="15%">Nama</td>
                        <td width="2%">:</td>
                        <td width="40%"><?php echo htmlspecialchars($r['nama_lengkap']); ?></td>
                        
                        <td width="15%">Gaji Pokok</td>
                        <td width="2%">:</td>
                        <td class="text-right" width="26%">Rp <?php echo number_format($r['gaji_pokok'], 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>NIP</td>
                        <td>:</td>
                        <td><?php echo htmlspecialchars($r['nip'] ?? '-'); ?></td>
                        
                        <td>Tunjangan</td>
                        <td>:</td>
                        <td class="text-right">Rp <?php echo number_format($r['tunjangan'], 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>Jabatan</td>
                        <td>:</td>
                        <td><?php echo htmlspecialchars($r['nama_jabatan'] ?? '-'); ?></td>
                        
                        <td>Potongan</td>
                        <td>:</td>
                        <td class="text-right">Rp <?php echo number_format($r['potongan'], 2, ',', '.'); ?></td>
                    </tr>
                 </table>
                 
                 <div class="line-separator"></div>
                 
                 <table class="slip-table">
                     <tr>
                         <td width="57%" class="text-right text-bold">TOTAL DITERIMA</td>
                         <td width="2%">:</td>
                         <td class="text-right text-bold" style="font-size: 1.1em;">Rp <?php echo number_format($r['total_gaji'], 2, ',', '.'); ?></td>
                     </tr>
                 </table>
                 
                 <div class="slip-footer">
                     <div class="slip-ttd">
                         <p>Penerima,</p>
                         <p class="nama"><?php echo htmlspecialchars($r['nama_lengkap']); ?></p>
                     </div>
                 </div>
            </div>
          <?php endwhile; ?>
      <?php else: ?>
          <div style="text-align:center; padding:20px; border:1px solid #ccc;">Tidak ada data gaji yang sesuai filter.</div>
      <?php endif; ?>
  </div>

  <div class="global-ttd">
    <p>Banjarmasin, <?php echo date('d F Y'); ?></p>
    <p><strong>Kepala Dinas,</strong></p>
    <p class="nama">Drs. Muhammad Ikhsan Alhak, M.Si</p>
    <p>Pembina Utama Muda (IV/c)</p>
    <p>NIP. 19660916 198602 1 002</p>
  </div>

</body>
</html>
