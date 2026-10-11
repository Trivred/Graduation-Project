<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login_register.php');
    exit;
}

function scalar_query(mysqli $conn, string $sql, string $column = 'total'): int
{
    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row[$column] ?? 0);
}


$totalPlants = scalar_query($conn, "
    SELECT COUNT(*) AS total
    FROM plant_types
");

$totalRegions = scalar_query($conn, "
    SELECT COUNT(*) AS total
    FROM regions
");

$totalDiseases = scalar_query($conn, "
    SELECT COUNT(*) AS total
    FROM plant_diseases
");

$totalToxic = scalar_query($conn, "
    SELECT COUNT(*) AS total
    FROM toxic_plants
");

$userName = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة المستخدم</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/user-shared.css">
</head>

<body>

<?php include __DIR__ . '/includes/user_sidebar.php'; ?>

<main class="main">

    <section class="hero-welcome">
        <div class="hero-content">
            <h1 class="welcome-title">مرحباً <?= e($userName) ?> </h1>
            <p class="welcome-sub">
          صلاحيات عرض فقط
            </p>
            <span class="welcome-date">
                <i class="bi bi-calendar3"></i>
                <?= date('Y-m-d') ?>
            </span>
        </div>
    </section>

    <section class="cards" aria-label="إحصائيات النظام">

        <article class="card">
            <i class="bi bi-tree-fill"></i>
            <h3 class="counter" data-target="<?= $totalPlants ?>">0</h3>
            <p>إجمالي النباتات</p>
        </article>

        <article class="card">
            <i class="bi bi-geo-alt-fill"></i>
            <h3 class="counter" data-target="<?= $totalRegions ?>">0</h3>
            <p>إجمالي المناطق</p>
        </article>

        <article class="card">
            <i class="bi bi-bug-fill"></i>
            <h3 class="counter" data-target="<?= $totalDiseases ?>">0</h3>
            <p>أمراض مسجلة</p>
        </article>

        <article class="card">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <h3 class="counter" data-target="<?= $totalToxic ?>">0</h3>
            <p>نباتات سامة مسجلة</p>
        </article>

    </section>

    <section class="quick-panel" aria-label="روابط سريعة">

        <a class="quick-link" href="user_plants.php">
            <span>
                <i class="bi bi-tree"></i>
                عرض الغطاء النباتي
            </span>
            <i class="bi bi-arrow-left"></i>
        </a>

        <a class="quick-link" href="user_regions.php">
            <span>
                <i class="bi bi-geo-alt"></i>
                عرض المناطق
            </span>
            <i class="bi bi-arrow-left"></i>
        </a>

        <a class="quick-link" href="plant_diseases.php">
            <span>
                <i class="bi bi-bug"></i>
                عرض أوبئة النباتات
            </span>
            <i class="bi bi-arrow-left"></i>
        </a>

        <a class="quick-link" href="export_regions.php">
            <span>
                <i class="bi bi-download"></i>
                تصدير عرض المناطق
            </span>
            <i class="bi bi-arrow-left"></i>
        </a>

        <a class="quick-link" href="export_diseases.php">
            <span>
                <i class="bi bi-file-earmark-spreadsheet"></i>
                تصدير عرض الأوبئة
            </span>
            <i class="bi bi-arrow-left"></i>
        </a>

    </section>

</main>

<script src="assets/js/app-core.js"></script>
<script src="assets/js/sidebar.js"></script>
<script src="assets/js/user-dashboard.js"></script>

</body>
</html>