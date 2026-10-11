<?php
require_once __DIR__ . '/includes/db.php';

if (!isset($conn) || !$conn) {
    die("فشل الاتصال بقاعدة البيانات. تأكد من ملف includes/db.php");
}

$conn->set_charset("utf8mb4");

$currentYear = (int)date('Y');
$minYear = 1990;
$maxYear = $currentYear;

$yearRes = $conn->query("
    SELECT 
        MIN(year) AS min_year,
        MAX(year) AS max_year
    FROM region_year_metrics
");

if ($yearRes && $yearRow = $yearRes->fetch_assoc()) {
    if (!empty($yearRow['min_year'])) {
        $minYear = (int)$yearRow['min_year'];
    }

    if (!empty($yearRow['max_year'])) {
        $maxYear = max((int)$yearRow['max_year'], $currentYear);
    }
}

$defaultYear = $maxYear;

$disease_groups = [];

$res = $conn->query("
    SELECT disease_id, name, disease_type
    FROM plant_diseases
    ORDER BY disease_type, name
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $type = trim($row['disease_type'] ?? 'غير مصنف');

        if ($type === '') {
            $type = 'غير مصنف';
        }

        if (!isset($disease_groups[$type])) {
            $disease_groups[$type] = [];
        }

        $disease_groups[$type][] = [
            'disease_id' => $row['disease_id'],
            'name' => $row['name'],
            'disease_type' => $type
        ];
    }
}

