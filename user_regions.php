<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/includes/db.php';

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login_register.php');
    exit;
}

$sql = "
    SELECT
        r.region_id,
        TRIM(REPLACE(REPLACE(r.region_name_ar, CHAR(13), ''), CHAR(10), '')) AS region_name,
        COALESCE(r.region_type, 'غير محدد') AS region_type,
        CASE
            WHEN COALESCE(stats.total_plants, 0) >= 10000 THEN 'مستقرة'
            ELSE 'غير مستقرة'
        END AS vegetation_status,
        COALESCE(stats.total_plants, 0) AS total_plants,
        COALESCE(ROUND(stats.avg_density, 2), 0) AS density_level
    FROM regions r
    LEFT JOIN (
        SELECT
            region_id,
            SUM(COALESCE(plant_count, 0)) AS total_plants,
            AVG(COALESCE(density_per_km2, 0)) AS avg_density
        FROM region_plant_stats
        GROUP BY region_id
    ) stats ON stats.region_id = r.region_id
    ORDER BY region_name ASC
";

$result = $conn->query($sql);
$rows = [];
$queryError = '';

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
} else {
    $queryError = $conn->error;
}

$totalRows = count($rows);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المناطق</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/user-shared.css">
</head>
<body>
<?php include __DIR__ . '/includes/user_sidebar.php'; ?>

<main class="main">
    <section class="page-header">
        <div>
            <h1 class="page-title"><i class="bi bi-geo-alt"></i> عرض المناطق</h1>
        </div>

        <div class="actions">
            <a class="btn btn-soft" href="export_regions.php">
                <i class="bi bi-download"></i>
                تصدير المناطق
            </a>

            <span class="btn btn-soft">
                <i class="bi bi-list-check"></i>
                المعروض:
                <strong id="visibleCount"><?= $totalRows ?></strong>
            </span>
        </div>
    </section>

    <section class="toolbar">
        <div class="control-card">
            <label for="regionSearch">بحث في المناطق</label>
            <input id="regionSearch" type="search" placeholder="اكتب اسم المنطقة أو نوعها أو حالتها...">
        </div>

        <div class="control-card">
            <label for="statusFilter">حالة الغطاء النباتي</label>
            <select id="statusFilter">
                <option value="">كل الحالات</option>
                <option value="مستقرة">مستقرة</option>
                <option value="غير مستقرة">غير مستقرة</option>
            </select>
        </div>

        <div class="actions">
            <button class="btn btn-soft" type="button" data-reset-regions>
                <i class="bi bi-arrow-counterclockwise"></i>
                إعادة ضبط
            </button>
        </div>
    </section>

    <?php if ($queryError !== ''): ?>
        <section class="table-box">
            <div class="empty-row" style="padding: 24px;">
                تعذر تحميل بيانات المناطق. سبب الخطأ:
                <?= e($queryError) ?>
            </div>
        </section>
    <?php else: ?>
        <section class="table-box">
            <table id="regionsTable" data-sortable-table>
                <thead>
                    <tr>
                        <th data-index="0" data-type="number"><span class="sort-icons">▲▼</span>#</th>
                        <th data-index="1"><span class="sort-icons">▲▼</span>اسم المنطقة</th>
                        <th data-index="2"><span class="sort-icons">▲▼</span>نوع المنطقة</th>
                        <th data-index="3" data-type="number"><span class="sort-icons">▲▼</span>الكثافة</th>
                        <th data-index="4" data-type="number"><span class="sort-icons">▲▼</span>عدد النباتات</th>
                        <th data-index="5"><span class="sort-icons">▲▼</span>الحالة</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($totalRows > 0): ?>
                        <?php $i = 1; foreach ($rows as $row): ?>
                            <?php
                                $totalPlants = (int) ($row['total_plants'] ?? 0);
                                // Logical status rule requested for the user regions page:
                                // less than 10,000 plants = unstable, 10,000 or more = stable.
                                $status = $totalPlants >= 10000 ? 'مستقرة' : 'غير مستقرة';
                                $badgeClass = $status === 'مستقرة' ? 'healthy' : 'warning';
                                $density = (float) ($row['density_level'] ?? 0);
                            ?>

                            <tr data-status="<?= e($status) ?>">
                                <td><?= $i++ ?></td>
                                <td><?= e($row['region_name'] ?? '') ?></td>
                                <td><?= e($row['region_type'] ?? 'غير محدد') ?></td>
                                <td><?= number_format($density, 2) ?></td>
                                <td><?= number_format($totalPlants) ?></td>
                                <td>
                                    <span class="badge <?= e($badgeClass) ?>">
                                        <?= e($status) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-row">
                            <td colspan="6">لا توجد بيانات مناطق حالياً.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</main>

<script src="assets/js/app-core.js"></script>
<script src="assets/js/sidebar.js"></script>
<script src="assets/js/user-regions.js"></script>
</body>
</html>
