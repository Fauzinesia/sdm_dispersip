<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

// Get filter parameters
$f_periode = $_GET['f_periode'] ?? date('Y-m');
$f_pegawai = $_GET['f_pegawai'] ?? '';

// Query rekap absensi
$query = "SELECT 
            p.pegawai_id,
            p.nip,
            p.nama_lengkap,
            mj.nama_jabatan,
            COUNT(*) as total_hari,
            SUM(CASE WHEN a.status_absensi = 'Hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN a.status_absensi = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN a.status_absensi = 'Tidak Hadir' THEN 1 ELSE 0 END) as tidak_hadir,
            SUM(CASE WHEN a.status_absensi = 'Izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN a.status_absensi = 'Sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN a.status_absensi = 'Cuti' THEN 1 ELSE 0 END) as cuti
          FROM pegawai p
          LEFT JOIN absensi a ON p.pegawai_id = a.pegawai_id AND DATE_FORMAT(a.tanggal, '%Y-%m') = '" . mysqli_real_escape_string($koneksi, $f_periode) . "'
          LEFT JOIN master_jabatan mj ON p.jabatan_id = mj.jabatan_id
          WHERE p.status_aktif = 'Aktif'";

if ($f_pegawai) {
    $query .= " AND p.pegawai_id = " . intval($f_pegawai);
}

$query .= " GROUP BY p.pegawai_id, p.nip, p.nama_lengkap, mj.nama_jabatan
            ORDER BY p.nama_lengkap ASC";

$result = mysqli_query($koneksi, $query);
$total = mysqli_num_rows($result);

// Get pegawai name if filtered
$pegawai_name = '';
if ($f_pegawai) {
    $peg_query = mysqli_query($koneksi, "SELECT nama_lengkap FROM pegawai WHERE pegawai_id = " . intval($f_pegawai));
    if ($peg_row = mysqli_fetch_assoc($peg_query)) {
        $pegawai_name = $peg_row['nama_lengkap'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rekap Absensi - Dinas Perpustakaan dan Kearsipan Kota Banjarmasin</title>
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
            font-size: 9pt;
        }
        
        td {
            padding: 6px;
            font-size: 9pt;
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
        <h1>REKAP ABSENSI PEGAWAI BULANAN</h1>
    </div>

    <!-- Info -->
    <div class="info">
        <p><strong>Tanggal Cetak:</strong> <?php echo date('d F Y'); ?></p>
        <p><strong>Periode:</strong> <?php echo date('F Y', strtotime($f_periode . '-01')); ?></p>
        <?php if ($pegawai_name): ?>
        <p><strong>Pegawai:</strong> <?php echo htmlspecialchars($pegawai_name); ?></p>
        <?php else: ?>
        <p><strong>Pegawai:</strong> Semua Pegawai Aktif</p>
        <?php endif; ?>
        <p><strong>Total Pegawai:</strong> <?php echo $total; ?> Orang</p>
    </div>

    <!-- Tabel Data -->
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">No</th>
                <th rowspan="2" style="width: 12%;">NIP</th>
                <th rowspan="2" style="width: 18%;">Nama Pegawai</th>
                <th rowspan="2" style="width: 15%;">Jabatan</th>
                <th colspan="6">Status Kehadiran</th>
                <th rowspan="2" style="width: 5%;">Total</th>
                <th rowspan="2" style="width: 7%;">% Hadir</th>
            </tr>
            <tr>
                <th style="width: 5%;">H</th>
                <th style="width: 5%;">T</th>
                <th style="width: 5%;">TH</th>
                <th style="width: 5%;">I</th>
                <th style="width: 5%;">S</th>
                <th style="width: 5%;">C</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($total > 0) {
                $no = 1;
                mysqli_data_seek($result, 0);
                while ($row = mysqli_fetch_assoc($result)) {
                    $total_hari = $row['total_hari'];
                    $hadir = $row['hadir'];
                    $persen = $total_hari > 0 ? round(($hadir / $total_hari) * 100, 1) : 0;
                    
                    echo '<tr>';
                    echo '<td class="text-center">' . $no++ . '</td>';
                    echo '<td>' . htmlspecialchars($row['nip'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['nama_jabatan'] ?? '-') . '</td>';
                    echo '<td class="text-center">' . $row['hadir'] . '</td>';
                    echo '<td class="text-center">' . $row['terlambat'] . '</td>';
                    echo '<td class="text-center">' . $row['tidak_hadir'] . '</td>';
                    echo '<td class="text-center">' . $row['izin'] . '</td>';
                    echo '<td class="text-center">' . $row['sakit'] . '</td>';
                    echo '<td class="text-center">' . $row['cuti'] . '</td>';
                    echo '<td class="text-center"><strong>' . $total_hari . '</strong></td>';
                    echo '<td class="text-center"><strong>' . $persen . '%</strong></td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="12" class="text-center">Tidak ada data</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <!-- Keterangan -->
    <div style="margin-bottom: 20px; font-size: 9pt;">
        <p><strong>Keterangan:</strong></p>
        <p>H = Hadir | T = Terlambat | TH = Tidak Hadir | I = Izin | S = Sakit | C = Cuti</p>
    </div>

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
