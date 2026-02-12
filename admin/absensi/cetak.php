<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

// Get filter parameters
$f_status = $_GET['f_status'] ?? '';
$f_from = $_GET['f_from'] ?? '';
$f_to = $_GET['f_to'] ?? '';
$f_pegawai = $_GET['f_pegawai'] ?? '';

// Build WHERE clause
$where = [];
if ($f_status) {
    $where[] = "a.status_absensi = '" . mysqli_real_escape_string($koneksi, $f_status) . "'";
}
if ($f_from) {
    $where[] = "a.tanggal >= '" . mysqli_real_escape_string($koneksi, $f_from) . "'";
}
if ($f_to) {
    $where[] = "a.tanggal <= '" . mysqli_real_escape_string($koneksi, $f_to) . "'";
}
if ($f_pegawai) {
    $where[] = "a.pegawai_id = " . intval($f_pegawai);
}

$whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Query data absensi
$query = "SELECT a.*, p.nama_lengkap, p.nip, mj.nama_jabatan
          FROM absensi a
          JOIN pegawai p ON a.pegawai_id = p.pegawai_id
          LEFT JOIN master_jabatan mj ON p.jabatan_id = mj.jabatan_id
          $whereSql
          ORDER BY a.tanggal DESC, p.nama_lengkap ASC";

$result = mysqli_query($koneksi, $query);
$total = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Data Absensi - Dinas Perpustakaan dan Kearsipan Kota Banjarmasin</title>
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
        
        .kop-surat h2 {
            margin: 0;
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .kop-surat h3 {
            margin: 0;
            font-size: 16pt;
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
        <h1>LAPORAN DATA ABSENSI PEGAWAI</h1>
    </div>

    <!-- Info -->
    <div class="info">
        <p><strong>Tanggal Cetak:</strong> <?php echo date('d F Y'); ?></p>
        <?php if ($f_from && $f_to): ?>
        <p><strong>Periode:</strong> <?php echo date('d F Y', strtotime($f_from)); ?> s/d <?php echo date('d F Y', strtotime($f_to)); ?></p>
        <?php elseif ($f_from): ?>
        <p><strong>Mulai Tanggal:</strong> <?php echo date('d F Y', strtotime($f_from)); ?></p>
        <?php elseif ($f_to): ?>
        <p><strong>Sampai Tanggal:</strong> <?php echo date('d F Y', strtotime($f_to)); ?></p>
        <?php endif; ?>
        <?php if ($f_status): ?>
        <p><strong>Filter Status:</strong> <?php echo htmlspecialchars($f_status); ?></p>
        <?php endif; ?>
        <p><strong>Total Data:</strong> <?php echo $total; ?> Absensi</p>
    </div>

    <!-- Tabel Data -->
    <table>
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="10%">Tanggal</th>
                <th width="12%">NIP</th>
                <th width="20%">Nama Pegawai</th>
                <th width="18%">Jabatan</th>
                <th width="8%">Jam Masuk</th>
                <th width="8%">Jam Pulang</th>
                <th width="10%">Status</th>
                <th width="11%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($total > 0) {
                $no = 1;
                mysqli_data_seek($result, 0);
                while ($row = mysqli_fetch_assoc($result)) {
                    echo '<tr>';
                    echo '<td class="text-center">' . $no++ . '</td>';
                    echo '<td class="text-center">' . date('d/m/Y', strtotime($row['tanggal'])) . '</td>';
                    echo '<td>' . htmlspecialchars($row['nip'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['nama_jabatan'] ?? '-') . '</td>';
                    echo '<td class="text-center">' . ($row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-') . '</td>';
                    echo '<td class="text-center">' . ($row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-') . '</td>';
                    echo '<td class="text-center">' . htmlspecialchars($row['status_absensi']) . '</td>';
                    echo '<td>' . htmlspecialchars(substr($row['keterangan'] ?? '-', 0, 30)) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>';
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
