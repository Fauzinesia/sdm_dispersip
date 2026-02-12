<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard.php"); exit(); }
require_once '../../config/koneksi.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: /sdm_dispersip/admin/absensi/absensi.php"); exit(); }

$stmt = mysqli_prepare($koneksi, "SELECT a.*, p.nama_lengkap, p.nip, p.jabatan_id, j.nama_jabatan
                                  FROM absensi a 
                                  JOIN pegawai p ON a.pegawai_id=p.pegawai_id 
                                  LEFT JOIN master_jabatan j ON p.jabatan_id=j.jabatan_id
                                  WHERE a.absensi_id=?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$data) { header("Location: /sdm_dispersip/admin/absensi/absensi.php"); exit(); }

$page_title = "Detail Absensi";
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';

$badgeMap = [
    'Hadir' => 'success',
    'Terlambat' => 'warning',
    'Tidak Hadir' => 'danger',
    'Izin' => 'info',
    'Sakit' => 'warning',
    'Cuti' => 'primary'
];
$badge = $badgeMap[$data['status_absensi']] ?? 'secondary';
?>
<div class="page-container">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Detail Absensi</h4></div></div></div>
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Informasi Absensi</h5>
                    <div>
                        <a href="admin/absensi/edit.php?id=<?php echo $id; ?>" class="btn btn-warning"><i class="ti ti-pencil me-1"></i>Edit</a>
                        <a href="admin/absensi/absensi.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">NIP</label>
                            <p class="fw-semibold"><?php echo htmlspecialchars($data['nip'] ?? '-'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Nama Pegawai</label>
                            <p class="fw-semibold"><?php echo htmlspecialchars($data['nama_lengkap']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Jabatan</label>
                            <p class="fw-semibold"><?php echo htmlspecialchars($data['nama_jabatan'] ?? '-'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Tanggal</label>
                            <p class="fw-semibold"><?php echo date('d F Y', strtotime($data['tanggal'])); ?></p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Jam Masuk</label>
                            <p class="fw-semibold"><?php echo $data['jam_masuk'] ? date('H:i', strtotime($data['jam_masuk'])) : '-'; ?></p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Jam Pulang</label>
                            <p class="fw-semibold"><?php echo $data['jam_pulang'] ? date('H:i', strtotime($data['jam_pulang'])) : '-'; ?></p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Status</label>
                            <p><span class="badge bg-<?php echo $badge; ?> fs-6"><?php echo htmlspecialchars($data['status_absensi']); ?></span></p>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-muted">Keterangan</label>
                            <p class="fw-semibold"><?php echo htmlspecialchars($data['keterangan'] ?? '-'); ?></p>
                        </div>
                        <?php if ($data['lat_masuk'] || $data['lng_masuk'] || $data['lat_pulang'] || $data['lng_pulang']): ?>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Lokasi Masuk (GPS)</label>
                            <p class="fw-semibold"><?php echo $data['lat_masuk'] && $data['lng_masuk'] ? htmlspecialchars($data['lat_masuk'] . ', ' . $data['lng_masuk']) : '-'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Lokasi Pulang (GPS)</label>
                            <p class="fw-semibold"><?php echo $data['lat_pulang'] && $data['lng_pulang'] ? htmlspecialchars($data['lat_pulang'] . ', ' . $data['lng_pulang']) : '-'; ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($data['foto_masuk'] || $data['foto_pulang']): ?>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Foto Masuk</label>
                            <?php if ($data['foto_masuk']): ?>
                                <div><img src="../../<?php echo htmlspecialchars($data['foto_masuk']); ?>" alt="Foto Masuk" class="img-thumbnail" style="max-width: 300px;"></div>
                            <?php else: ?>
                                <p class="fw-semibold">-</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Foto Pulang</label>
                            <?php if ($data['foto_pulang']): ?>
                                <div><img src="../../<?php echo htmlspecialchars($data['foto_pulang']); ?>" alt="Foto Pulang" class="img-thumbnail" style="max-width: 300px;"></div>
                            <?php else: ?>
                                <p class="fw-semibold">-</p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Dibuat Pada</label>
                            <p class="fw-semibold"><?php echo date('d F Y H:i', strtotime($data['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
