<?php
// Konfigurasi Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sdm_dispersip";

// Membuat koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($koneksi, "utf8mb4");

/**
 * Menghitung jumlah hari kerja (Senin-Jumat) antara dua tanggal (inklusif)
 * dan mengabaikan hari libur nasional jika ada di database.
 * 
 * @param mysqli $koneksi Objek koneksi database
 * @param string $startDate Tanggal mulai (Y-m-d)
 * @param string $endDate Tanggal selesai (Y-m-d)
 * @return int Jumlah hari kerja
 */
function hitungHariKerja($koneksi, $startDate, $endDate) {
    if (!$startDate || !$endDate) return 0;
    
    $start = strtotime($startDate);
    $end = strtotime($endDate);
    
    if ($start > $end) return 0;
    
    // Ambil daftar hari libur dalam range tanggal tersebut
    $libur = [];
    $query = "SELECT tanggal FROM hari_libur WHERE tanggal BETWEEN ? AND ?";
    $stmt = mysqli_prepare($koneksi, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $libur[] = $row['tanggal'];
        }
        mysqli_stmt_close($stmt);
    }
    
    $hariKerja = 0;
    $current = $start;
    
    while ($current <= $end) {
        $dayOfWeek = date('w', $current); // 0 (Sun) - 6 (Sat)
        $currentDate = date('Y-m-d', $current);
        
        // Cek jika bukan Sabtu (6) atau Minggu (0) DAN bukan hari libur
        if ($dayOfWeek != 0 && $dayOfWeek != 6 && !in_array($currentDate, $libur)) {
            $hariKerja++;
        }
        
        $current = strtotime('+1 day', $current);
    }
    
    return $hariKerja;
}

/**
 * Menentukan predikat penilaian kinerja berdasarkan skor akhir.
 * Range nilai:
 * 90 - 100   : Sangat Baik
 * 76 - <90   : Baik
 * 61 - <76   : Cukup
 * 51 - <61   : Kurang
 * 0  - <51   : Sangat Kurang
 * 
 * @param float $skor Skor akhir
 * @return string Predikat
 */
function getPredikatKinerja($skor) {
    if ($skor >= 90) return 'Sangat Baik';
    if ($skor >= 76) return 'Baik';
    if ($skor >= 61) return 'Cukup';
    if ($skor >= 51) return 'Kurang';
    return 'Sangat Kurang';
}
?>
