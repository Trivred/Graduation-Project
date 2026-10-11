<?php
session_start();

require_once 'includes/db.php';

$pageTitle = 'لوحة المخاطر التفاعلية';
$adminTitle = 'لوحة المخاطر التفاعلية';
$pageBodyClass = 'admin-risk-dashboard-page';

function adminRiskE($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function adminRiskNumber($value, int $decimals = 1): string
{
    return number_format((float)$value, $decimals);
}

function adminRiskClamp(float $value): float
{
    if ($value < 0) {
        return 0;
    }

    if ($value > 100) {
        return 100;
    }

    return $value;
}

function adminRiskLevel(float $score): array
{
    if ($score >= 60) {
        return [
            'label' => 'خطر مرتفع',
            'class' => 'text-bg-danger',
            'icon'  => 'ri-error-warning-line'
        ];
    }

    if ($score >= 35) {
        return [
            'label' => 'متوسط',
            'class' => 'text-bg-warning text-dark',
            'icon'  => 'ri-alert-line'
        ];
    }

    return [
        'label' => 'منخفض',
        'class' => 'text-bg-success',
        'icon'  => 'ri-shield-check-line'
    ];
}

function adminRiskFetchSingle(mysqli $conn, string $sql, string $column, $fallback = 0)
{
    $result = $conn->query($sql);

    if (!$result) {
        return $fallback;
    }

    $row = $result->fetch_assoc();

    return $row[$column] ?? $fallback;
}

function adminRiskRecommendation(float $riskScore, float $density, float $toxicity, float $rarity, float $disease): string
{
    if ($riskScore >= 60) {
        if ($density < 30) {
            return 'أولوية عالية: انخفاض واضح في الكثافة النباتية، يوصى بتدخل ميداني وتشجير عاجل.';
        }

        if ($toxicity >= 55) {
            return 'أولوية عالية: ارتفاع مؤشر السمية، يوصى بمسح النباتات السامة وتوثيقها.';
        }

        if ($rarity >= 55) {
            return 'أولوية عالية: حساسية بيئية مرتفعة بسبب الندرة، يوصى بخطة حماية.';
        }

        if ($disease >= 55) {
            return 'أولوية عالية: انتشار أمراض نباتية، يوصى بمتابعة ميدانية.';
        }

        return 'أولوية عالية: المنطقة تحتاج مراجعة تفصيلية لمؤشرات الخطر.';
    }

    if ($riskScore >= 35) {
        if ($density < 45) {
            return 'متابعة متوسطة: الكثافة النباتية تحتاج تحسين ومراقبة خلال المواسم القادمة.';
        }

        if ($toxicity >= 35 || $rarity >= 35 || $disease >= 35) {
            return 'متابعة متوسطة: توجد مؤشرات تحتاج مراقبة دورية.';
        }

        return 'متابعة متوسطة: راقب التغيرات السنوية في الكثافة والمؤشرات البيئية.';
    }

    return 'الوضع مستقر: متابعة دورية فقط.';
}

$minYear = (int)adminRiskFetchSingle(
    $conn,
    "SELECT MIN(year) AS min_year FROM region_year_metrics",
    'min_year',
    1990
);

$maxYear = (int)adminRiskFetchSingle(
    $conn,
    "SELECT MAX(year) AS max_year FROM region_year_metrics",
    'max_year',
    (int)date('Y')
);

if ($minYear <= 0) {
    $minYear = 1990;
}

if ($maxYear <= 0) {
    $maxYear = (int)date('Y');
}

$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $maxYear;

if ($selectedYear < $minYear || $selectedYear > $maxYear) {
    $selectedYear = $maxYear;
}

$riskRows = [];
$queryError = '';

$sql = "
    SELECT
        r.region_id,
        r.region_name_ar,
        r.region_type,
        r.vegetation_status,

        rym.year,
        rym.ndvi_mean,
        rym.density_score,
        rym.toxicity_score,
        rym.rarity_score,

        COALESCE(dis.avg_spread, 0) AS disease_score

    FROM regions r

    INNER JOIN region_year_metrics rym
        ON rym.region_id = r.region_id
       AND rym.year = ?

    LEFT JOIN (
        SELECT
            region_id,
            ROUND(AVG(COALESCE(spread_percentage, 0)), 2) AS avg_spread
        FROM disease_spread
        GROUP BY region_id
    ) dis ON dis.region_id = r.region_id

    ORDER BY r.region_name_ar ASC
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param('i', $selectedYear);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $density = adminRiskClamp((float)$row['density_score']);
            $toxicity = adminRiskClamp((float)$row['toxicity_score']);
            $rarity = adminRiskClamp((float)$row['rarity_score']);
            $disease = adminRiskClamp((float)$row['disease_score']);
            $densityRisk = 100 - $density;

            $riskScore = round(
                ($densityRisk * 0.50) +
                ($toxicity * 0.25) +
                ($rarity * 0.15) +
                ($disease * 0.10),
                1
            );

            $riskScore = adminRiskClamp($riskScore);
            $level = adminRiskLevel($riskScore);

            $row['density_score'] = $density;
            $row['toxicity_score'] = $toxicity;
            $row['rarity_score'] = $rarity;
            $row['disease_score'] = $disease;
            $row['risk_score'] = $riskScore;
            $row['risk_label'] = $level['label'];
            $row['risk_class'] = $level['class'];
            $row['risk_icon'] = $level['icon'];
            $row['recommendation'] = adminRiskRecommendation($riskScore, $density, $toxicity, $rarity, $disease);

            $riskRows[] = $row;
        }
    } else {
        $queryError = 'تعذر تنفيذ استعلام لوحة المخاطر.';
    }

    $stmt->close();
} else {
    $queryError = 'تعذر تجهيز استعلام لوحة المخاطر.';
}

