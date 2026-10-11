<?php
/**
 * Admin layout header.
 * - Loads layout CSS and shared admin page CSS from assets/css.
 * - Keeps page files clean: no design code inside PHP pages.
 */
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
            'Open Page',
            'فتح الصفحة: ' . $currentPage
        );
    }
}
$currentAdminFile = basename($_SERVER['PHP_SELF'] ?? 'admin_dashboard.php');

$adminPageTitles = [
    'admin_dashboard.php' => 'لوحة تحكم الأدمن',
    'admin_alerts.php' => 'تنبيهات الغطاء النباتي',
    'admin_reports.php' => 'تصدير التقارير',
    'admin_users.php' => 'إدارة المستخدمين',
    'edit_user.php' => 'تعديل مستخدم',
    'admin_planet_management.php' => 'إدارة الغطاء النباتي',
    'admin_rare.php' => 'إدارة النباتات النادرة',
    'admin_beneficial.php' => 'إدارة النباتات النافعة',
    'admin_toxic.php' => 'إدارة النباتات السامة',
    'admin_harmful.php' => 'إدارة النباتات الضارة',
    'admin_plant_diseases.php' => 'إدارة الأمراض النباتية',
    'admin_risk_areas.php' => 'تحليل المناطق الخطرة',
    'admin_risk_dashboard.php' => 'لوحة المخاطر التفاعلية',
];

$adminBodyClasses = [
    'admin_dashboard.php' => 'admin-dashboard-page',
    'admin_alerts.php' => 'admin-alerts-page',
    'admin_reports.php' => 'admin-reports-page',
    'admin_users.php' => 'admin-users-page',
    'edit_user.php' => 'admin-users-page admin-edit-user-page',
    'admin_risk_areas.php' => 'admin-risk-areas-page',
    'admin_risk_dashboard.php' => 'admin-risk-dashboard-page',
    'admin_planet_management.php' => 'admin-crud-page admin-vegetation-page',
    'admin_rare.php' => 'admin-crud-page admin-rare-page',
    'admin_beneficial.php' => 'admin-crud-page admin-beneficial-page',
    'admin_toxic.php' => 'admin-crud-page admin-toxic-page',
    'admin_harmful.php' => 'admin-crud-page admin-harmful-page',
    'admin_plant_diseases.php' => 'admin-crud-page admin-diseases-page',
];

$pageTitle = $pageTitle ?? ($adminPageTitles[$currentAdminFile] ?? 'لوحة التحكم');
$adminTitle = $adminTitle ?? $pageTitle;
$pageBodyClass = trim(($pageBodyClass ?? '') . ' ' . ($adminBodyClasses[$currentAdminFile] ?? 'admin-page'));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css?v=20260426-final" rel="stylesheet">
    <link href="assets/css/admin-layout.css?v=20260426-final" rel="stylesheet">

    <script src="assets/js/app-layout.js?v=20260426-final" defer></script>
</head>
<body class="admin-body admin-structured-page <?= htmlspecialchars($pageBodyClass, ENT_QUOTES, 'UTF-8') ?>">
<div class="admin-layout row g-0 m-0 flex-nowrap">
