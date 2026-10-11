<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'lang.php';
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang_code = $_SESSION['lang'] ?? 'ar';
$text = $lang; 
$dir = $lang_code === 'ar' ? 'rtl' : 'ltr';
$currentPage = basename($_SERVER['PHP_SELF']);

if (!function_exists('e')) {
    function e($val) { return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('tr')) {
    function tr($key) { global $text; return $text[$key] ?? $key; }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(tr('site_name')) ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/public-home.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            background: #f5f7f6;
            line-height: 1.6;
        }

        .public-header {
            background: #145c32;
            padding: 12px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #fff;
        }

        .brand img { height: 40px; }
        .brand span { font-weight: 700; font-size: 18px; }

        .public-nav { display: flex; gap: 25px; align-items: center; }
        .public-nav a { color: #e0e0e0; text-decoration: none; transition: 0.3s; }
        .public-nav a:hover, .public-nav a.active { color: #fff; }

        .hero { padding: 100px 0; background: #f5f7f6; }
        .hero-container {
            width: 85%;
            margin: auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .hero-text h1 { font-size: 42px; color: #145c32; margin: 10px 0; }
        .hero-text p { color: #555; font-size: 16px; }
        .hero-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 28px;
            background: #145c32;
            color: #fff;
            border-radius: 30px;
            text-decoration: none;
        }

        .hero-image img { width: 100%; max-width: 400px; display: block; margin: auto; }

        .section-goals {
            padding: 80px 0 120px 0; 
            width: 85%;
            margin: auto;
        }

        .section-goals h1 {
            text-align: center;
            color: #145c32;
            margin-bottom: 50px;
            font-size: 32px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .card-item {
            background: white;
            padding: 35px 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: 0.3s;
        }

        .card-item:hover { transform: translateY(-10px); }
        .card-item h3 { color: #145c32; margin-bottom: 15px; }
        .card-item p { color: #666; font-size: 15px; }

        @media(max-width:900px){
            .hero-container { grid-template-columns: 1fr; text-align: center; }
            .public-header { padding: 12px 20px; }
        }
    </style>
</head>
<body>

<header class="public-header">
    <a class="brand" href="index.php">
        <img src="assets/img/logo2.png" alt="Logo" onerror="this.style.display='none'">
        <span><?= e(tr('site_name')) ?></span>
    </a>

    <nav class="public-nav">
        <a class="<?= $currentPage == 'index.php' ? 'active' : '' ?>" href="index.php">
            <i class="bi bi-house"></i> <?= e(tr('home')) ?>
        </a>
        <a class="<?= $currentPage == 'about.php' ? 'active' : '' ?>" href="about.php">
            <i class="bi bi-info-circle"></i> <?= e(tr('about')) ?>
        </a>
        <a href="login_register.php">
            <i class="bi bi-box-arrow-in-right"></i> <?= e(tr('login')) ?>
        </a>
        <div class="lang-switch">
            <a href="?lang=ar">AR</a> | <a href="?lang=en">EN</a>
        </div>
    </nav>
</header>

<main>
    <section class="hero">
        <div class="hero-container">
            <div class="hero-text">
                <small><?= $text['hero_small'] ?? '' ?></small>
                <h1><?= $text['hero_title'] ?? '' ?></h1>
                <p><?= $text['hero_desc'] ?? '' ?></p>
                <a href="#goals" class="hero-btn"><?= $text['hero_btn'] ?? 'استكشف' ?></a>
            </div>
            <div class="hero-image">
                <img src="assets/img/logo.png" alt="Logo">
            </div>
        </div>
    </section>

    <section id="goals" class="section-goals">
        <h1><?= $text['goals_title'] ?? 'أهدافنا' ?></h1>
        <div class="cards-grid">
            <div class="card-item">
                <h3><?= $text['goal1_title'] ?? '' ?></h3>
                <p><?= $text['goal1_desc'] ?? '' ?></p>
            </div>
            <div class="card-item">
                <h3><?= $text['goal2_title'] ?? '' ?></h3>
                <p><?= $text['goal2_desc'] ?? '' ?></p>
            </div>
            <div class="card-item">
                <h3><?= $text['goal3_title'] ?? '' ?></h3>
                <p><?= $text['goal3_desc'] ?? '' ?></p>
            </div>
        </div>
    </section>
</main>

<?php include("includes/footer.php"); ?>

</body>
</html>