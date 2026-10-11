<?php
require_once 'includes/db.php';
require_once 'includes/admin_export_helpers.php';
require_once 'includes/log_activity.php';
admin_require_login();

$filename = 'regions_report_' . date('Y-m-d_H-i') . '.csv';
admin_send_csv_headers($filename);
$out = fopen('php://output', 'w');

admin_write_csv_section(
    $out,
    'تقرير المناطق',
    ['ID', 'اسم المنطقة', 'نوع المنطقة', 'حالة الغطاء النباتي', 'عدد النباتات', 'متوسط الكثافة', 'نباتات سامة', 'نباتات نادرة'],
    admin_fetch_rows($conn, "
        SELECT
            r.region_id,
            r.region_name_ar,
            COALESCE(r.region_type, 'غير محدد') AS region_type,
            CASE
                WHEN COALESCE(stats.plants_count, 0) >= 10000 THEN 'مستقرة'
                ELSE 'غير مستقرة'
            END AS vegetation_status,
            COALESCE(stats.plants_count, 0) AS plants_count,
            COALESCE(stats.avg_density, 0) AS avg_density,
            COALESCE(toxic.toxic_count, 0) AS toxic_count,
            COALESCE(rare.rare_count, 0) AS rare_count
        FROM regions r
        LEFT JOIN (
            SELECT region_id, SUM(COALESCE(plant_count, 0)) AS plants_count, ROUND(AVG(COALESCE(density_per_km2, 0)), 2) AS avg_density
            FROM region_plant_stats
            GROUP BY region_id
        ) stats ON stats.region_id = r.region_id
        LEFT JOIN (
            SELECT region_id, COUNT(*) AS toxic_count
            FROM toxic_plants
            GROUP BY region_id
        ) toxic ON toxic.region_id = r.region_id
        LEFT JOIN (
            SELECT region_id, COUNT(*) AS rare_count
            FROM rare_plants
            GROUP BY region_id
        ) rare ON rare.region_id = r.region_id
        ORDER BY r.region_name_ar ASC
    "),
    fn($row) => [
        $row['region_id'],
        $row['region_name_ar'],
        $row['region_type'],
        $row['vegetation_status'],
        $row['plants_count'],
        $row['avg_density'],
        $row['toxic_count'],
        $row['rare_count']
    ]
);

fclose($out);
logActivity(
    $conn,
    $_SESSION['user_id'],
    'Export Regions',
    'تم تصدير بيانات المناطق وعدد السجلات '
    . count($rows)
);
exit;
