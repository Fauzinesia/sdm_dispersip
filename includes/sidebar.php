<!-- Sidenav Menu Start -->
<div class="sidenav-menu">

    <!-- Brand Logo -->
    <?php $home = 'admin/dashboard.php'; if (isset($_SESSION['role'])) { if ($_SESSION['role'] === 'pegawai') { $home = 'pegawai/dashboard.php'; } elseif ($_SESSION['role'] === 'verifikator') { $home = 'verifikator/dashboard.php'; } } ?>
    <a href="<?php echo $home; ?>" class="logo" style="height: auto !important; padding: 10px 0;">
        <span class="logo-lg">
            <img src="assets/images/logo_sdm.png" alt="logo SDM" style="height: 150px !important; width: auto; max-height: none !important;">
        </span>
        <span class="logo-sm">
            <img src="assets/images/logo_sdm.png" alt="logo SDM" style="height: 40px !important; width: auto;">
        </span>
    </a>

    <!-- Sidebar Hover Menu Toggle Button -->
    <button class="button-sm-hover">
        <i class="ri-circle-line align-middle"></i>
    </button>

    <!-- Full Sidebar Menu Close Button -->
    <button class="button-close-fullsidebar">
        <i class="ti ti-x align-middle"></i>
    </button>

    <div data-simplebar>

        <!--- Sidenav Menu -->
        <ul class="side-nav">
            <li class="side-nav-title">
                Menu Utama
            </li>

            <li class="side-nav-item">
                <a href="<?php echo $home; ?>" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-dashboard"></i></span>
                    <span class="menu-text"> Dashboard </span>
                </a>
            </li>

            <?php $role = $_SESSION['role'] ?? 'pegawai'; $isAdmin = ($role==='admin'); $isVer = ($role==='verifikator'); $isPeg = ($role==='pegawai'); ?>
            <?php if ($isAdmin): ?>
            <li class="side-nav-title mt-2">
                Data Master
            </li>

            <li class="side-nav-item">
                <a href="admin/pegawai/pegawai.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-users"></i></span>
                    <span class="menu-text"> Data Pegawai </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="admin/jabatan/jabatan.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-briefcase"></i></span>
                    <span class="menu-text"> Master Jabatan </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="admin/pangkat/pangkat.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-award"></i></span>
                    <span class="menu-text"> Master Pangkat </span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
            <li class="side-nav-title mt-2">Kepegawaian</li>
            <li class="side-nav-item">
                <a href="admin/cuti/cuti.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-calendar-event"></i></span>
                    <span class="menu-text"> Manajemen Cuti </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="admin/absensi/absensi.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-clock"></i></span>
                    <span class="menu-text"> Manajemen Absensi </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="admin/absensi/verif_logbook.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-book"></i></span>
                    <span class="menu-text"> Verifikasi Logbook </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="admin/hari_libur/hari_libur.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-calendar-off"></i></span>
                    <span class="menu-text"> Hari Libur </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="admin/kenaikan_pangkat/kenaikan_pangkat.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-trending-up"></i></span>
                    <span class="menu-text"> Kenaikan Pangkat </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="admin/gaji/gaji.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-cash"></i></span>
                    <span class="menu-text"> Data Gaji </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="admin/penilaian_kinerja/penilaian_kinerja.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-chart-bar"></i></span>
                    <span class="menu-text"> Penilaian Kinerja </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="admin/pensiun/pensiun.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-user-off"></i></span>
                    <span class="menu-text"> Data Pensiun </span>
                </a>
            </li>
            <?php endif; ?>

            <li class="side-nav-title mt-2">Lainnya</li>
            <?php if ($isAdmin): ?>
            <li class="side-nav-item">
                <a href="admin/arsip/arsip.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-file-text"></i></span>
                    <span class="menu-text"> Arsip Dokumen </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="admin/kgb/kgb.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-wallet"></i></span>
                    <span class="menu-text"> Kenaikan Gaji Berkala </span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
            <li class="side-nav-item">
                <a href="admin/users/users.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-user-shield"></i></span>
                    <span class="menu-text"> Manajemen User </span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isPeg): ?>
            <li class="side-nav-item">
            </li>
            <li class="side-nav-item">
                <a href="pegawai/cuti.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-calendar-event"></i></span>
                    <span class="menu-text"> Pengajuan Cuti </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="pegawai/absensi.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-clock"></i></span>
                    <span class="menu-text"> Absensi Saya </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="pegawai/logbook.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-book"></i></span>
                    <span class="menu-text"> Logbook Harian </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="pegawai/penilaian_kinerja.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-chart-bar"></i></span>
                    <span class="menu-text"> Penilaian Kinerja </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="pegawai/arsip.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-file-text"></i></span>
                    <span class="menu-text"> Arsip Dokumen Saya </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="pegawai/kenaikan_pangkat.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-trending-up"></i></span>
                    <span class="menu-text"> Kenaikan Pangkat </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="pegawai/gaji.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-wallet"></i></span>
                    <span class="menu-text"> Slip Gaji </span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isVer): ?>
            <li class="side-nav-title mt-2">Menu Verifikator</li>
            <li class="side-nav-item">
                <a href="verifikator/cuti.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-check"></i></span>
                    <span class="menu-text"> Persetujuan Cuti </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="verifikator/kgb.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-cash"></i></span>
                    <span class="menu-text"> Kenaikan Gaji Berkala </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="verifikator/kenaikan_pangkat.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-trending-up"></i></span>
                    <span class="menu-text"> Kenaikan Pangkat </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="admin/absensi/verif_logbook.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-book"></i></span>
                    <span class="menu-text"> Verifikasi Logbook </span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="verifikator/penilaian_kinerja.php" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-chart-bar"></i></span>
                    <span class="menu-text"> Penilaian Kinerja </span>
                </a>
            </li>
            <?php endif; ?>

        </ul>
                

        <div class="clearfix"></div>
    </div>
</div>
<!-- Sidenav Menu End -->
