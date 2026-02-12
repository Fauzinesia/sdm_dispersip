<?php
// Bottom Navigation Bar untuk Pegawai (Mobile)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'pegawai'):
    $current_page = basename($_SERVER['PHP_SELF']);
    $pegawai_id = $_SESSION['pegawai_id'] ?? 0;
    
    // Get today's absensi status for badge
    $has_absen_today = false;
    if ($pegawai_id) {
        $today = date('Y-m-d');
        $check_absen = mysqli_query($koneksi, "SELECT absensi_id FROM absensi WHERE pegawai_id=$pegawai_id AND tanggal='$today'");
        $has_absen_today = mysqli_num_rows($check_absen) > 0;
    }
?>
<!-- Mobile Bottom Navigation -->
<div class="mobile-bottom-nav">
    <a href="pegawai/dashboard.php" class="nav-item <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
        <i class="ti ti-home"></i>
        <span>Beranda</span>
    </a>
    <a href="pegawai/absensi.php" class="nav-item <?php echo ($current_page === 'absensi.php') ? 'active' : ''; ?>">
        <?php if (!$has_absen_today): ?>
            <span class="badge-dot"></span>
        <?php endif; ?>
        <i class="ti ti-clock"></i>
        <span>Absensi</span>
    </a>
    <a href="pegawai/cuti.php" class="nav-item <?php echo ($current_page === 'cuti.php') ? 'active' : ''; ?>">
        <i class="ti ti-calendar-event"></i>
        <span>Cuti</span>
    </a>
    <a href="pegawai/penilaian_kinerja.php" class="nav-item <?php echo ($current_page === 'penilaian_kinerja.php') ? 'active' : ''; ?>">
        <i class="ti ti-chart-bar"></i>
        <span>Kinerja</span>
    </a>
    <a href="pegawai/arsip.php" class="nav-item <?php echo ($current_page === 'arsip.php') ? 'active' : ''; ?>">
        <i class="ti ti-file-text"></i>
        <span>Arsip</span>
    </a>
</div>

<style>
/* Mobile Bottom Navigation */
.mobile-bottom-nav {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #ffffff;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    z-index: 1000;
    padding: 8px 0;
    border-top: 1px solid #e9ecef;
}

.mobile-bottom-nav .nav-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #6c757d;
    padding: 8px 4px;
    transition: all 0.3s ease;
    position: relative;
}

.mobile-bottom-nav .nav-item i {
    font-size: 24px;
    margin-bottom: 4px;
    transition: all 0.3s ease;
}

.mobile-bottom-nav .nav-item span {
    font-size: 11px;
    font-weight: 500;
}

.mobile-bottom-nav .nav-item.active {
    color: #0d6efd;
}

.mobile-bottom-nav .nav-item.active i {
    transform: scale(1.1);
}

.mobile-bottom-nav .nav-item:hover {
    color: #0d6efd;
    background: rgba(13, 110, 253, 0.05);
}

/* Badge dot for notification */
.mobile-bottom-nav .badge-dot {
    position: absolute;
    top: 8px;
    right: 50%;
    margin-right: -20px;
    width: 8px;
    height: 8px;
    background: #dc3545;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.2);
    }
}

/* Show on mobile */
@media (max-width: 768px) {
    .mobile-bottom-nav {
        display: flex;
    }
    
    /* Add padding to body to prevent content being hidden behind nav */
    body {
        padding-bottom: 70px;
    }
    
    /* Hide desktop sidebar on mobile */
    .left-sidebar {
        transform: translateX(-100%);
    }
    
    /* Adjust page container */
    .page-container {
        margin-left: 0 !important;
    }
}

/* Tablet */
@media (min-width: 769px) and (max-width: 1024px) {
    .mobile-bottom-nav {
        display: flex;
    }
    
    body {
        padding-bottom: 70px;
    }
}
</style>

<?php endif; ?>

    </div> <!-- wrapper end -->

    <!-- Vendor js -->
    <script src="/sdm_dispersip/assets/js/vendor.min.js"></script>

    <!-- App js -->
    <script src="/sdm_dispersip/assets/js/app.js"></script>

    <?php if(isset($extra_js)): ?>
        <!-- Extra JS -->
        <?php echo $extra_js; ?>
    <?php endif; ?>

</body>

</html>
