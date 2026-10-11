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
        pd.disease_id,
        COALESCE(pd.name, 'غير محدد') AS disease_name,
        COALESCE(pd.disease_type, 'غير محدد') AS disease_type,
        COALESCE(pd.severity, 'غير محدد') AS severity,
        COALESCE(pd.description, '') AS description,
        COALESCE(pd.affected_crops, 'غير محدد') AS affected_crops,
        COALESCE(pd.transmission_mode, 'غير محدد') AS transmission_mode,
        COALESCE(pd.preferred_region, 'غير محدد') AS preferred_region,
        COALESCE(pd.plant_count, 0) AS plant_count,
        CASE LOWER(COALESCE(pd.severity, ''))
            WHEN 'high' THEN 3
            WHEN 'medium' THEN 2
            WHEN 'low' THEN 1
            ELSE 0
        END AS severity_level
    FROM plant_diseases pd
    ORDER BY severity_level DESC, disease_name ASC
";

$result = $conn->query($sql);
$allRows = [];
$queryError = '';

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allRows[] = $row;
    }
} else {
    $queryError = $conn->error;
}

$types = [];
foreach ($allRows as $row) {
    $type = trim((string) ($row['disease_type'] ?? ''));
    if ($type !== '' && !in_array($type, $types, true)) {
        $types[] = $type;
    }
}

function severityLabel(string $severity): string
{
    $severity = strtolower(trim($severity));

    return match ($severity) {
        'high' => 'عالية',
        'medium' => 'متوسطة',
        'low' => 'منخفضة',
        default => 'غير محدد',
    };
}

function severityClass(string $severity): string
{
    $severity = strtolower(trim($severity));

    return match ($severity) {
        'high' => 'unhealthy',
        'medium' => 'warning',
        'low' => 'healthy',
        default => 'neutral',
    };
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أمراض النباتات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/user-shared.css">
</head>
<body>
<?php include __DIR__ . '/includes/user_sidebar.php'; ?>

<main class="main">
    <section class="page-header">
        <div>
            <h1 class="page-title"><i class="bi bi-bug"></i> عرض اوبئة النباتات  </h1>
        </div>

        <div class="actions">
            <a class="btn btn-soft" href="export_diseases.php">
                <i class="bi bi-download"></i>
                تصدير الأوبئة
            </a>

            <span class="btn btn-soft">
                <i class="bi bi-filter-circle"></i>
                الفئة:
                <strong id="activeDiseaseType">كل الأنواع</strong>
            </span>

            <span class="btn btn-soft">
                <i class="bi bi-list-check"></i>
                المعروض:
                <strong id="visibleDiseases"><?= count($allRows) ?></strong>
            </span>
        </div>
    </section>

    <section class="toolbar">
        <div class="control-card grow">
            <label for="diseaseSearch">بحث في الأوبئة</label>
            <input id="diseaseSearch" type="search" placeholder="ابحث باسم المرض، النوع، المحصول، أو المنطقة...">
        </div>

        <div class="actions">
            <button class="btn btn-soft" type="button" data-reset-diseases>
                <i class="bi bi-arrow-counterclockwise"></i>
                إعادة ضبط
            </button>
        </div>
    </section>

    <section class="filter-chips" aria-label="أنواع الأمراض">
        <button class="chip active" type="button" data-disease-type="all">كل الأنواع</button>
        <?php foreach ($types as $type): ?>
            <button class="chip" type="button" data-disease-type="<?= e($type) ?>">
                <?= e($type) ?>
            </button>
        <?php endforeach; ?>
    </section>

    <?php if ($queryError !== ''): ?>
        <section class="table-box">
            <div class="empty-row" style="padding: 24px;">
                تعذر تحميل بيانات أمراض النباتات. سبب الخطأ:
                <?= e($queryError) ?>
            </div>
        </section>
    <?php else: ?>
        <section class="table-box">
            <table id="diseasesTable" data-sortable-table>
                <thead>
                    <tr>
                        <th data-index="0" data-type="number"><span class="sort-icons">▲▼</span>#</th>
                        <th data-index="1"><span class="sort-icons">▲▼</span>الافات والأمراض  </th>
                        <th data-index="2"><span class="sort-icons">▲▼</span>النوع </th>
                        <th data-index="3"><span class="sort-icons">▲▼</span>المحاصيل المتأثرة</th>
                        <th data-index="4"><span class="sort-icons">▲▼</span>طريقة الانتقال</th>
                        <th data-index="5"><span class="sort-icons">▲▼</span>المنطقة</th>
                        <th data-index="6"><span class="sort-icons">▲▼</span>الوصف</th>
                        <th data-index="7" data-type="number"><span class="sort-icons">▲▼</span>الشدة</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($allRows) > 0): ?>
                        <?php $i = 1; foreach ($allRows as $row): ?>
                            <?php
                                $severity = (string) ($row['severity'] ?? 'غير محدد');
                                $severityLevel = (int) ($row['severity_level'] ?? 0);
                                $type = (string) ($row['disease_type'] ?? 'غير محدد');
                            ?>

                            <tr data-type="<?= e($type) ?>">
                                <td><?= $i++ ?></td>
                                <td><?= e($row['disease_name'] ?? '') ?></td>
                                <td><?= e($type) ?></td>
                                <td><?= e($row['affected_crops'] ?? 'غير محدد') ?></td>
                                <td><?= e($row['transmission_mode'] ?? 'غير محدد') ?></td>
                                <td><?= e($row['preferred_region'] ?? 'غير محدد') ?></td>
                                <td><?= e($row['description'] ?? '') ?></td>
                                <td>
                                    <span class="badge <?= e(severityClass($severity)) ?>">
                                        <?= $severityLevel ?> - <?= e(severityLabel($severity)) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-row">
                            <td colspan="8">لا توجد بيانات أمراض حالياً.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</main>

<script src="assets/js/app-core.js"></script>
<script src="assets/js/sidebar.js"></script>
<script src="assets/js/plant-diseases.js"></script>
</body>
</html>