<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /sdm_dispersip/login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: /sdm_dispersip/admin/dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

// Get filter parameters
$f_golongan = $_GET['f_golongan'] ?? '';

// Build WHERE clause
$where = [];
if ($f_golongan && $f_golongan !== '') {
    $where[] = "mp.golongan = '" . mysqli_real_escape_string($koneksi, $f_golongan) . "'";
}

$whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Query data pangkat
$query = "SELECT mp.*,
          (SELECT COUNT(*) FROM pegawai WHERE pangkat_id = mp.pangkat_id) as jumlah_pegawai
          FROM master_pangkat mp
          $whereSql
          ORDER BY mp.golongan ASC, mp.nama_pangkat ASC";

$result = mysqli_query($koneksi, $query);
$total = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Data Pangkat - Dinas Perpustakaan dan Kearsipan Kota Banjarmasin</title>
    <style>
        @media print {
            .no-print { display: none; }
            @page { margin: 1cm; }
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 20px;
            font-size: 12pt;
        }
        
        .kop-surat {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .kop-surat img {
            height: 80px;
            float: left;
            margin-right: 20px;
        }
        
        .kop-surat .header-text {
            text-align: center;
            line-height: 1.3;
        }
        
        .kop-surat h3 {
            margin: 0;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .kop-surat h2 {
            margin: 0;
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .kop-surat p {
            margin: 2px 0;
            font-size: 10pt;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        .judul {
            text-align: center;
            margin: 30px 0 20px 0;
        }
        
        .judul h1 {
            margin: 0;
            font-size: 14pt;
            text-decoration: underline;
            font-weight: bold;
        }
        
        .info {
            margin-bottom: 20px;
            font-size: 11pt;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table, th, td {
            border: 1px solid #000;
        }
        
        th {
            background-color: #f0f0f0;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
        }
        
        td {
            padding: 6px;
            font-size: 10pt;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .ttd {
            margin-top: 40px;
            float: right;
            width: 300px;
            text-align: center;
        }
        
        .ttd p {
            margin: 5px 0;
        }
        
        .ttd .nama {
            margin-top: 60px;
            font-weight: bold;
            text-decoration: underline;
        }
        
        .btn-print {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .btn-print:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">🖨️ Cetak Dokumen</button>
        <button onclick="window.close()" class="btn-print" style="background-color: #6c757d;">❌ Tutup</button>
    </div>

    <!-- Kop Surat -->
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

    <!-- Judul -->
    <div class="judul">
        <h1>DAFTAR DATA PANGKAT</h1>
    </div>

    <!-- Info -->
    <div class="info">
        <p><strong>Tanggal Cetak:</strong> <?php echo date('d F Y'); ?></p>
        <p><strong>Total Data:</strong> <?php echo $total; ?> Pangkat</p>
        <?php if ($f_golongan): ?>
        <p><strong>Filter Golongan:</strong> <?php echo htmlspecialchars($f_golongan); ?></p>
        <?php endif; ?>
    </div>

    <!-- Tabel Data -->
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="40%">Nama Pangkat</th>
                <th width="15%">Golongan</th>
                <th width="15%">Jumlah Pegawai</th>
                <th width="25%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($total > 0) {
                $no = 1;
                mysqli_data_seek($result, 0); // Reset pointer
                while ($row = mysqli_fetch_assoc($result)) {
                    echo '<tr>';
                    echo '<td class="text-center">' . $no++ . '</td>';
                    echo '<td>' . htmlspecialchars($row['nama_pangkat']) . '</td>';
                    echo '<td class="text-center">' . htmlspecialchars($row['golongan']) . '</td>';
                    echo '<td class="text-center">' . $row['jumlah_pegawai'] . ' orang</td>';
                    echo '<td>' . htmlspecialchars($row['keterangan'] ?? '-') . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="5" class="text-center">Tidak ada data</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <!-- Tanda Tangan -->
    <div class="ttd">
        <p>Banjarmasin, <?php echo date('d F Y'); ?></p>
        <p><strong>Kepala Dinas,</strong></p>
        <p class="nama">Drs. Muhammad Ikhsan Alhak, M.Si</p>
        <p>Pembina Utama Muda (IV/c)</p>
        <p>NIP. 19660916 198602 1 002</p>
    </div>

</body>
</html>
