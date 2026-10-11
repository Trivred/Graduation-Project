<?php
include_once __DIR__ . '/db.php';
include_once __DIR__ . '/log_activity.php';

if (isset($_SESSION['user_id'])) {

    $currentPage = basename($_SERVER['PHP_SELF']);

    $ignoredPages = [
        'Activity_logs.php',
        'logout.php'
    ];

    if (!in_array($currentPage, $ignoredPages)) {

        logActivity(
            $conn,
            $_SESSION['user_id'],
            'Open User Page',
            'قام المستخدم بفتح الصفحة: ' . $currentPage
        );
    }
}

$userNavItems = [
    ['href' => 'user_dashboard.php', 'icon' => 'bi-speedometer2', 'label' => 'لوحة المستخدم'],
    ['href' => 'user_plants.php', 'icon' => 'bi-tree', 'label' => 'عرض الغطاء النباتي'],
    ['href' => 'user_regions.php', 'icon' => 'bi-geo-alt', 'label' => 'عرض المناطق'],
    ['href' => 'plant_diseases.php', 'icon' => 'bi-bug', 'label' => 'عرض اوبئة النباتات'],
    ];

if (!defined('USER_LAYOUT_ASSETS_LOADED')) {
    define('USER_LAYOUT_ASSETS_LOADED', true);
    echo '<link href="assets/css/user-layout.css?v=20260426" rel="stylesheet">' . PHP_EOL;
    echo '<script src="assets/js/app-layout.js?v=20260426" defer></script>' . PHP_EOL;
}
?>
<button class="user-mobile-menu-btn" type="button" data-sidebar-toggle aria-label="فتح القائمة">
    <i class="bi bi-list" aria-hidden="true"></i>
</button>

<aside class="user-sidebar" aria-label="قائمة المستخدم">
    <div class="user-sidebar-brand">
        <img class="user-sidebar-logo" src="assets/img/logo2.png" alt="شعار النظام" onerror="this.style.display='none'">
        <div class="user-sidebar-title">Vegetation System</div>
        <div class="user-sidebar-subtitle">User Panel</div>
    </div>

    <nav class="user-sidebar-nav">
        <?php foreach ($userNavItems as $item): ?>
            <?php $activeClass = $currentPage === $item['href'] ? 'active' : ''; ?>
            <a class="user-sidebar-link <?= $activeClass ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="user-sidebar-footer">
      
        <a class="user-sidebar-action danger" href="logout.php">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            <span>تسجيل الخروج</span>
        </a>
    </div>
</aside>
