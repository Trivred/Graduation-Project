<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login_register.php');
    exit();
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rows(mysqli $conn, string $sql): array {
    $data = [];
    $result = @$conn->query($sql);
    if (!$result) {
        return $data;
    }
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

function coord($value): ?float {
    if ($value === null || $value === '') {
        return null;
    }
    $number = (float)$value;
    return is_finite($number) ? $number : null;
}

function add_alert(array &$alerts, array $data): void {
    $data['lat'] = coord($data['lat'] ?? null);
    $data['lon'] = coord($data['lon'] ?? null);
    $data['region_id'] = (int)($data['region_id'] ?? 0);
    $alerts[] = $data;
}

$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'critical', 'warning', 'disease', 'toxicity', 'density', 'rare'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$alerts = [];

$densityRows = rows($conn, "
    SELECT
        r.region_id,
        r.region_name_ar,
        ROUND(AVG(IFNULL(rps.density_per_km2, 0)), 1) AS avg_density,
        SUM(IFNULL(rps.plant_count, 0)) AS total_plants,
        AVG(hp.latitude) AS lat,
        AVG(hp.longitude) AS lon
    FROM regions r
    JOIN region_plant_stats rps ON r.region_id = rps.region_id
    LEFT JOIN heatmap_points hp ON hp.region_id = r.region_id
    GROUP BY r.region_id, r.region_name_ar
    HAVING avg_density < 20
    ORDER BY avg_density ASC
    LIMIT 20
");

foreach ($densityRows as $row) {
    $density = (float)$row['avg_density'];
    $score = max(45, min(100, 100 - ($density * 3)));
    $severity = $density < 10 ? 'critical' : 'warning';

    add_alert($alerts, [
        'type' => 'density',
        'severity' => $severity,
        'icon' => 'bi-droplet-half',
        'title' => 'كثافة نباتية منخفضة',
        'region_id' => $row['region_id'],
        'region' => $row['region_name_ar'],
        'value' => $density . ' / كم²',
        'score' => round($score),
        'description' => 'انخفاض واضح في الكثافة النباتية مقارنة بالحد الآمن، وقد يشير إلى تدهور أو حاجة لمتابعة المنطقة.',
        'recommendation' => 'مراجعة بيانات المنطقة ميدانياً ومقارنتها بسنوات سابقة قبل اتخاذ قرار المعالجة.',
        'lat' => $row['lat'],
        'lon' => $row['lon']
    ]);
}

$diseaseRows = rows($conn, "
    SELECT
        r.region_id,
        r.region_name_ar,
        ROUND(AVG(ds.spread_percentage), 1) AS avg_spread,
        AVG(hp.latitude) AS lat,
        AVG(hp.longitude) AS lon
    FROM disease_spread ds
    JOIN regions r ON r.region_id = ds.region_id
    LEFT JOIN heatmap_points hp ON hp.region_id = r.region_id
    GROUP BY r.region_id, r.region_name_ar
    HAVING avg_spread >= 35
    ORDER BY avg_spread DESC
    LIMIT 20
");

foreach ($diseaseRows as $row) {
    $spread = (float)$row['avg_spread'];
    $severity = $spread >= 65 ? 'critical' : 'warning';

    add_alert($alerts, [
        'type' => 'disease',
        'severity' => $severity,
        'icon' => 'bi-virus2',
        'title' => 'انتشار وبائي مرتفع',
        'region_id' => $row['region_id'],
        'region' => $row['region_name_ar'],
        'value' => $spread . '%',
        'score' => round($spread),
        'description' => 'نسبة انتشار الأمراض في هذه المنطقة مرتفعة وقد تؤثر على سلامة الغطاء النباتي.',
        'recommendation' => 'فتح بيانات الأوبئة للمنطقة وتحديد نوع المرض الأكثر تأثيراً.',
        'lat' => $row['lat'],
        'lon' => $row['lon']
    ]);
}

$toxicityRows = rows($conn, "
    SELECT
        r.region_id,
        r.region_name_ar,
        COUNT(tp.id) AS toxic_count,
        MAX(IFNULL(tp.toxicity_level, 0)) AS max_toxicity,
        AVG(hp.latitude) AS lat,
        AVG(hp.longitude) AS lon
    FROM toxic_plants tp
    LEFT JOIN regions r ON r.region_id = tp.region_id
    LEFT JOIN heatmap_points hp ON hp.region_id = r.region_id
    GROUP BY r.region_id, r.region_name_ar
    HAVING toxic_count > 0 OR max_toxicity >= 2
    ORDER BY max_toxicity DESC, toxic_count DESC
    LIMIT 20
");

foreach ($toxicityRows as $row) {
    $count = (int)$row['toxic_count'];
    $level = (float)$row['max_toxicity'];
    $score = min(100, ($count * 12) + ($level * 18));
    $severity = $score >= 70 ? 'critical' : 'warning';

    add_alert($alerts, [
        'type' => 'toxicity',
        'severity' => $severity,
        'icon' => 'bi-radioactive',
        'title' => 'مؤشر سمية نباتية',
        'region_id' => $row['region_id'],
        'region' => $row['region_name_ar'] ?: 'غير محددة',
        'value' => $count . ' نبات / مستوى ' . $level,
        'score' => round($score),
        'description' => 'وجود نباتات سامة أو مستوى سمية مرتفع يتطلب متابعة خاصة.',
        'recommendation' => 'تحديد مواقع النباتات السامة وربطها بسجلات المنطقة في الخريطة.',
        'lat' => $row['lat'],
        'lon' => $row['lon']
    ]);
}

$rareRows = rows($conn, "
    SELECT
        r.region_id,
        r.region_name_ar,
        COUNT(rp.id) AS rare_count,
        AVG(hp.latitude) AS lat,
        AVG(hp.longitude) AS lon
    FROM rare_plants rp
    LEFT JOIN regions r ON r.region_id = rp.region_id
    LEFT JOIN heatmap_points hp ON hp.region_id = r.region_id
    GROUP BY r.region_id, r.region_name_ar
    HAVING rare_count > 0
    ORDER BY rare_count DESC
    LIMIT 20
");

foreach ($rareRows as $row) {
    $count = (int)$row['rare_count'];
    $score = min(100, 35 + ($count * 10));

    add_alert($alerts, [
        'type' => 'rare',
        'severity' => $count >= 5 ? 'warning' : 'info',
        'icon' => 'bi-star-fill',
        'title' => 'منطقة تحتوي نباتات نادرة',
        'region_id' => $row['region_id'],
        'region' => $row['region_name_ar'] ?: 'غير محددة',
        'value' => $count . ' نبات نادر',
        'score' => round($score),
        'description' => 'وجود نباتات نادرة ليس خطراً مباشراً، لكنه مؤشر مهم للحماية والمراقبة.',
        'recommendation' => 'توثيق المنطقة كأولوية حماية ومتابعة ظهور النباتات النادرة عبر السنوات.',
        'lat' => $row['lat'],
        'lon' => $row['lon']
    ]);
}

usort($alerts, function ($a, $b) {
    $rank = ['critical' => 4, 'warning' => 3, 'info' => 2, 'success' => 1];
    if (($rank[$a['severity']] ?? 0) === ($rank[$b['severity']] ?? 0)) {
        return $b['score'] <=> $a['score'];
    }
    return ($rank[$b['severity']] ?? 0) <=> ($rank[$a['severity']] ?? 0);
});

$filteredAlerts = array_values(array_filter($alerts, function ($alert) use ($filter) {
    if ($filter === 'all') {
        return true;
    }
    if ($filter === 'critical') {
        return $alert['severity'] === 'critical';
    }
    if ($filter === 'warning') {
        return $alert['severity'] === 'warning';
    }
    return $alert['type'] === $filter;
}));

$totalAlerts = count($alerts);
$criticalAlerts = count(array_filter($alerts, fn($a) => $a['severity'] === 'critical'));
$warningAlerts = count(array_filter($alerts, fn($a) => $a['severity'] === 'warning'));
$infoAlerts = count(array_filter($alerts, fn($a) => $a['severity'] === 'info'));


include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';
?>

<style>
.alert-map-card {
    display: block;
    width: 100%;
    height: 100%;
    text-align: start;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
}
.alert-map-card:hover {
    transform: translateY(-3px);
}
.open-map-hint {
    margin-top: 12px;
    padding: 10px 12px;
    border-radius: 14px;
    background: #ecfdf5;
    color: #14532d;
    font-weight: 900;
    font-size: 13px;
}
</style>

<div class="alerts-hero">
    <div>
        <h2><i class="bi bi-bell-fill"></i> تنبيهات الغطاء النباتي</h2>    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="summary-card"><div class="num"><?= $totalAlerts ?></div><div class="txt">إجمالي التنبيهات</div></div></div>
    <div class="col-md-3"><div class="summary-card"><div class="num text-danger"><?= $criticalAlerts ?></div><div class="txt">تنبيهات حرجة</div></div></div>
    <div class="col-md-3"><div class="summary-card"><div class="num text-warning"><?= $warningAlerts ?></div><div class="txt">تحتاج متابعة</div></div></div>
    <div class="col-md-3"><div class="summary-card"><div class="num text-primary"><?= $infoAlerts ?></div><div class="txt">إنذار مبكر</div></div></div>
</div>

<div class="filter-bar filters-row mt-3">
    <a class="filter-chip <?= $filter === 'all' ? 'active' : '' ?>" href="admin_alerts.php?filter=all">الكل</a>
    <a class="filter-chip danger <?= $filter === 'critical' ? 'active' : '' ?>" href="admin_alerts.php?filter=critical">مرتفع</a>
    <a class="filter-chip warning <?= $filter === 'warning' ? 'active' : '' ?>" href="admin_alerts.php?filter=warning">متوسط</a>
    <a class="filter-chip <?= $filter === 'disease' ? 'active' : '' ?>" href="admin_alerts.php?filter=disease">الأوبئة</a>
    <a class="filter-chip <?= $filter === 'toxicity' ? 'active' : '' ?>" href="admin_alerts.php?filter=toxicity">السمية</a>
    <a class="filter-chip <?= $filter === 'density' ? 'active' : '' ?>" href="admin_alerts.php?filter=density">الكثافة</a>
    <a class="filter-chip <?= $filter === 'rare' ? 'active' : '' ?>" href="admin_alerts.php?filter=rare">الندرة</a>
</div>

<?php if (empty($filteredAlerts)): ?>
    <div class="empty-state">
        <h4 class="fw-bold text-success"><i class="bi bi-shield-check"></i> لا توجد تنبيهات ضمن هذا الفلتر</h4>
        <p class="text-muted mb-0">غيّري نوع الفلتر أو حدّثي بيانات قاعدة البيانات.</p>
    </div>
<?php else: ?>
    <div class="row g-4 alerts-list">
        <?php foreach ($filteredAlerts as $index => $alert): ?>
            <?php
            $badgeClass = $alert['severity'] === 'critical' ? 'badge-critical' : ($alert['severity'] === 'warning' ? 'badge-warning' : 'badge-info');
            $levelText = $alert['severity'] === 'critical' ? 'مرتفع' : ($alert['severity'] === 'warning' ? 'متوسط' : 'إنذار مبكر');
            $hasLocation = $alert['lat'] !== null && $alert['lon'] !== null;
            ?>
            <?php
                $mapParams = [
                    'focus_region_id' => (int)$alert['region_id'],
                    'focus_region' => $alert['region'],
                    'focus_title' => $alert['title'],
                ];

                if ($hasLocation) {
                    $mapParams['focus_lat'] = $alert['lat'];
                    $mapParams['focus_lon'] = $alert['lon'];
                }

                $mapUrl = 'vegetation.php?' . http_build_query($mapParams) . '#map';
            ?>
            <div class="col-md-6 col-xl-4">
                <a
                    class="alert-card alert-map-card <?= h($alert['severity']) ?>"
                    href="<?= h($mapUrl) ?>"
                    title="عرض التنبيه على الخريطة الأساسية"
                >
                    <div class="alert-body">
                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <span class="badge-severity <?= $badgeClass ?>"><?= $levelText ?></span>
                            <span class="text-muted fw-bold">درجة <?= h($alert['score']) ?>%</span>
                        </div>

                        <div class="alert-head">
                            <div class="alert-icon"><i class="bi <?= h($alert['icon']) ?>"></i></div>
                            <div>
                                <h5 class="alert-title"><?= h($alert['title']) ?></h5>
                                <div class="alert-region"><i class="bi bi-geo-alt"></i> <?= h($alert['region']) ?></div>
                            </div>
                        </div>

                        <div class="metric-box">
                            <span>المؤشر المقاس</span>
                            <b><?= h($alert['value']) ?></b>
                        </div>

                        <div class="progress mb-3">
                            <div class="progress-bar <?= h($alert['severity']) ?>" style="width: <?= min(100, max(0, (int)$alert['score'])) ?>%"></div>
                        </div>

                        <p class="alert-message mb-2"><?= h($alert['description']) ?></p>
                        <div class="recommendation">
                            <b><i class="bi bi-check2-circle"></i> ملاحظة متابعة:</b><br>
                            <?= h($alert['recommendation']) ?>
                        </div>

                        <div class="open-map-hint"><i class="bi bi-map"></i> اضغطي لعرض المنطقة على الخريطة الأساسية</div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script src="assets/js/admin-table-tools.js?v=20260426-final"></script>
</div>
</div>
</div>
</body>
</html>