usort($riskRows, static function ($a, $b) {
    return $b['risk_score'] <=> $a['risk_score'];
});

$totalRegions = count($riskRows);
$criticalCount = 0;
$warningCount = 0;
$stableCount = 0;

$totalRisk = 0;
$totalDensity = 0;
$totalToxicity = 0;
$totalRarity = 0;
$totalDisease = 0;

foreach ($riskRows as $row) {
    $riskScore = (float)$row['risk_score'];

    $totalRisk += $riskScore;
    $totalDensity += (float)$row['density_score'];
    $totalToxicity += (float)$row['toxicity_score'];
    $totalRarity += (float)$row['rarity_score'];
    $totalDisease += (float)$row['disease_score'];

    if ($riskScore >= 60) {
        $criticalCount++;
    } elseif ($riskScore >= 35) {
        $warningCount++;
    } else {
        $stableCount++;
    }
}

$avgRisk = $totalRegions ? round($totalRisk / $totalRegions, 1) : 0;
$avgDensity = $totalRegions ? round($totalDensity / $totalRegions, 1) : 0;
$avgToxicity = $totalRegions ? round($totalToxicity / $totalRegions, 1) : 0;
$avgRarity = $totalRegions ? round($totalRarity / $totalRegions, 1) : 0;
$avgDisease = $totalRegions ? round($totalDisease / $totalRegions, 1) : 0;

$topRisk = $riskRows[0] ?? null;
$topRiskName = $topRisk['region_name_ar'] ?? '-';
$topRiskScore = $topRisk['risk_score'] ?? 0;

$topRows = array_slice($riskRows, 0, 10);

$chartPayload = [
    'labels' => ['خطر مرتفع', 'متوسط', 'منخفض'],
    'values' => [$criticalCount, $warningCount, $stableCount],
];

include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';
?>

