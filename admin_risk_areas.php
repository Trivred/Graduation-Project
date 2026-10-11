<?php
require_once 'includes/db.php';

if (!function_exists('riskAreaH')) {
    function riskAreaH($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('riskAreaClamp')) {
    function riskAreaClamp(float $value): float
    {
        if ($value < 0) {
            return 0;
        }

        if ($value > 100) {
            return 100;
        }

        return $value;
    }
}

if (!function_exists('riskAreaLevel')) {
    function riskAreaLevel(int $risk): array
    {
        if ($risk >= 75) {
            return [
                'label' => 'مرتفع',
                'color' => '#7f1d1d',
                'icon'  => 'fa-circle-exclamation'
            ];
        }

        if ($risk >= 55) {
            return [
                'label' => 'مرتفع',
                'color' => '#dc2626',
                'icon'  => 'fa-triangle-exclamation'
            ];
        }

        return [
            'label' => 'متوسط',
            'color' => '#f59e0b',
            'icon'  => 'fa-circle-info'
        ];
    }
}

if (!function_exists('riskAreaReasons')) {
    function riskAreaReasons(float $density, float $toxicity, float $rarity, float $diseaseSpread): array
    {
        $reasons = [];

        if ($density < 25) {
            $reasons[] = 'الكثافة النباتية منخفضة جدًا';
        } elseif ($density < 45) {
            $reasons[] = 'الكثافة النباتية متوسطة وتحتاج متابعة';
        }

        if ($toxicity >= 70) {
            $reasons[] = 'ارتفاع واضح في مؤشر السمية';
        } elseif ($toxicity >= 40) {
            $reasons[] = 'مؤشر السمية متوسط ويحتاج مراقبة';
        }

        if ($rarity >= 70) {
            $reasons[] = 'وجود حساسية بيئية عالية بسبب النباتات النادرة';
        } elseif ($rarity >= 40) {
            $reasons[] = 'وجود نباتات نادرة تحتاج حماية';
        }

        if ($diseaseSpread >= 70) {
            $reasons[] = 'انتشار مرتفع للأوبئة النباتية';
        } elseif ($diseaseSpread >= 40) {
            $reasons[] = 'انتشار متوسط للأوبئة النباتية';
        }

        if (!$reasons) {
            $reasons[] = 'مؤشرات متابعة متوسطة تحتاج مراقبة دورية';
        }

        return $reasons;
    }
}

if (!function_exists('riskAreaRecommendations')) {
    function riskAreaRecommendations(float $density, float $toxicity, float $rarity, float $diseaseSpread, int $risk): array
    {
        $recommendations = [];

        if ($density < 25) {
            $recommendations[] = 'تنفيذ برنامج تشجير عاجل لرفع مستوى الغطاء النباتي.';
        } elseif ($density < 45) {
            $recommendations[] = 'زيادة حملات التشجير والمتابعة الموسمية للكثافة النباتية.';
        }

        if ($toxicity >= 70) {
            $recommendations[] = 'إجراء مسح ميداني للنباتات السامة ووضع خطة إزالة أو عزل آمنة.';
        } elseif ($toxicity >= 40) {
            $recommendations[] = 'مراقبة النباتات السامة وتحديث بياناتها بشكل دوري.';
        }

        if ($rarity >= 70) {
            $recommendations[] = 'حماية مواقع النباتات النادرة ومنع العبث بها.';
        } elseif ($rarity >= 40) {
            $recommendations[] = 'توثيق مواقع النباتات النادرة وربطها بخطة حماية بيئية.';
        }

        if ($diseaseSpread >= 60) {
            $recommendations[] = 'تنفيذ خطة متابعة للأوبئة النباتية وتقليل انتشارها.';
        }

        if (!$recommendations) {
            $recommendations[] = 'متابعة المنطقة دورياً ومراجعة مؤشرات الكثافة والأوبئة في التحديث القادم.';
        }

        return $recommendations;
    }
}

$latestYear = 0;
$yearResult = $conn->query("SELECT MAX(year) AS latest_year FROM region_year_metrics");

if ($yearResult) {
    $yearRow = $yearResult->fetch_assoc();
    $latestYear = (int)($yearRow['latest_year'] ?? 0);
}

$areas = [];
$medium = 0;
$high = 0;

if ($latestYear > 0) {
    $sql = "
        SELECT
            r.region_id,
            r.region_name_ar,
            rym.year,
            ROUND(rym.ndvi_mean, 4) AS ndvi_mean,
            ROUND(rym.density_score, 2) AS density_score,
            ROUND(rym.toxicity_score, 2) AS toxicity_score,
            ROUND(rym.rarity_score, 2) AS rarity_score,
            COALESCE(tox.toxic_count, 0) AS toxic_count,
            COALESCE(tox.avg_toxicity_level, 0) AS avg_toxicity_level,
            COALESCE(tox.toxic_plants, '') AS toxic_plants,
            COALESCE(rare.rare_count, 0) AS rare_count,
            COALESCE(rare.high_rare_count, 0) AS high_rare_count,
            COALESCE(rare.rare_plants, '') AS rare_plants,
            COALESCE(dis.avg_spread, 0) AS avg_disease_spread
        FROM regions r
        INNER JOIN region_year_metrics rym
            ON rym.region_id = r.region_id
            AND rym.year = ?
        LEFT JOIN (
            SELECT
                region_id,
                COUNT(*) AS toxic_count,
                ROUND(AVG(COALESCE(toxicity_level, 0)), 2) AS avg_toxicity_level,
                GROUP_CONCAT(DISTINCT plant_name ORDER BY plant_name SEPARATOR '، ') AS toxic_plants
            FROM toxic_plants
            GROUP BY region_id
        ) tox ON tox.region_id = r.region_id
        LEFT JOIN (
            SELECT
                region_id,
                COUNT(*) AS rare_count,
                SUM(
                    CASE
                        WHEN rarity_level = 'عالية'
                             OR conservation_status IN ('مهدد', 'نادر', 'محمي')
                        THEN 1
                        ELSE 0
                    END
                ) AS high_rare_count,
                GROUP_CONCAT(DISTINCT plant_name ORDER BY plant_name SEPARATOR '، ') AS rare_plants
            FROM rare_plants
            GROUP BY region_id
        ) rare ON rare.region_id = r.region_id
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
        $stmt->bind_param('i', $latestYear);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $density = riskAreaClamp((float)$row['density_score']);
            $toxicity = riskAreaClamp((float)$row['toxicity_score']);
            $rarity = riskAreaClamp((float)$row['rarity_score']);
            $diseaseSpread = riskAreaClamp((float)$row['avg_disease_spread']);

            $densityRisk = 100 - $density;

            $risk = (int)round(
                ($densityRisk * 0.50) +
                ($toxicity * 0.25) +
                ($rarity * 0.15) +
                ($diseaseSpread * 0.10)
            );

            $risk = (int)riskAreaClamp((float)$risk);

            if ($risk < 30) {
                continue;
            }

            $levelData = riskAreaLevel($risk);
            $level = $levelData['label'];
            if ($level === 'مرتفع') {
                $high++;
            } else {
                $medium++;
            }

            $row['density'] = $density;
            $row['toxicity'] = $toxicity;
            $row['rarity'] = $rarity;
            $row['disease_spread'] = $diseaseSpread;
            $row['risk'] = $risk;
            $row['level'] = $level;
            $row['color'] = $levelData['color'];
            $row['icon'] = $levelData['icon'];
            $row['reasons'] = riskAreaReasons($density, $toxicity, $rarity, $diseaseSpread);
            $row['recommendations'] = riskAreaRecommendations($density, $toxicity, $rarity, $diseaseSpread, $risk);

            $areas[] = $row;
        }

        $stmt->close();
    }
}

