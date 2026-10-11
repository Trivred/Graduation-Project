<?php
/**
 * Admin sidebar.
 * Markup only. Counts are isolated in includes/admin_sidebar_data.php.
 */
require_once __DIR__ . '/admin_sidebar_data.php';

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'admin_dashboard.php');
$stats = admin_sidebar_stats($conn ?? null);

if (!function_exists('admin_sidebar_active')) {
    function admin_sidebar_active(array $pages, string $currentPage): string
    {
        return in_array($currentPage, $pages, true) ? 'active' : '';
    }
}

$adminNavGroups = [
    'الرئيسية والتحليل' => [
        ['href' => 'admin_dashboard.php', 'icon' => 'bi-speedometer2', 'label' => 'الرئيسية', 'pages' => ['admin_dashboard.php']],
        ['href' => 'admin_alerts.php', 'icon' => 'bi-bell-fill', 'label' => 'تنبيهات الغطاء النباتي', 'pages' => ['admin_alerts.php']],
        ['href' => 'admin_reports.php', 'icon' => 'bi-file-earmark-arrow-down-fill', 'label' => 'تصدير التقارير', 'pages' => ['admin_reports.php'], 'class' => 'report-link'],
        ['href' => 'vegetation.php', 'icon' => 'bi-map-fill', 'label' => 'الخريطة التفصيلية', 'pages' => ['vegetation.php']],
        ['href' => 'admin_risk_areas.php', 'icon' => 'bi-geo-alt-fill', 'label' => 'تحليل المناطق الخطرة', 'pages' => ['admin_risk_areas.php']],
        ['href' => 'admin_risk_dashboard.php', 'icon' => 'bi-activity', 'label' => 'لوحة المخاطر التفاعلية', 'pages' => ['admin_risk_dashboard.php']],
    ],
    'إدارة البيانات' => [
        ['href' => 'admin_planet_management.php', 'icon' => 'bi-tree', 'label' => 'إدارة الغطاء النباتي', 'pages' => ['admin_planet_management.php']],
        ['href' => 'admin_rare.php', 'icon' => 'bi-star-fill', 'label' => 'إدارة النباتات النادرة', 'pages' => ['admin_rare.php']],
        ['href' => 'admin_beneficial.php', 'icon' => 'bi-heart-fill', 'label' => 'إدارة النباتات النافعة', 'pages' => ['admin_beneficial.php']],
        ['href' => 'admin_toxic.php', 'icon' => 'bi-radioactive', 'label' => 'إدارة النباتات السامة', 'pages' => ['admin_toxic.php']],
        ['href' => 'admin_plant_diseases.php', 'icon' => 'bi-virus2', 'label' => 'إدارة الأمراض النباتية', 'pages' => ['admin_plant_diseases.php']],
    ],
    'النظام' => [
                ['href' => 'plantes_add.php', 'icon' => 'bi-plus-circle-fill', 'label' => 'إضافة نباتات', 'pages' => ['plantes_add.php']],
        ['href' => 'Activity_logs.php', 'icon' => 'bi-clock-history', 'label' => 'سجل العمليات ', 'pages' => ['Activity_logs.php']],

        ['href' => 'admin_users.php', 'icon' => 'bi-people-fill', 'label' => 'إدارة المستخدمين', 'pages' => ['admin_users.php', 'edit_user.php']],
        
    ],
];
?>
<aside class="col-lg-2 col-md-3 admin-sidebar" aria-label="قائمة الأدمن">
    <div class="admin-sidebar-brand">
        <img class="admin-sidebar-logo" src="assets/img/logo2.png" alt="Logo" onerror="this.style.display='none'">
        <span class="admin-sidebar-title">لوحة التحكم </span>
    </div>

    <nav class="admin-sidebar-nav">
        <?php foreach ($adminNavGroups as $groupTitle => $items): ?>
            <div class="admin-sidebar-section-title"><?= htmlspecialchars($groupTitle, ENT_QUOTES, 'UTF-8') ?></div>

            <?php foreach ($items as $item): ?>
                <?php
                    $extraClass = $item['class'] ?? '';
                    $activeClass = admin_sidebar_active($item['pages'], $currentPage);
                    $classes = trim('admin-nav-link ' . $extraClass . ' ' . $activeClass);
                ?>
                <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

  
</aside>
