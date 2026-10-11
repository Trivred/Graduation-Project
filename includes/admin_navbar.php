<?php
/**
 * Admin top navbar.
 * Markup only. Styling is in assets/css/admin-layout.css.
 */
$adminTitle = $adminTitle ?? 'Admin Panel';
?>
<div class="col-lg-10 col-md-9 admin-main p-0">
    <nav class="navbar admin-navbar">
        <div class="container-fluid d-flex justify-content-between align-items-center gap-3">
            <div class="admin-title">
                <button class="admin-sidebar-toggle" type="button" data-admin-sidebar-toggle aria-label="فتح القائمة">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <i class="ri-leaf-line" aria-hidden="true"></i>
                <span><?= htmlspecialchars($adminTitle, ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <a href="logout.php" class="logout-btn">
                <i class="ri-logout-box-r-line" aria-hidden="true"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </nav>

    <div class="admin-content p-4">
