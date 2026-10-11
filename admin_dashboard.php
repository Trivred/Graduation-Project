<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login_register.php');
    exit();
}

$pageTitle = 'لوحة تحكم الأدمن';
$adminTitle = 'Admin Panel';
$pageBodyClass = 'admin-dashboard-page';

function adminDashE($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function adminDashNumber($value, int $decimals = 0): string
{
    return number_format((float)$value, $decimals);
}

function adminDashValue(mysqli $conn, string $sql, string $field = 'total')
{
    $result = @$conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return $row[$field] ?? 0;
}

function adminDashRows(mysqli $conn, string $sql): array
{
    $rows = [];
    $result = @$conn->query($sql);

    if (!$result) {
        return $rows;
    }

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

$totalUsers = (int)adminDashValue($conn, "SELECT COUNT(*) AS total FROM users");
$totalRegions = (int)adminDashValue($conn, "SELECT COUNT(*) AS total FROM regions");
$toxicPlants = (int)adminDashValue($conn, "SELECT COUNT(*) AS total FROM toxic_plants");
$rarePlants = (int)adminDashValue($conn, "SELECT COUNT(*) AS total FROM rare_plants");
$beneficialPlants = (int)adminDashValue($conn, "SELECT COUNT(*) AS total FROM plant_benefits");
$totalDiseases = (int)adminDashValue($conn, "SELECT COUNT(*) AS total FROM plant_diseases");

$highToxicPlants = (int)adminDashValue(
    $conn,
    "SELECT COUNT(*) AS total FROM toxic_plants WHERE IFNULL(toxicity_level, 0) >= 2"
);

$highHazards = (int)adminDashValue(
    $conn,
    "SELECT COUNT(*) AS total FROM plant_hazards WHERE LOWER(IFNULL(severity_level, '')) = 'high'"
);

$lowVegetationAreas = (int)adminDashValue($conn, "
    SELECT COUNT(*) AS total
    FROM (
        SELECT region_id, AVG(IFNULL(density_per_km2, 0)) AS avg_density
        FROM region_plant_stats
        GROUP BY region_id
        HAVING avg_density < 20
    ) AS low_density_regions
");

$highDiseaseAreas = (int)adminDashValue(
    $conn,
    "SELECT COUNT(DISTINCT region_id) AS total FROM disease_spread WHERE IFNULL(spread_percentage, 0) >= 50"
);

$topRiskRegion = adminDashRows($conn, "
    SELECT 
        r.region_name_ar,
        ROUND(AVG(IFNULL(ds.spread_percentage, 0)), 1) AS risk
    FROM regions r
    INNER JOIN disease_spread ds ON r.region_id = ds.region_id
    GROUP BY r.region_id, r.region_name_ar
    ORDER BY risk DESC
    LIMIT 1
");

$topRiskName = $topRiskRegion[0]['region_name_ar'] ?? 'غير متوفر';
$topRiskScore = (float)($topRiskRegion[0]['risk'] ?? 0);

$topRegionsByDisease = adminDashRows($conn, "
    SELECT 
        r.region_name_ar,
        ROUND(AVG(IFNULL(ds.spread_percentage, 0)), 1) AS spread
    FROM regions r
    INNER JOIN disease_spread ds ON r.region_id = ds.region_id
    GROUP BY r.region_id, r.region_name_ar
    ORDER BY spread DESC
    LIMIT 5
");

$topRegionsByDensity = adminDashRows($conn, "
    SELECT 
        r.region_name_ar,
        ROUND(AVG(IFNULL(rps.density_per_km2, 0)), 1) AS density
    FROM regions r
    INNER JOIN region_plant_stats rps ON r.region_id = rps.region_id
    GROUP BY r.region_id, r.region_name_ar
    ORDER BY density ASC
    LIMIT 5
");

$smartAlerts = [];

if ($highDiseaseAreas > 0) {
    $smartAlerts[] = [
        'level' => 'danger',
        'icon' => 'ri-virus-line',
        'title' => 'انتشار وبائي يحتاج متابعة',
        'message' => "يوجد {$highDiseaseAreas} منطقة لديها انتشار مرضي مرتفع، ويجب تحديد الأولويات من صفحة التنبيهات.",
        'action' => 'مراجعة التنبيهات',
        'link' => 'admin_alerts.php?filter=disease',
    ];
}

if ($highToxicPlants > 0) {
    $smartAlerts[] = [
        'level' => $highToxicPlants >= 10 ? 'danger' : 'warning',
        'icon' => 'ri-radio-button-line',
        'title' => 'مؤشر سمية مرتفع',
        'message' => "تم رصد {$highToxicPlants} نبات أو سجل بمستوى سمية مرتفع، ويفضل مراجعة خطة المراقبة.",
        'action' => 'عرض النباتات السامة',
        'link' => 'admin_toxic.php',
    ];
}

if ($lowVegetationAreas > 0) {
    $smartAlerts[] = [
        'level' => 'warning',
        'icon' => 'ri-plant-line',
        'title' => 'انخفاض في الكثافة النباتية',
        'message' => "يوجد {$lowVegetationAreas} منطقة تقل فيها الكثافة عن الحد الآمن، وهذا يساعد في قرارات التشجير.",
        'action' => 'تحليل المناطق',
        'link' => 'admin_risk_areas.php',
    ];
}

if (empty($smartAlerts)) {
    $smartAlerts[] = [
        'level' => 'success',
        'icon' => 'ri-shield-check-line',
        'title' => 'الوضع مستقر',
        'message' => 'لا توجد مؤشرات حرجة حالياً. استمر في تحديث البيانات ومراقبة الخريطة الزمنية.',
        'action' => 'فتح الخريطة',
        'link' => 'vegetation.php',
    ];
}

$criticalCount = count(array_filter($smartAlerts, static fn($alert) => $alert['level'] === 'danger'));
$warningCount = count(array_filter($smartAlerts, static fn($alert) => $alert['level'] === 'warning'));

if ($criticalCount > 0) {
    $systemStatus = 'خطر مرتفع';
    $systemClass = 'danger';
    $systemIcon = 'ri-error-warning-line';
} elseif ($warningCount > 0) {
    $systemStatus = 'يحتاج متابعة';
    $systemClass = 'warning';
    $systemIcon = 'ri-alert-line';
} else {
    $systemStatus = 'مستقر';
    $systemClass = 'success';
    $systemIcon = 'ri-shield-check-line';
}

$dashboardCards = [
    [
        'label' => 'المستخدمون',
        'value' => $totalUsers,
        'icon' => 'ri-user-3-line',
        'class' => 'primary',
        'link' => 'admin_users.php',
    ],
    [
        'label' => 'المناطق',
        'value' => $totalRegions,
        'icon' => 'ri-map-pin-line',
        'class' => 'success',
        'link' => 'admin_risk_areas.php',
    ],
    [
        'label' => 'النباتات السامة',
        'value' => $toxicPlants,
        'icon' => 'ri-radio-button-line',
        'class' => 'danger',
        'link' => 'admin_toxic.php',
    ],
    [
        'label' => 'النباتات النادرة',
        'value' => $rarePlants,
        'icon' => 'ri-star-line',
        'class' => 'warning',
        'link' => 'admin_rare.php',
    ],
    [
        'label' => 'النباتات النافعة',
        'value' => $beneficialPlants,
        'icon' => 'ri-heart-3-line',
        'class' => 'success',
        'link' => 'admin_beneficial.php',
    ],
    [
        'label' => 'الأوبئة النباتية',
        'value' => $totalDiseases,
        'icon' => 'ri-virus-line',
        'class' => 'danger',
        'link' => 'admin_plant_diseases.php',
    ],
    [
        'label' => 'مناطق منخفضة الكثافة',
        'value' => $lowVegetationAreas,
        'icon' => 'ri-leaf-line',
        'class' => 'warning',
        'link' => 'admin_alerts.php?filter=density',
    ],
    [
        'label' => 'انتشار وبائي مرتفع',
        'value' => $highDiseaseAreas,
        'icon' => 'ri-bug-line',
        'class' => 'danger',
        'link' => 'admin_alerts.php?filter=disease',
    ],
];

$dashboardChartData = [
    'diseaseLabels' => array_map(static fn($row) => (string)$row['region_name_ar'], $topRegionsByDisease),
    'diseaseValues' => array_map(static fn($row) => (float)$row['spread'], $topRegionsByDisease),
    'densityLabels' => array_map(static fn($row) => (string)$row['region_name_ar'], $topRegionsByDensity),
    'densityValues' => array_map(static fn($row) => (float)$row['density'], $topRegionsByDensity),
];

include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';
?>

<link rel="stylesheet" href="assets/css/admin.css?v=20260429">
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

<section class="page-head admin-welcome-head">

    <div class="dashboard-header">

        <div class="header-right">
            <span class="small-title">لوحة التحكم الرئيسية</span>

            <h2>
                مرحباً مدير النظام
                <b><?= adminDashE($_SESSION['username'] ?? 'مستخدم') ?></b>
            </h2>
        </div>

        <div class="header-actions">
            <a href="admin_reports.php" class="dashboard-action action-green">
                <i class="ri-file-list-3-line"></i>
                <span>التقارير الكاملة</span>
            </a>

            <a href="<?= adminDashE($_SERVER['PHP_SELF']) ?>" class="dashboard-action action-light">
                <i class="ri-refresh-line"></i>
                <span>تحديث البيانات</span>
            </a>
        </div>

    </div>

</section>

<div class="stats-grid">
    <?php foreach ($dashboardCards as $card): ?>
        <article class="stat-card <?= adminDashE($card['class']) ?>" data-navigate="<?= adminDashE($card['link']) ?>">
            <div>
                <span><?= adminDashE($card['label']) ?></span>
                <b class="counter" data-target="<?= (int)$card['value'] ?>">0</b>
                <small class="text-muted">اضغط لعرض التفاصيل</small>
            </div>

            <div class="icon">
                <i class="<?= adminDashE($card['icon']) ?>"></i>
            </div>
        </article>
    <?php endforeach; ?>
</div>



<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <section class="chart-card h-100">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
                <div>
                    <h5 class="fw-black mb-1">
                        <i class="ri-bar-chart-2-line"></i>
                        أعلى مناطق انتشار الأمراض
                    </h5>
                   
                </div>

                <span class="badge-soft danger">
                    <i class="ri-virus-line"></i>
                    أمراض
                </span>
            </div>

            <?php if (empty($topRegionsByDisease)): ?>
                <div class="empty-state">لا توجد بيانات أمراض كافية لعرض الرسم.</div>
            <?php else: ?>
                <canvas id="diseaseChart" height="150" aria-label="أعلى مناطق انتشار الأمراض"></canvas>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-xl-6">
        <section class="chart-card h-100">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
                <div>
                    <h5 class="fw-black mb-1">
                        <i class="ri-line-chart-line"></i>
                        أقل مناطق الكثافة النباتية
                    </h5>

                </div>

                <span class="badge-soft warning">
                    <i class="ri-plant-line"></i>
                    كثافة
                </span>
            </div>

            <?php if (empty($topRegionsByDensity)): ?>
                <div class="empty-state">لا توجد بيانات كثافة كافية لعرض الرسم.</div>
            <?php else: ?>
                <canvas id="densityChart" height="150" aria-label="أقل مناطق الكثافة النباتية"></canvas>
            <?php endif; ?>
        </section>
    </div>
</div>

<script id="adminDashboardData" type="application/json">
<?= json_encode($dashboardChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="assets/js/admin-dashboard.js?v=20260426-final" defer></script>
<script src="assets/js/admin-table-tools.js?v=20260426-final" defer></script>

<?php
$conn->close();
?>

</body>
</html>