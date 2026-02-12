<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'pegawai') { header("Location: ../admin/dashboard.php"); exit(); }
require_once '../config/koneksi.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { echo 'ID tidak valid'; exit(); }

// Query data cuti dan pegawai
$sql = "SELECT c.*, p.nip, p.nama_lengkap, p.jabatan_id, p.pangkat_id,
        mj.nama_jabatan, mp.nama_pangkat, mp.golongan
        FROM cuti c 
        JOIN pegawai p ON c.pegawai_id=p.pegawai_id 
        LEFT JOIN master_jabatan mj ON p.jabatan_id = mj.jabatan_id
        LEFT JOIN master_pangkat mp ON p.pangkat_id = mp.pangkat_id
        WHERE c.cuti_id=? AND c.pegawai_id=?";

$pegawai_id = $_SESSION['pegawai_id'];
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $id, $pegawai_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res || mysqli_num_rows($res)===0) { 
    mysqli_stmt_close($stmt); 
    echo 'Data tidak ditemukan atau Anda tidak memiliki akses.'; 
    exit(); 
}

$d = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

// Format tanggal Indonesia
function tgl_indo($tanggal) {
    if (!$tanggal) return '';
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

$tahun_ini = date('Y', strtotime($d['tgl_mulai']));
$tgl_mulai_indo = tgl_indo($d['tgl_mulai']);
$tgl_selesai_indo = tgl_indo($d['tgl_selesai']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Formulir Permintaan dan Pemberian Cuti</title>
  <style>
    @media print { 
        .no-print { display: none; } 
        @page { size: A4; margin: 0.8cm 1cm 0.5cm 1cm; }
    }
    body { font-family: 'Times New Roman', Times, serif; margin:0; padding:10px; font-size:10pt; color: #000; }
    .container { width: 100%; max-width: 100%; margin: 0 auto; }
    
    p { margin: 2px 0; line-height: 1.2; }
    
    .header-right { text-align: right; margin-bottom: 5px; font-size: 10pt; }
    .header-title { text-align: center; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; font-size: 12pt; }
    
    table { width: 100%; border-collapse: collapse; margin-bottom: 3px; }
    table, th, td { border: 1px solid black; }
    td { padding: 2px 4px; vertical-align: top; line-height: 1.2; }
    
    .section-title { font-weight: bold; text-transform: uppercase; background-color: #eee; padding: 2px 4px; }
    .no-border { border: none; }
    .no-border-bottom { border-bottom: none; }
    .no-border-top { border-top: none; }
    .center { text-align: center; }
    
    .checkbox-cell { width: 20px; text-align: center; }
    .check-mark { font-family: 'DejaVu Sans', sans-serif; font-weight: bold; }
    
    .btn-print { background-color:#007bff; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; font-size:14px; margin-bottom:10px; }
    .btn-print:hover { background-color:#0056b3; }
  </style>
</head>
<body>
  <div class="no-print" style="text-align: center;">
    <button onclick="window.print()" class="btn-print">🖨️ Cetak Formulir</button>
    <button onclick="window.close()" class="btn-print" style="background-color:#6c757d;">❌ Tutup</button>
  </div>

  <div class="container">
    <div class="header-right">
        <p>Banjarmasin, <?php echo tgl_indo(date('Y-m-d')); ?></p>
        <p style="text-align: left; width: 60%; margin-left: auto;">
        Yth. Kepala Dinas Perpustakaan dan Kearsipan Kota Banjarmasin.<br>
        melalui Kepala Sub Bagian Umum dan Kepegawaian<br>
        di Banjarmasin
        </p>
    </div>

    <div class="header-title">FORMULIR PERMINTAAN DAN PEMBERIAN CUTI</div>

    <!-- I. DATA PEGAWAI -->
    <table>
        <tr>
            <td colspan="4" class="section-title">I. DATA PEGAWAI</td>
        </tr>
        <tr>
            <td width="15%">Nama</td>
            <td width="35%"><?php echo htmlspecialchars($d['nama_lengkap'] ?? ''); ?></td>
            <td width="15%">NIP / NI PPPK</td>
            <td width="35%"><?php echo htmlspecialchars($d['nip'] ?? ''); ?></td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td><?php echo htmlspecialchars($d['nama_jabatan'] ?? '-'); ?></td>
            <td>Masa Kerja</td>
            <td></td>
        </tr>
        <tr>
            <td>Unit Kerja</td>
            <td>Dinas Perpustakaan dan Kearsipan Kota Banjarmasin</td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <!-- II. JENIS CUTI YANG DIAMBIL -->
    <?php
    $jenis = strtolower($d['jenis_cuti']);
    ?>
    <table>
        <tr>
            <td colspan="4" class="section-title">II. JENIS CUTI YANG DIAMBIL**</td>
        </tr>
        <tr>
            <td width="35%">1. Cuti Tahunan</td>
            <td width="15%" class="center"><?php echo (strpos($jenis, 'tahunan') !== false) ? '<span class="check-mark">√</span>' : ''; ?></td>
            <td width="35%">2. Cuti Besar</td>
            <td width="15%" class="center"><?php echo (strpos($jenis, 'besar') !== false) ? '<span class="check-mark">√</span>' : ''; ?></td>
        </tr>
        <tr>
            <td>3. Cuti Sakit</td>
            <td class="center"><?php echo (strpos($jenis, 'sakit') !== false) ? '<span class="check-mark">√</span>' : ''; ?></td>
            <td>4. Cuti Melahirkan</td>
            <td class="center"><?php echo (strpos($jenis, 'melahirkan') !== false) ? '<span class="check-mark">√</span>' : ''; ?></td>
        </tr>
        <tr>
            <td>5. Cuti Karena Alasan Penting</td>
            <td class="center"><?php echo (strpos($jenis, 'penting') !== false) ? '<span class="check-mark">√</span>' : ''; ?></td>
            <td>6. Cuti di Luar Tanggungan Negara</td>
            <td class="center"><?php echo (strpos($jenis, 'luar tanggungan') !== false) ? '<span class="check-mark">√</span>' : ''; ?></td>
        </tr>
    </table>

    <!-- III. ALASAN CUTI -->
    <table>
        <tr>
            <td class="section-title">III. ALASAN CUTI</td>
        </tr>
        <tr>
            <td style="height: 30px;"><?php echo htmlspecialchars($d['alasan'] ?? ''); ?></td>
        </tr>
    </table>

    <!-- IV. LAMANYA CUTI -->
    <table>
        <tr>
            <td colspan="6" class="section-title">IV. LAMANYA CUTI</td>
        </tr>
        <tr>
            <td width="10%">Selama</td>
            <td width="20%"><?php echo htmlspecialchars($d['lama_hari']); ?> (hari/bulan/tahun)*</td>
            <td width="10%">Mulai tanggal</td>
            <td width="25%"><?php echo $tgl_mulai_indo; ?></td>
            <td width="5%" class="center">s/d</td>
            <td width="30%"><?php echo $tgl_selesai_indo; ?></td>
        </tr>
    </table>

    <!-- V. CATATAN CUTI -->
    <table>
        <tr>
            <td colspan="5" class="section-title">V. CATATAN CUTI***</td>
        </tr>
        <tr>
            <td colspan="3" width="50%">7. CUTI TAHUNAN</td>
            <td width="30%">8. CUTI BESAR</td>
            <td width="20%"></td>
        </tr>
        <tr>
            <td width="15%" class="center">Tahun</td>
            <td width="15%" class="center">Sisa</td>
            <td width="20%" class="center">Keterangan</td>
            <td>9. CUTI SAKIT</td>
            <td></td>
        </tr>
        <tr>
            <td class="center"><?php echo date('Y')-2; ?></td>
            <td></td>
            <td></td>
            <td>10. CUTI MELAHIRKAN</td>
            <td></td>
        </tr>
        <tr>
            <td class="center"><?php echo date('Y')-1; ?></td>
            <td></td>
            <td></td>
            <td>11. CUTI KARENA ALASAN PENTING</td>
            <td></td>
        </tr>
        <tr>
            <td class="center"><?php echo date('Y'); ?></td>
            <td class="center">12</td>
            <td>Diambil <?php echo $d['lama_hari']; ?> hari sisa <?php echo 12 - $d['lama_hari']; ?> hari</td>
            <td>12. CUTI DI LUAR TANGGUNGAN NEGARA</td>
            <td></td>
        </tr>
    </table>

    <!-- VI. ALAMAT SELAMA MENJALANKAN CUTI -->
    <table>
        <tr>
            <td colspan="3" class="section-title">VI. ALAMAT SELAMA MENJALANKAN CUTI</td>
        </tr>
        <tr>
            <td width="50%" rowspan="2" style="vertical-align: top; height: 70px;">
                Banjarmasin
            </td>
            <td width="10%">TELP</td>
            <td width="40%"></td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center; vertical-align: top;">
                <p>Hormat Saya,</p>
                <br><br><br>
                <p style="font-weight: bold; text-decoration: underline;"><?php echo htmlspecialchars($d['nama_lengkap'] ?? ''); ?></p>
                <p>NIP / NI PPPK. <?php echo htmlspecialchars($d['nip'] ?? ''); ?></p>
            </td>
        </tr>
    </table>

    <!-- VII. PERTIMBANGAN ATASAN LANGSUNG -->
    <table>
        <tr>
            <td colspan="4" class="section-title">VII. PERTIMBANGAN ATASAN LANGSUNG**</td>
        </tr>
        <tr>
            <td width="20%" class="center">DISETUJUI</td>
            <td width="20%" class="center">PERUBAHAN****</td>
            <td width="20%" class="center">DITANGGUHKAN****</td>
            <td width="40%" class="center">TIDAK DISETUJUI****</td>
        </tr>
        <tr>
            <td style="height: 70px;"></td>
            <td></td>
            <td></td>
            <td style="vertical-align: top;">
                Kabid Pengelolaan Arsip<br><br><br>
                <span style="text-decoration: underline;">RINI WARDINA, SE.,MT</span><br>
                NIP. 19751120 200003 2 003
            </td>
        </tr>
    </table>

    <!-- VIII. KEPUTUSAN PEJABAT -->
    <table>
        <tr>
            <td colspan="4" class="section-title">VIII. KEPUTUSAN PEJABAT YANG BERWENANG MEMBERIKAN CUTI**</td>
        </tr>
        <tr>
            <td width="20%" class="center">DISETUJUI</td>
            <td width="20%" class="center">PERUBAHAN****</td>
            <td width="20%" class="center">DITANGGUHKAN****</td>
            <td width="40%" class="center">TIDAK DISETUJUI****</td>
        </tr>
        <tr>
            <td style="height: 70px;"></td>
            <td></td>
            <td></td>
            <td style="vertical-align: top;">
                Kepala Dinas,<br><br><br>
                <span style="text-decoration: underline;">Drs.MUHAMAD IKHSAN ALHAK, M.Si</span><br>
                NIP. 19660916 198602 1 002
            </td>
        </tr>
    </table>

  </div>
</body>
</html>
