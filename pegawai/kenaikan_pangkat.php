<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'pegawai') { header("Location: ../admin/dashboard.php"); exit(); }
require_once '../config/koneksi.php';

$pegawai_id = intval($_SESSION['pegawai_id'] ?? 0);
// Cek status kepegawaian
$stmt_status = mysqli_prepare($koneksi, "SELECT status_kepegawaian FROM pegawai WHERE pegawai_id = ?");
mysqli_stmt_bind_param($stmt_status, 'i', $pegawai_id);
mysqli_stmt_execute($stmt_status);
$pegawai_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_status));
mysqli_stmt_close($stmt_status);

$allowed_status = ['PNS', 'PPPK'];
$can_access = in_array($pegawai_data['status_kepegawaian'] ?? '', $allowed_status);

$page_title = "Riwayat Kenaikan Pangkat";
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';
?>

<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Riwayat Kenaikan Pangkat</h4>
                    </div>
                </div>
            </div>

            <?php if (!$can_access): ?>
            <div class="alert alert-info" role="alert">
                <h5 class="alert-heading"><i class="ti ti-info-circle me-2"></i>Informasi</h5>
                <p class="mb-0">Fitur Riwayat Kenaikan Pangkat hanya tersedia untuk pegawai dengan status kepegawaian <strong>PNS</strong> dan <strong>PPPK</strong>.</p>
                <p class="mb-0 mt-2">Status kepegawaian Anda: <strong><?php echo htmlspecialchars($pegawai_data['status_kepegawaian'] ?? '-'); ?></strong></p>
                <hr>
                <p class="mb-0"><small>Silakan hubungi bagian kepegawaian untuk informasi lebih lanjut.</small></p>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive-sm">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Nomor SK</th>
                                    <th>Tanggal SK</th>
                                    <th>TMT</th>
                                    <th>Pangkat Lama</th>
                                    <th>Pangkat Baru</th>
                                    <th>File SK</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT kp.*, pl.nama_pangkat as pangkat_lama, pb.nama_pangkat as pangkat_baru 
                                          FROM kenaikan_pangkat kp 
                                          LEFT JOIN master_pangkat pl ON kp.pangkat_lama_id = pl.pangkat_id 
                                          LEFT JOIN master_pangkat pb ON kp.pangkat_baru_id = pb.pangkat_id 
                                          WHERE kp.pegawai_id = ? 
                                          ORDER BY kp.tmt DESC";
                                
                                $stmt = mysqli_prepare($koneksi, $query);
                                mysqli_stmt_bind_param($stmt, 'i', $pegawai_id);
                                mysqli_stmt_execute($stmt);
                                $res = mysqli_stmt_get_result($stmt);

                                if ($res && mysqli_num_rows($res) > 0) {
                                    while ($r = mysqli_fetch_assoc($res)) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($r['nomor_sk'] ?? '-') . '</td>';
                                        echo '<td>' . ($r['tanggal_sk'] ? date('d F Y', strtotime($r['tanggal_sk'])) : '-') . '</td>';
                                        echo '<td>' . ($r['tmt'] ? date('d F Y', strtotime($r['tmt'])) : '-') . '</td>';
                                        echo '<td>' . htmlspecialchars($r['pangkat_lama'] ?? '-') . '</td>';
                                        echo '<td><span class="badge bg-primary">' . htmlspecialchars($r['pangkat_baru'] ?? '-') . '</span></td>';
                                        
                                        echo '<td>';
                                        if (!empty($r['file_sk'])) {
                                            echo '<a href="/sdm_dispersip/' . htmlspecialchars($r['file_sk']) . '" target="_blank" class="btn btn-sm btn-info"><i class="ti ti-file-text me-1"></i>Lihat</a>';
                                        } else {
                                            echo '-';
                                        }
                                        echo '</td>';
                                        
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center">Belum ada data kenaikan pangkat.</td></tr>';
                                }
                                mysqli_stmt_close($stmt);
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
