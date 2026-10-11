<?php
require_once 'includes/db.php';
require_once 'includes/admin_export_helpers.php';
require_once 'includes/log_activity.php';
admin_require_login();

$filename = 'admin_dashboard_report_' . date('Y-m-d_H-i') . '.csv';
admin_send_csv_headers($filename);
$out = fopen('php://output', 'w');

$summary = [
    ['إجمالي المستخدمين', admin_scalar($conn, "SELECT COUNT(*) FROM users")],
    ['إجمالي النباتات', admin_scalar($conn, "SELECT COUNT(*) FROM plants")],
    ['إجمالي المناطق', admin_scalar($conn, "SELECT COUNT(*) FROM regions")],
    ['النباتات السامة', admin_scalar($conn, "SELECT COUNT(*) FROM toxic_plants")],
    ['النباتات النادرة', admin_scalar($conn, "SELECT COUNT(*) FROM rare_plants")],
    ['الأمراض النباتية', admin_scalar($conn, "SELECT COUNT(*) FROM plant_diseases")],
];

admin_write_csv_row($out, ['تقرير لوحة التحكم العامة']);
admin_write_csv_row($out, ['المؤشر', 'القيمة']);
foreach ($summary as $row) {
    admin_write_csv_row($out, $row);
}
admin_write_csv_row($out, []);

admin_write_csv_section(
    $out,
    'توزيع النباتات حسب المنطقة',
    ['المنطقة', 'عدد النباتات'],
    admin_fetch_rows($conn, "
        SELECT r.region_name_ar, COUNT(p.plant_id) AS total
        FROM regions r
        LEFT JOIN plants p ON p.region_id = r.region_id
        GROUP BY r.region_id, r.region_name_ar
        ORDER BY total DESC, r.region_name_ar ASC
    "),
    fn($row) => [$row['region_name_ar'], $row['total']]
);

admin_write_csv_section(
    $out,
    'أعلى مناطق انتشار الأمراض',
    ['المنطقة', 'متوسط الانتشار'],
    admin_fetch_rows($conn, "
        SELECT r.region_name_ar, ROUND(AVG(IFNULL(d.spread_percentage,0)),2) AS spread
        FROM regions r
        LEFT JOIN diseases d ON d.region_id = r.region_id
        GROUP BY r.region_id, r.region_name_ar
        ORDER BY spread DESC
        LIMIT 10
    "),
    fn($row) => [$row['region_name_ar'], $row['spread']]
);

fclose($out);
logActivity(
    $conn,
    $_SESSION['user_id'],
    'Export Dashboard',
    'تم تصدير ملخص لوحة التحكم'
);
exit;