<div class="content-wrapper admin-structured-page admin-risk-dashboard-page">

    <section class="page-head">
        <div>
            <h3>
                <i class="ri-pulse-line"></i>
                لوحة المخاطر التفاعلية
            </h3>

        </div>


            <a class="btn-soft" href="admin_reports.php">
                <i class="ri-file-chart-line"></i>
                تصدير تقرير شامل
            </a>
        </div>
    </section>

    <?php if ($queryError !== ''): ?>
        <div class="alert-soft">
            <i class="ri-error-warning-line"></i>
            <?= adminRiskE($queryError) ?>
        </div>
    <?php endif; ?>

    <form class="toolbar" method="get" action="admin_risk_dashboard.php">
        <select class="year-select" name="year" aria-label="اختيار السنة">
            <?php for ($year = $maxYear; $year >= $minYear; $year--): ?>
                <option value="<?= (int)$year ?>" <?= $year === $selectedYear ? 'selected' : '' ?>>
                    سنة <?= (int)$year ?>
                </option>
            <?php endfor; ?>
        </select>

        <button class="btn-soft" type="submit">
            <i class="ri-filter-3-line"></i>
            تطبيق السنة
        </button>

        <a class="btn-soft secondary" href="admin_risk_dashboard.php">
            <i class="ri-refresh-line"></i>
            آخر سنة
        </a>
    </form>

    <div class="stats-grid">
        <div class="stat-card">
            <span>المناطق المحللة</span>
            <b><?= adminRiskNumber($totalRegions, 0) ?></b>
            <small class="text-muted">حسب سنة <?= adminRiskE($selectedYear) ?></small>
        </div>

        <div class="stat-card">
            <span>مناطق الخطر المرتفع</span>
            <b><?= adminRiskNumber($criticalCount, 0) ?></b>
            <small class="text-muted">مؤشر 60% فأكثر</small>
        </div>

        <div class="stat-card">
            <span>مناطق متوسطة الخطورة</span>
            <b><?= adminRiskNumber($warningCount, 0) ?></b>
            <small class="text-muted">من 35% إلى أقل من 60%</small>
        </div>

        <div class="stat-card">
            <span>متوسط مؤشر الخطورة</span>
            <b><?= adminRiskNumber($avgRisk) ?>%</b>
            <small class="text-muted">متوسط كل المناطق</small>
        </div>

        <div class="stat-card">
            <span>أعلى منطقة خطورة</span>
            <b><?= adminRiskE($topRiskName) ?></b>
            <small class="text-muted"><?= adminRiskNumber($topRiskScore) ?>%</small>
        </div>

        <div class="stat-card">
            <span>متوسط الكثافة</span>
            <b><?= adminRiskNumber($avgDensity) ?>%</b>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
                    <div>
                        <h5 class="fw-black mb-1">
                            <i class="ri-donut-chart-line"></i>
                            توزيع مستويات المخاطر
                        </h5>

                    </div>

                    <span class="badge-soft">
                        <i class="ri-calendar-line"></i>
                        <?= adminRiskE($selectedYear) ?>
                    </span>
                </div>

                <canvas id="riskChart" aria-label="توزيع مستويات المخاطر"></canvas>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
                    <div>
                        <h5 class="fw-black mb-1">
                            <i class="ri-bar-chart-grouped-line"></i>
                            أعلى المناطق خطورة
                        </h5>
                    </div>

                    <span class="badge-soft">
                        <i class="ri-list-check"></i>
                        أعلى 10
                    </span>
                </div>

                <?php if (empty($topRows)): ?>
                    <div class="empty-state">
                        لا توجد بيانات مخاطر لهذه السنة.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>المنطقة</th>
                                    <th>الخطورة</th>
                                    <th>الكثافة</th>
                                    <th>السمية</th>
                                    <th>الندرة</th>
                                    <th>الأمراض</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($topRows as $row): ?>
                                    <tr>
                                        <td class="fw-bold">
                                            <?= adminRiskE($row['region_name_ar']) ?>
                                        </td>

                                        <td>
                                            <span class="badge <?= adminRiskE($row['risk_class']) ?>">
                                                <?= adminRiskNumber($row['risk_score']) ?>%
                                            </span>
                                        </td>

                                        <td><?= adminRiskNumber($row['density_score']) ?>%</td>
                                        <td><?= adminRiskNumber($row['toxicity_score']) ?>%</td>
                                        <td><?= adminRiskNumber($row['rarity_score']) ?>%</td>
                                        <td><?= adminRiskNumber($row['disease_score']) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="summary-card h-100">
                <span class="badge-soft mb-2">
                    <i class="ri-plant-line"></i>
                    الكثافة
                </span>

                <h3 class="fw-black mb-1"><?= adminRiskNumber($avgDensity) ?>%</h3>

                <p class="text-muted fw-bold mb-0">
                    كلما انخفضت الكثافة زادت الخطورة البيئية.
                </p>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="summary-card h-100">
                <span class="badge-soft mb-2">
                    <i class="ri-drop-line"></i>
                    السمية
                </span>

                <h3 class="fw-black mb-1"><?= adminRiskNumber($avgToxicity) ?>%</h3>

                <p class="text-muted fw-bold mb-0">
                    ارتفاع السمية يعني أولوية تحقق ميداني.
                </p>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="summary-card h-100">
                <span class="badge-soft mb-2">
                    <i class="ri-seedling-line"></i>
                    الندرة
                </span>

                <h3 class="fw-black mb-1"><?= adminRiskNumber($avgRarity) ?>%</h3>

                <p class="text-muted fw-bold mb-0">
                    مؤشر لحساسية الغطاء النباتي في المنطقة.
                </p>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="summary-card h-100">
                <span class="badge-soft mb-2">
                    <i class="ri-virus-line"></i>
                    الأمراض
                </span>

                <h3 class="fw-black mb-1"><?= adminRiskNumber($avgDisease) ?>%</h3>

                <p class="text-muted fw-bold mb-0">
                    متوسط انتشار الأمراض النباتية المسجلة.
                </p>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="toolbar mb-3">
            <input
                id="searchInput"
                class="search-box"
                type="search"
                placeholder="ابحث باسم المنطقة أو نوعها أو مستوى الخطورة..."
            >

            <button class="btn-soft secondary" type="button" data-reset-filters>
                <i class="ri-filter-off-line"></i>
                إعادة ضبط
            </button>

            <button
                class="btn-soft"
                type="button"
                data-export-table="risk_dashboard_<?= adminRiskE($selectedYear) ?>.csv"
            >
                <i class="ri-download-2-line"></i>
                تصدير CSV
            </button>
        </div>

        <?php if (empty($riskRows)): ?>
            <div class="empty-state">
                لا توجد بيانات للعرض في سنة <?= adminRiskE($selectedYear) ?>.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="dataTable" class="table table-hover align-middle sortable-table">
                    <thead>
                        <tr>
                            <th data-type="number"># <span class="sort-icons">↕</span></th>
                            <th>المنطقة <span class="sort-icons">↕</span></th>
                            <th>النوع <span class="sort-icons">↕</span></th>
                            <th data-type="number">الكثافة <span class="sort-icons">↕</span></th>
                            <th data-type="number">السمية <span class="sort-icons">↕</span></th>
                            <th data-type="number">الندرة <span class="sort-icons">↕</span></th>
                            <th data-type="number">الأمراض <span class="sort-icons">↕</span></th>
                            <th data-type="number">الخطورة <span class="sort-icons">↕</span></th>
                            <th>المستوى <span class="sort-icons">↕</span></th>
                            <th>التوصية <span class="sort-icons">↕</span></th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($riskRows as $index => $row): ?>
                            <tr>
                                <td><?= (int)($index + 1) ?></td>

                                <td class="fw-bold">
                                    <?= adminRiskE($row['region_name_ar']) ?>
                                </td>

                                <td>
                                    <?= adminRiskE($row['region_type'] ?: '-') ?>
                                </td>

                                <td>
                                    <?= adminRiskNumber($row['density_score']) ?>%
                                </td>

                                <td>
                                    <?= adminRiskNumber($row['toxicity_score']) ?>%
                                </td>

                                <td>
                                    <?= adminRiskNumber($row['rarity_score']) ?>%
                                </td>

                                <td>
                                    <?= adminRiskNumber($row['disease_score']) ?>%
                                </td>

                                <td class="fw-black">
                                    <?= adminRiskNumber($row['risk_score']) ?>%
                                </td>

                                <td>
                                    <span class="badge <?= adminRiskE($row['risk_class']) ?>">
                                        <i class="<?= adminRiskE($row['risk_icon']) ?>"></i>
                                        <?= adminRiskE($row['risk_label']) ?>
                                    </span>
                                </td>

                                <td class="cell-long">
                                    <?= adminRiskE($row['recommendation']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<script id="riskDashboardData" type="application/json">
<?= json_encode($chartPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="assets/js/admin-risk-dashboard.js?v=20260429-final-logic" defer></script>
<script src="assets/js/admin-table-tools.js?v=20260426-final" defer></script>

<?php
$conn->close();
?>

</body>
</html>