$focusRegionId = isset($_GET['focus_region_id']) ? (int)$_GET['focus_region_id'] : 0;
$focusLat = isset($_GET['focus_lat']) && is_numeric($_GET['focus_lat']) ? (float)$_GET['focus_lat'] : null;
$focusLon = isset($_GET['focus_lon']) && is_numeric($_GET['focus_lon']) ? (float)$_GET['focus_lon'] : null;
$focusRegion = trim((string)($_GET['focus_region'] ?? ''));
$focusTitle = trim((string)($_GET['focus_title'] ?? ''));
$focusEnabled = isset($_GET['focus_region_id']) || isset($_GET['focus_lat']) || isset($_GET['focus_lon']) || isset($_GET['focus_region']) || isset($_GET['focus_title']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>خريطة الغطاء النباتي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">

    <link rel="stylesheet" href="assets/css/vegetation-map.css?v=20260510-focus-eastern-fix">
</head>

<body>

<div class="sidebar">

    <div class="title"> لوحة التحكم</div>

    <div class="logo-box">
        <img src="assets/img/logo.png" class="logo-img" onerror="this.style.display='none';">
    </div>

    <div class="top-actions">
        <button class="refresh-btn" onclick="refreshMapData()" id="refreshMapBtn"> تحديث الخريطة</button>
        <button class="map-toggle" onclick="toggleDarkMode()" id="mapModeBtn">🌙 Dark Map</button>
    </div>


    <details class="filter-card dropdown-card" open>
        <summary>📍 المنطقة</summary>
        <div class="dropdown-body">
            <input id="regionSearch" placeholder="ابحث عن منطقة...">
            <select id="regionFilter">
                <option value="">كل المناطق</option>
            </select>
        </div>
    </details>


    <details class="filter-card dropdown-card" open>
        <summary> الخط الزمني من <?php echo $minYear; ?> إلى <?php echo $maxYear; ?></summary>
        <div class="dropdown-body">

            <div id="yearValue" class="year-display"><?php echo $defaultYear; ?></div>

            <input
                type="range"
                id="yearSlider"
                class="timeline-slider"
                dir="rtl"
                min="<?php echo $minYear; ?>"
                max="<?php echo $maxYear; ?>"
                value="<?php echo $defaultYear; ?>"
                step="1">

            <div class="timeline-meta">
                <span><?php echo $minYear; ?></span>
                <span><?php echo $maxYear; ?></span>
            </div>
           <div class="layer-switches">
                <label class="switch-card"><input type="checkbox" id="toggleDensity" checked> كثافة</label>
                <label class="switch-card"><input type="checkbox" id="toggleRarity" checked> ندرة</label>
                <label class="switch-card"><input type="checkbox" id="toggleToxicity" checked> سمية</label>
            </div>

            <button class="play-btn" id="playBtn" onclick="toggleYearAnimation()">▶ تشغيل السنوات</button>

            <div class="legend-box">
                <div class="legend-row">
                    <div class="legend-left">
                        <span class="legend-color legend-low"></span>
                        <span>كثافة منخفضة</span>
                    </div>
                    <span>0 - 29</span>
                </div>

                <div class="legend-row">
                    <div class="legend-left">
                        <span class="legend-color legend-medium"></span>
                        <span>كثافة متوسطة</span>
                    </div>
                    <span>30 - 59</span>
                </div>

                <div class="legend-row">
                    <div class="legend-left">
                        <span class="legend-color legend-high"></span>
                        <span>كثافة مرتفعة</span>
                    </div>
                    <span>60 - 79</span>
                </div>

                <div class="legend-row">
                    <div class="legend-left">
                        <span class="legend-color legend-very-high"></span>
                        <span>كثافة مرتفعة جدًا</span>
                    </div>
                    <span>80 - 100</span>
                </div>

                <div class="legend-row">
                    <div class="legend-left">
                        <span class="legend-color legend-rarity"></span>
                        <span>ندرة النباتات</span>
                    </div>
                    <span>أزرق</span>
                </div>

                <div class="legend-row">
                    <div class="legend-left">
                        <span class="legend-color legend-toxicity"></span>
                        <span>سمية النباتات</span>
                    </div>
                    <span>أصفر / أحمر</span>
                </div>
            </div>

            <div class="year-stats-box" id="yearStatsBox">
                <div class="stats-row"><span>عدد المناطق</span><b>0</b></div>
                <div class="stats-row"><span>نقاط الكثافة</span><b>0</b></div>
                <div class="stats-row"><span>نقاط الندرة</span><b>0</b></div>
                <div class="stats-row"><span>نقاط السمية</span><b>0</b></div>
                <div class="stats-row"><span>متوسط الكثافة</span><b>0</b></div>
                <div class="stats-row"><span>أعلى كثافة</span><b>0</b></div>
                <div class="stats-row"><span>أعلى ندرة</span><b>0</b></div>
                <div class="stats-row"><span>أعلى سمية</span><b>0</b></div>
            </div>

            <div class="loading-note" id="loadingNote">جاري تحديث الخريطة...</div>


        </div>
    </details>


    <details class="filter-card dropdown-card">
        <summary>🦠 الأوبئة</summary>
        <div class="dropdown-body">

            <div class="icon-filter-grid" id="mainDiseaseFilter">
                <div class="icon-filter" data-type="فيروسي">🦠<span>فيروسي</span></div>
                <div class="icon-filter" data-type="فطري">🍄<span>فطري</span></div>
                <div class="icon-filter" data-type="فسيولوجي">🌱<span>فسيولوجي</span></div>
                <div class="icon-filter" data-type="حشري">🐛<span>حشري</span></div>
                <div class="icon-filter" data-type="بكتيري">🧫<span>بكتيري</span></div>
                <div class="icon-filter" data-type="آفة ديدانية">🪱<span>ديدانية</span></div>
                <div class="icon-filter" data-type="آفة عناكبية">🕷️<span>عناكبية</span></div>
                <div class="icon-filter active" data-type="all">🌍<span>الكل</span></div>
            </div>

            <select id="subDiseaseFilter">
                <option value="">اختر المرض الدقيق</option>
            </select>
        </div>
    </details>

    <div class="filter-card">
        <div class="filter-actions">
            <button onclick="applyFilters()">تطبيق الفلاتر</button>
            <button class="reset-btn" onclick="resetFilters()">إعادة ضبط</button>
        </div>
    </div>

    <div class="analysis" id="analysisBox">
        <div class="analysis-header">تحليل المنطقة</div>
        <div class="analysis-empty">
            اضغط على أي علامة في الخريطة لعرض بيانات المنطقة وتحليلها.
        </div>
    </div>

</div>

<div class="map-container">
    <div class="map-year-badge" id="mapYearBadge">السنة: <?php echo $defaultYear; ?></div>
    
    <div id="map"></div>
</div>

<script>
    window.VEGETATION_CONFIG = {
        yearMin: <?php echo (int)$minYear; ?>,
        yearMax: <?php echo (int)$maxYear; ?>,
        defaultYear: <?php echo (int)$defaultYear; ?>,
        focus: <?php echo json_encode([
            'region_id' => $focusRegionId,
            'lat' => $focusLat,
            'lon' => $focusLon,
            'region' => $focusRegion,
            'enabled' => $focusEnabled,
            'title' => $focusTitle,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: "{}"; ?>,
        diseaseGroups: <?php echo json_encode($disease_groups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: "{}"; ?>
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script src="assets/js/vegetation-map.js?v=20260510-focus-eastern-fix"></script>

</body>
</html>