<!-- Topbar Start -->
<header class="app-topbar" id="header">
    <div class="page-container topbar-menu">
        <div class="d-flex align-items-center gap-2">

            <!-- Brand Logo -->
            <a href="admin/dashboard.php" class="logo">
                <span class="logo-light">
                    <span class="logo-lg"><img src="assets/images/logo.png" alt="logo"></span>
                    <span class="logo-sm"><img src="assets/images/logo-sdm.png" alt="small logo" style="height: 45px; width: auto;"></span>
                </span>

                <span class="logo-dark">
                    <span class="logo-lg"><img src="assets/images/logo-dark.png" alt="dark logo"></span>
                    <span class="logo-sm"><img src="assets/images/logo-sdm.png" alt="small logo" style="height: 45px; width: auto;"></span>
                </span>
            </a>

            <!-- Sidebar Menu Toggle Button -->
            <button class="sidenav-toggle-button">
                <i class="ri-menu-5-line fs-24"></i>
            </button>

            <!-- Horizontal Menu Toggle Button -->
            <button class="topnav-toggle-button px-2" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <i class="ri-menu-5-line fs-24"></i>
            </button>

            <!-- Topbar Page Title -->
            <div class="topbar-item d-none d-md-flex px-2">
                <div>
                    <h4 class="page-title fs-20 fw-semibold mb-0"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h4>
                </div>
            </div>

        </div>

        <div class="d-flex align-items-center gap-2">

            <!-- Light/Dark Mode Button -->
            <div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link" id="light-dark-mode" type="button">
                    <i class="ri-moon-line light-mode-icon fs-22"></i>
                    <i class="ri-sun-line dark-mode-icon fs-22"></i>
                </button>
            </div>

            <!-- User Dropdown -->
            <div class="topbar-item nav-user">
                <div class="dropdown">
                    <a class="topbar-link dropdown-toggle drop-arrow-none px-2" data-bs-toggle="dropdown"
                        data-bs-offset="0,19" type="button" aria-haspopup="false" aria-expanded="false">
                        <img src="assets/images/logo-sdm.png" width="80" class="rounded-circle me-lg-2 d-flex"
                            alt="user-image">
                        <span class="d-lg-flex flex-column gap-1 d-none">
                            <h5 class="my-0"><?php echo isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $_SESSION['username']; ?></h5>
                        </span>
                        <i class="ri-arrow-down-s-line d-none d-lg-block align-middle ms-1"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Selamat Datang!</h6>
                        </div>

                        <!-- item-->
                        <a href="profile.php" class="dropdown-item">
                            <i class="ri-account-circle-line me-1 fs-16 align-middle"></i>
                            <span class="align-middle">Profil Saya</span>
                        </a>

                        <!-- item-->
                        <a href="settings.php" class="dropdown-item">
                            <i class="ri-settings-4-line me-1 fs-16 align-middle"></i>
                            <span class="align-middle">Pengaturan</span>
                        </a>

                        <div class="dropdown-divider"></div>

                        <!-- item-->
                        <a href="logout.php" class="dropdown-item">
                            <i class="ri-logout-box-line me-1 fs-16 align-middle"></i>
                            <span class="align-middle">Logout</span>
                        </a>

                    </div>
                </div>
            </div>

        </div>
    </div>
</header>
<!-- Topbar End -->
