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

if ($d['status'] !== 'Disetujui') {
    echo 'Surat Izin Cuti hanya dapat dicetak jika status pengajuan "Disetujui".';
    exit();
}

// Format tanggal Indonesia
function tgl_indo($tanggal) {
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
$tgl_masuk_kerja = tgl_indo(date('Y-m-d', strtotime($d['tgl_selesai'] . ' +1 day'))); // Asumsi masuk kerja besoknya (bisa disesuaikan logic hari kerja)
$bulan_romawi = [1=>'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
$bln_skrg_romawi = $bulan_romawi[date('n')];

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Surat Izin Cuti - <?php echo htmlspecialchars($d['nama_lengkap']); ?></title>
  <style>
    @media print { .no-print { display: none; } @page { margin: 1.5cm 2cm; } }
    body { font-family: 'Times New Roman', Times, serif; margin:0; padding:20px; font-size:12pt; color: #000; }
    .container { max-width: 21cm; margin: 0 auto; }
    .kop-surat { text-align:center; border-bottom:3px solid #000; padding-bottom:10px; margin-bottom:20px; position: relative; }
    .kop-surat img { height:90px; position: absolute; left: 0; top: 0; }
    .kop-surat .header-text { text-align:center; margin-left: 0; }
    .kop-surat h3 { margin:0; font-size:14pt; font-weight:bold; text-transform:uppercase; letter-spacing: 1px; }
    .kop-surat h2 { margin:5px 0; font-size:16pt; font-weight:bold; text-transform:uppercase; letter-spacing: 1px; }
    .kop-surat p { margin:2px 0; font-size:10pt; }
    
    .judul { text-align:center; margin:30px 0 30px 0; }
    .judul h4 { margin:0; font-size:12pt; text-decoration:underline; font-weight:bold; text-transform: uppercase; }
    .judul p { margin:5px 0 0 0; font-size:12pt; }
    
    .content { font-size:12pt; line-height:1.5; }
    .data-table { width: 100%; margin-bottom: 10px; }
    .data-table td { vertical-align: top; padding: 2px 0; }
    .label { width: 220px; }
    .titik { width: 10px; text-align: center; }
    
    .ketentuan { margin-top: 10px; }
    .ketentuan ol { padding-left: 20px; margin-top: 5px; }
    .ketentuan li { margin-bottom: 5px; text-align: justify; }

    .ttd { margin-top:50px; float:right; width:300px; }
    .ttd p { margin: 0; line-height: 1.5; }
    .ttd .nama { margin-top:70px; font-weight:bold; text-decoration:underline; }
    
    .btn-print { background-color:#007bff; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; font-size:14px; margin-bottom:20px; }
    .btn-print:hover { background-color:#0056b3; }
    
    .clearfix::after { content:""; display:table; clear:both; }
  </style>
</head>
<body>
  <div class="no-print" style="text-align: center;">
    <button onclick="window.print()" class="btn-print">🖨️ Cetak Surat</button>
    <button onclick="window.close()" class="btn-print" style="background-color:#6c757d;">❌ Tutup</button>
  </div>

  <div class="container">
    <div class="kop-surat clearfix">
        <img src="../assets/images/logo_bjm.png" alt="Logo Banjarmasin">
        <div class="header-text">
            <h3>PEMERINTAH KOTA BANJARMASIN</h3>
            <h2>DINAS PERPUSTAKAAN DAN KEARSIPAN</h2>
            <p>Jalan K.S Tubun RT.05 RW.01 /Fax (0511) 3362523</p>
            <p>Banjarmasin 70114</p>
        </div>
    </div>

    <div class="judul">
        <h4>SURAT IZIN CUTI <?php echo strtoupper($d['jenis_cuti']); ?></h4>
        <p>NOMOR : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; / &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; / SET -DISPERSIP</p>
    </div>

    <div class="content">
        <p>Diberikan izin cuti <?php echo htmlspecialchars($d['jenis_cuti']); ?>, untuk tahun <?php echo $tahun_ini; ?>:</p>
        
        <table class="data-table">
            <tr>
                <td class="label">Nama</td>
                <td class="titik">:</td>
                <td><?php echo htmlspecialchars($d['nama_lengkap'] ?? ''); ?></td>
            </tr>
            <tr>
                <td class="label">NIP / NI PPPK</td>
                <td class="titik">:</td>
                <td><?php echo htmlspecialchars($d['nip'] ?? ''); ?></td>
            </tr>
            <tr>
                <td class="label">Pangkat/Gol</td>
                <td class="titik">:</td>
                <td><?php echo htmlspecialchars($d['nama_pangkat'] ?? '-'); ?> (<?php echo htmlspecialchars($d['golongan'] ?? '-'); ?>)</td>
            </tr>
            <tr>
                <td class="label">Jabatan</td>
                <td class="titik">:</td>
                <td><?php echo htmlspecialchars($d['nama_jabatan'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="label">Unit Kerja</td>
                <td class="titik">:</td>
                <td>Dinas Perpustakaan dan Kearsipan</td>
            </tr>
            <tr>
                <td class="label">Lamanya</td>
                <td class="titik">:</td>
                <td><?php echo htmlspecialchars($d['lama_hari']); ?> (<?php echo terbilang($d['lama_hari']); ?>) Hari Kerja</td>
            </tr>
            <tr>
                <td class="label">Alamat selama menjalankan Cuti</td>
                <td class="titik">:</td>
                <td>Banjarmasin</td> <!-- Bisa disesuaikan jika ada kolom alamat cuti di DB -->
            </tr>
            <tr>
                <td class="label">Terhitung mulai Tanggal</td>
                <td class="titik">:</td>
                <td><?php echo $tgl_mulai_indo; ?> s.d <?php echo $tgl_selesai_indo; ?></td>
            </tr>
            <tr>
                <td class="label">Harus kembali masuk kerja</td>
                <td class="titik">:</td>
                <td><?php echo $tgl_masuk_kerja; ?></td>
            </tr>
        </table>

        <div class="ketentuan">
            <p>Dengan ketentuan sebagai berikut :</p>
            <ol>
                <li>Sebelum menjalankan izin cuti <?php echo htmlspecialchars($d['jenis_cuti']); ?>, wajib menyerahkan pekerjaannya kepada atasan langsung atau Pejabat lain yang ditunjuk.</li>
                <li>Setelah selesai menjalankan izin cuti <?php echo htmlspecialchars($d['jenis_cuti']); ?>, wajib melaporkan diri kepada atasan langsung dan bekerja kembali sebagaimana mestinya.</li>
            </ol>
        </div>

        <div class="ttd">
            <p>Banjarmasin, &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo tgl_indo(date('Y-m-d')); ?></p>
            <p>Kepala Dinas,</p>
            <br><br><br>
            <p class="nama">Drs. MUHAMAD IKHSAN ALHAK, M.Si</p>
            <p>Pembina Utama Muda (IV/c)</p>
            <p>NIP. 19660916 198602 1 002</p>
        </div>
    </div>
  </div>
</body>
</html>
<?php
function terbilang($x) {
  $angka = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
  if ($x < 12) return " " . $angka[$x];
  elseif ($x < 20) return terbilang($x - 10) . " Belas";
  elseif ($x < 100) return terbilang($x / 10) . " Puluh" . terbilang($x % 10);
  elseif ($x < 200) return " Seratus" . terbilang($x - 100);
  elseif ($x < 1000) return terbilang($x / 100) . " Ratus" . terbilang($x % 100);
  elseif ($x < 2000) return " Seribu" . terbilang($x - 1000);
  elseif ($x < 1000000) return terbilang($x / 1000) . " Ribu" . terbilang($x % 1000);
  elseif ($x < 1000000000) return terbilang($x / 1000000) . " Juta" . terbilang($x % 1000000);
}
?>
