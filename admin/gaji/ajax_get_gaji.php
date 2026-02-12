<?php
require_once dirname(__DIR__, 2) . '/config/koneksi.php';

$pegawai_id = intval($_GET['id'] ?? 0);
$response = ['status' => 'success', 'gaji_pokok' => 0];

if ($pegawai_id > 0) {
    // Ambil gaji terbaru dari KGB yang sudah disahkan
    // Kita urutkan berdasarkan TMT (Tanggal Mulai Tugas) paling baru
    $query = "SELECT gaji_baru FROM kgb 
              WHERE pegawai_id = ? AND status = 'Disahkan' 
              ORDER BY tmt_mulai DESC, tanggal_sk DESC LIMIT 1";
              
    $stmt = mysqli_prepare($koneksi, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $pegawai_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $response['gaji_pokok'] = floatval($row['gaji_baru']);
        }
        mysqli_stmt_close($stmt);
    }
}

header('Content-Type: application/json');
echo json_encode($response);