$totalAreas = count($areas);
$highOrCritical = $high;

include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/admin-risk-areas.css?v=20260505-filter-force">

<div class="content-wrapper admin-structured-page admin-risk-areas-page">
    <div class="page-hero">
        <div>
            <h3>
                <i class="fa-solid fa-triangle-exclamation"></i>
                تحليل المناطق الخطرة
            </h3>
            <p>
               
                 للسنة الحالية: <?= (int)$latestYear ?>
            </p>
        </div>

        <div class="hero-actions">
            <button type="button" class="btn-soft btn-export" data-export-cards="risk_areas.csv">
                <i class="fa-solid fa-download"></i>
                تصدير الصفحة
            </button>

            <a href="admin_reports.php" class="btn-soft btn-report">
                <i class="fa-solid fa-file-lines"></i>
                التقارير الكاملة
            </a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <span>إجمالي المناطق</span>
            <b><?= (int)$totalAreas ?></b>
        </div>

        <div class="stat-card">
            <span>المناطق المعروضة بعد الفلترة</span>
            <b id="visibleAreasCount"><?= (int)$totalAreas ?></b>
        </div>

        <div class="stat-card">
            <span>متوسطة الخطورة</span>
            <b><?= (int)$medium ?></b>
        </div>

        <div class="stat-card">
            <span>مرتفعة الخطورة</span>
            <b><?= (int)$highOrCritical ?></b>
        </div>

        <div class="stat-card">
            <span>السنة المعتمدة</span>
            <b><?= (int)$latestYear ?></b>
        </div>
    </div>

    <div class="filter-bar" role="search" aria-label="فلترة المناطق الخطرة">
        <input
            id="riskSearchInput"
            class="search-box"
            placeholder="🔍 ابحث عن منطقة أو سبب أو توصية..."
            autocomplete="off"
        >

        <button type="button" class="filter-btn active" data-filter-risk="all" data-risk-filter="all">الكل</button>
        <button type="button" class="filter-btn" data-filter-risk="high" data-risk-filter="high">مرتفع</button>
        <button type="button" class="filter-btn" data-filter-risk="medium" data-risk-filter="medium">متوسط</button>
    </div>

    <div class="filter-result-note" id="riskFilterResult">
        يتم عرض <?= (int)$totalAreas ?> منطقة. استخدمي البحث أو اختاري متوسط / مرتفع فقط.
    </div>

    <div class="widgets-grid" id="cardsGrid">
        <?php if (!$areas): ?>
            <div class="empty">
                لا توجد بيانات كافية في جدول region_year_metrics لعرض تحليل المناطق الخطرة.
            </div>
        <?php endif; ?>

        <?php foreach ($areas as $row): ?>
            <?php
                $detailsId = 'risk_details_' . (int)$row['region_id'];
                $riskGroup = $row['level'] === 'مرتفع' ? 'high' : 'medium';

                $searchText = trim(
                    $row['region_name_ar'] . ' ' .
                    $row['level'] . ' ' .
                    implode(' ', $row['reasons']) . ' ' .
                    implode(' ', $row['recommendations']) . ' ' .
                    $row['toxic_plants'] . ' ' .
                    $row['rare_plants']
                );

                $exportText =
                    $row['region_name_ar'] . '|' .
                    $row['year'] . '|' .
                    $row['level'] . '|' .
                    $row['risk'] . '|' .
                    number_format((float)$row['density'], 2) . '|' .
                    number_format((float)$row['toxicity'], 2) . '|' .
                    number_format((float)$row['rarity'], 2) . '|' .
                    number_format((float)$row['disease_spread'], 2);
            ?>

            <article
                class="widget-box"
                data-risk-card="1"
                data-level="<?= riskAreaH($row['level']) ?>"
                data-risk-group="<?= riskAreaH($riskGroup) ?>"
                data-search="<?= riskAreaH($searchText) ?>"
                data-export="<?= riskAreaH($exportText) ?>"
                style="--riskColor: <?= riskAreaH($row['color']) ?>; --riskPercent: <?= (int)$row['risk'] ?>%;"
            >
                <div class="widget-title">
                    <i class="fa-solid <?= riskAreaH($row['icon']) ?>"></i>
                    <?= riskAreaH($row['region_name_ar']) ?>
                </div>

                <span class="badge-risk">
                    <?= riskAreaH($row['level']) ?> - <?= (int)$row['risk'] ?>%
                </span>

                <div class="risk-progress" aria-hidden="true">
                    <span></span>
                </div>

                <div class="stats-row">
                    <div class="stat-mini">
                        الكثافة
                        <span><?= number_format((float)$row['density'], 1) ?></span>
                    </div>

                    <div class="stat-mini">
                        السمية
                        <span><?= number_format((float)$row['toxicity'], 1) ?></span>
                    </div>

                    <div class="stat-mini">
                        الندرة
                        <span><?= number_format((float)$row['rarity'], 1) ?></span>
                    </div>

                    <div class="stat-mini">
                        الأوبئة
                        <span><?= number_format((float)$row['disease_spread'], 1) ?></span>
                    </div>
                </div>

                <button
                    class="details-btn"
                    type="button"
                    data-details-target="<?= riskAreaH($detailsId) ?>"
                    aria-controls="<?= riskAreaH($detailsId) ?>"
                    aria-expanded="false"
                >
                    تفاصيل المنطقة
                </button>

                <div id="<?= riskAreaH($detailsId) ?>" class="details-box" hidden>
                    <div class="details-grid">
                        <div class="detail-card">
                            <b>السنة المعتمدة:</b><br>
                            <?= (int)$row['year'] ?>
                        </div>

                        <div class="detail-card">
                            <b>NDVI:</b><br>
                            <?= number_format((float)$row['ndvi_mean'], 4) ?>
                        </div>

                        <div class="detail-card">
                            <b>نسبة الخطر:</b><br>
                            <?= (int)$row['risk'] ?>%
                        </div>

                        <div class="detail-card">
                            <b>حالة الكثافة:</b><br>
                            <?php if ($row['density'] < 25): ?>
                                منخفضة جدًا
                            <?php elseif ($row['density'] < 45): ?>
                                متوسطة وتحتاج متابعة
                            <?php elseif ($row['density'] < 70): ?>
                                جيدة
                            <?php else: ?>
                                مرتفعة ومستقرة
                            <?php endif; ?>
                        </div>

                        <div class="detail-card detail-card-wide">
                            <b>أسباب التصنيف:</b><br>
                            <?= riskAreaH(implode(' | ', $row['reasons'])) ?>
                        </div>

                        <div class="detail-card">
                            <b>النباتات السامة المسجلة:</b><br>
                            العدد: <?= (int)$row['toxic_count'] ?><br>
                            متوسط السمية: <?= number_format((float)$row['avg_toxicity_level'], 1) ?><br>
                            <?= riskAreaH($row['toxic_plants'] ?: 'لا يوجد') ?>
                        </div>

                        <div class="detail-card">
                            <b>النباتات النادرة المسجلة:</b><br>
                            العدد: <?= (int)$row['rare_count'] ?><br>
                            عالية الحساسية: <?= (int)$row['high_rare_count'] ?><br>
                            <?= riskAreaH($row['rare_plants'] ?: 'لا يوجد') ?>
                        </div>

                        <div class="detail-card solution detail-card-wide">
                            <b>التوصيات:</b>
                            <ul>
                                <?php foreach ($row['recommendations'] as $recommendation): ?>
                                    <li><?= riskAreaH($recommendation) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <div class="empty empty-filter" id="riskNoResults" hidden>
            لا توجد مناطق مطابقة للبحث أو الفلتر الحالي.
        </div>
    </div>
</div>

<script src="assets/js/admin-risk-areas.js?v=20260505-filter-force"></script>
<script src="assets/js/admin-table-tools.js?v=20260426-final"></script>

</body>
</html>