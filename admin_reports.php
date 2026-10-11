<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/log_activity.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login_register.php');
    exit();
}

$pageTitle = 'تصدير التقارير';
$adminTitle = 'تصدير التقارير';
$pageBodyClass = 'admin-reports-page';

function adminReportE($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function adminReportNumber($value, int $decimals = 0): string
{
    return number_format((float)$value, $decimals);
}

function adminReportScalar(mysqli $conn, string $sql, string $field = 'total')
{
    $result = @$conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return $row[$field] ?? 0;
}

function adminReportTableExists(mysqli $conn, string $tableName): bool
{
    $safeTable = $conn->real_escape_string($tableName);
    $result = @$conn->query("SHOW TABLES LIKE '{$safeTable}'");

    return $result && $result->num_rows > 0;
}

function adminReportRows(mysqli $conn, string $tableName, string $orderBy = ''): array
{
    if (!adminReportTableExists($conn, $tableName)) {
        return [];
    }

    $sql = "SELECT * FROM `{$tableName}`";

    if ($orderBy !== '') {
        $sql .= " ORDER BY {$orderBy}";
    }

    $rows = [];
    $result = @$conn->query($sql);

    if (!$result) {
        return [];
    }

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function adminReportWriteCsvSection($handle, string $title, array $rows): void
{
    fputcsv($handle, []);
    fputcsv($handle, [$title]);

    if (empty($rows)) {
        fputcsv($handle, ['لا توجد بيانات']);
        return;
    }

    fputcsv($handle, array_keys($rows[0]));

    foreach ($rows as $row) {
        fputcsv($handle, array_values($row));
    }
}

$summaryRows = [
    [
        'المؤشر' => 'عدد المستخدمين',
        'القيمة' => adminReportScalar($conn, 'SELECT COUNT(*) AS total FROM users'),
    ],
    [
        'المؤشر' => 'عدد المناطق',
        'القيمة' => adminReportScalar($conn, 'SELECT COUNT(*) AS total FROM regions'),
    ],
    [
        'المؤشر' => 'النباتات السامة',
        'القيمة' => adminReportScalar($conn, 'SELECT COUNT(*) AS total FROM toxic_plants'),
    ],
    [
        'المؤشر' => 'النباتات النادرة',
        'القيمة' => adminReportScalar($conn, 'SELECT COUNT(*) AS total FROM rare_plants'),
    ],
    [
        'المؤشر' => 'النباتات النافعة',
        'القيمة' => adminReportScalar($conn, 'SELECT COUNT(*) AS total FROM plant_benefits'),
    ],
    [
        'المؤشر' => 'الأمراض النباتية',
        'القيمة' => adminReportScalar($conn, 'SELECT COUNT(*) AS total FROM plant_diseases'),
    ],
    [
        'المؤشر' => 'تنبيهات انتشار مرضي مرتفع',
        'القيمة' => adminReportScalar(
            $conn,
            'SELECT COUNT(*) AS total FROM disease_spread WHERE IFNULL(spread_percentage, 0) >= 50'
        ),
    ],
];

$regionsRows = adminReportRows($conn, 'regions', 'region_id DESC');
$vegetationRows = adminReportRows($conn, 'vegetation_cover_admin', 'year DESC, id DESC');
$toxicRows = adminReportRows($conn, 'toxic_plants', 'id DESC');
$rareRows = adminReportRows($conn, 'rare_plants', 'id DESC');
$benefitRows = adminReportRows($conn, 'plant_benefits', 'id DESC');
$diseaseRows = adminReportRows($conn, 'plant_diseases', 'disease_id DESC');
$spreadRows = adminReportRows($conn, 'disease_spread', 'id DESC');

$reportSections = [
    [
        'title' => 'المناطق',
        'rows' => $regionsRows,
        'icon' => 'ri-map-pin-line',
        'count' => count($regionsRows),
    ],
    [
        'title' => 'بيانات الغطاء النباتي',
        'rows' => $vegetationRows,
        'icon' => 'ri-leaf-line',
        'count' => count($vegetationRows),
    ],
    [
        'title' => 'النباتات السامة',
        'rows' => $toxicRows,
        'icon' => 'ri-radio-button-line',
        'count' => count($toxicRows),
    ],
    [
        'title' => 'النباتات النادرة',
        'rows' => $rareRows,
        'icon' => 'ri-star-line',
        'count' => count($rareRows),
    ],
    [
        'title' => 'النباتات النافعة',
        'rows' => $benefitRows,
        'icon' => 'ri-heart-3-line',
        'count' => count($benefitRows),
    ],
    [
        'title' => 'الأمراض النباتية',
        'rows' => $diseaseRows,
        'icon' => 'ri-virus-line',
        'count' => count($diseaseRows),
    ],
    [
        'title' => 'انتشار الأمراض',
        'rows' => $spreadRows,
        'icon' => 'ri-bug-line',
        'count' => count($spreadRows),
    ],
];

$totalExportRows = array_sum(array_column($reportSections, 'count'));
$generatedAt = date('Y-m-d H:i:s');

if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="admin_complete_report_' . date('Y-m-d_H-i') . '.csv"');

    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    fputcsv($out, ['تقرير كامل لجميع بيانات النظام']);
    fputcsv($out, ['تاريخ التصدير', $generatedAt]);

    adminReportWriteCsvSection($out, 'ملخص لوحة التحكم', $summaryRows);
    adminReportWriteCsvSection($out, 'المناطق', $regionsRows);
    adminReportWriteCsvSection($out, 'بيانات الغطاء النباتي', $vegetationRows);
    adminReportWriteCsvSection($out, 'النباتات السامة', $toxicRows);
    adminReportWriteCsvSection($out, 'النباتات النادرة', $rareRows);
    adminReportWriteCsvSection($out, 'النباتات النافعة', $benefitRows);
    adminReportWriteCsvSection($out, 'الأمراض النباتية', $diseaseRows);
    adminReportWriteCsvSection($out, 'انتشار الأمراض', $spreadRows);

    fclose($out);
    logActivity(
        $conn,
        $_SESSION['user_id'],
        'Export Reports',
        'تم تصدير التقرير الكامل بصيغة CSV'
    );

    exit();
}

include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';
?>

<section class="page-head">
    <div>
        <span class="eyebrow">تقارير الإدارة</span>
        <h3>
            <i class="ri-file-chart-line"></i>
            تصدير التقارير
        </h3>
    </div>

    <div class="hero-actions no-print">

        <a class="btn-soft secondary" href="admin_dashboard.php">
            <i class="ri-dashboard-3-line"></i>
            رجوع للوحة التحكم
        </a>
    </div>
</section>

<div class="stats-grid">
    <article class="stat-card success">
        <div>
            <span>أقسام التقرير</span>
            <b><?= adminReportNumber(count($reportSections)) ?></b>
            <small class="text-muted">أقسام رئيسية قابلة للتصدير</small>
        </div>

        <div class="icon">
            <i class="ri-folders-line"></i>
        </div>
    </article>

    <article class="stat-card primary">
        <div>
            <span>إجمالي السجلات</span>
            <b><?= adminReportNumber($totalExportRows) ?></b>
            <small class="text-muted">ضمن التقرير الكامل</small>
        </div>

        <div class="icon">
            <i class="ri-database-2-line"></i>
        </div>
    </article>

    <article class="stat-card danger">
        <div>
            <span>آخر تحديث</span>
            <b><?= adminReportE(date('H:i')) ?></b>
            <small class="text-muted"><?= adminReportE(date('Y-m-d')) ?></small>
        </div>

        <div class="icon">
            <i class="ri-time-line"></i>
        </div>
    </article>
</div>

<div class="row g-4 mb-4 no-print">
    <div class="col-md-6 col-xl-3">
        <section class="summary-card h-100">
            <span class="badge-soft success mb-3">
                <i class="ri-database-2-line"></i>
                التقرير الكامل
            </span>

            <h5 class="fw-black mb-2">تقرير جميع البيانات</h5>

            <p class="text-muted fw-bold mb-3">
                يشمل الملخص، المناطق، الغطاء النباتي، السمية، الندرة، النباتات النافعة، الأمراض، وانتشار الأمراض.
            </p>

            <a class="btn-soft w-100 justify-content-center" href="admin_reports.php?download=csv">
                <i class="ri-download-2-line"></i>
                تحميل CSV كامل
            </a>
        </section>
    </div>

    <div class="col-md-6 col-xl-3">
        <section class="summary-card h-100">
            <span class="badge-soft primary mb-3">
                <i class="ri-dashboard-3-line"></i>
                تقرير اللوحة
            </span>

            <h5 class="fw-black mb-2">ملخص لوحة التحكم</h5>

            <p class="text-muted fw-bold mb-3">
                تصدير ملخص أرقام لوحة التحكم الحالية ومؤشرات المتابعة.
            </p>

            <a class="btn-soft secondary w-100 justify-content-center" href="export_dashboard.php">
                <i class="ri-download-2-line"></i>
                تصدير الملخص
            </a>
        </section>
    </div>

    <div class="col-md-6 col-xl-3">
        <section class="summary-card h-100">
            <span class="badge-soft danger mb-3">
                <i class="ri-file-list-3-line"></i>
                تقارير جزئية
            </span>

            <h5 class="fw-black mb-2">تصدير حسب القسم</h5>

            <div class="d-grid gap-2 mt-3">
                <a class="btn-soft secondary justify-content-center" href="export_regions.php">
                    <i class="ri-map-pin-line"></i>
                    المناطق
                </a>

                <a class="btn-soft secondary justify-content-center" href="export_diseases.php">
                    <i class="ri-virus-line"></i>
                    الأمراض
                </a>

                <a class="btn-soft secondary justify-content-center" href="export_excel.php">
                    <i class="ri-leaf-line"></i>
                    الغطاء النباتي
                </a>
            </div>
        </section>
    </div>
</div>

<section class="table-card mb-4">
    <div class="toolbar mb-3">
        <div>
            <span class="eyebrow">ملخص التقرير</span>
            <h5 class="fw-black mb-0">
                <i class="ri-list-check-3"></i>
                مؤشرات البيانات الحالية
            </h5>
        </div>

        <div class="d-flex gap-2 flex-wrap no-print">
            <input
                id="searchInput"
                class="search-box"
                type="search"
                placeholder="ابحث داخل الملخص..."
            >

            <button
                class="btn-soft secondary"
                type="button"
                data-export-table="admin_report_summary.csv"
            >
                <i class="ri-download-2-line"></i>
                تصدير الملخص CSV
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="dataTable" class="table table-hover align-middle sortable-table">
            <thead>
            <tr>
                <th>المؤشر <span class="sort-icons">↕</span></th>
                <th data-type="number">القيمة <span class="sort-icons">↕</span></th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($summaryRows as $row): ?>
                <tr>
                    <td class="fw-bold"><?= adminReportE($row['المؤشر']) ?></td>
                    <td><?= adminReportE($row['القيمة']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script src="assets/js/admin-table-tools.js?v=20260426-final" defer></script>

<?php
$conn->close();
?>

    </div>
</div>
</div>
</body>
</html>