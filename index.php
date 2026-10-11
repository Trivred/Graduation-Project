<?php
require_once __DIR__ . '/lang.php';

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="<?= e($currentLang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(tr('site_name')) ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/public-home.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>

<body>

<header class="public-header">

    <a class="brand" href="index.php">
        <img src="assets/img/logo2.png" alt="<?= e(tr('site_name')) ?>" onerror="this.style.display='none'">
        <span><?= e(tr('site_name')) ?></span>
    </a>

    <button class="menu-toggle" onclick="toggleMenu()">
        <i class="bi bi-list"></i>
    </button>

    <nav class="public-nav" id="mainNav" aria-label="main navigation">

        <a class="<?= $currentPage == 'index.php' ? 'active' : '' ?>" href="index.php">
            <i class="bi bi-house"></i>
            <?= e(tr('home')) ?>
        </a>

        <a class="<?= $currentPage == 'about.php' ? 'active' : '' ?>" href="about.php">
            <i class="bi bi-info-circle"></i>
            <?= e(tr('about')) ?>
        </a>

        <a class="<?= $currentPage == 'login_register.php' ? 'active' : '' ?>" href="login_register.php">
            <i class="bi bi-box-arrow-in-right"></i>
            <?= e(tr('login')) ?>
        </a>

        <div class="lang-switch">
            <a class="<?= $currentLang === 'ar' ? 'active' : '' ?>" href="?lang=ar">AR</a>
            <a class="<?= $currentLang === 'en' ? 'active' : '' ?>" href="?lang=en">EN</a>
        </div>

    </nav>

</header>

<main class="map-section">
    <section class="map-card">
        <div class="map-title">
            <div>
                <h1>خريطة الغطاء النباتي</h1>
                <p>عرض الخريطة الأساسية مع البيانات النباتية داخل النظام.</p>
            </div>
        </div>
        <iframe class="map-frame" src="vegetation.php" loading="lazy" title="Vegetation Map"></iframe>
    </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/public-home.js"></script>
</body>
</html>
