<?php
require_once 'includes/db.php';
require_once 'includes/admin_export_helpers.php';

require_once 'includes/log_activity.php';
admin_require_login();

$filename = 'plant_diseases_' . date('Y-m-d_H-i') . '.csv';
admin_send_csv_headers($filename);
$out = fopen('php://output', 'w');

admin_write_csv_section(
    $out,
    'تقرير الأوبئة والأمراض النباتية',
    ['ID', 'اسم المرض', 'النوع', 'الخطورة', 'المحاصيل المصابة', 'طريقة الانتقال', 'المنطقة المفضلة', 'عدد النباتات المتأثرة'],
    admin_fetch_rows($conn, "
        SELECT
            disease_id,
            COALESCE(name, '') AS name,
            COALESCE(disease_type, '') AS disease_type,
            COALESCE(severity, '') AS severity,
            COALESCE(affected_crops, '') AS affected_crops,
            COALESCE(transmission_mode, '') AS transmission_mode,
            COALESCE(preferred_region, '') AS preferred_region,
            COALESCE(plant_count, 0) AS plant_count
        FROM plant_diseases
        ORDER BY disease_id DESC
    "),
    fn($row) => [
        $row['disease_id'],
        $row['name'],
        $row['disease_type'],
        $row['severity'],
        $row['affected_crops'],
        $row['transmission_mode'],
        $row['preferred_region'],
        $row['plant_count']
    ]
);

fclose($out);
logActivity(
    $conn,
    $_SESSION['user_id'],
    'Export Diseases',
    'تم تصدير بيانات الأمراض النباتية وعدد السجلات '
    . count($rows)
);
exit;
