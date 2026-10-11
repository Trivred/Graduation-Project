<?php
require_once 'includes/db.php';
require_once 'includes/admin_export_helpers.php';
require_once 'includes/log_activity.php';
admin_require_login();

$filename = 'plants_report_' . date('Y-m-d_H-i') . '.csv';
admin_send_csv_headers($filename);
$out = fopen('php://output', 'w');

admin_write_csv_section(
    $out,
    'تقرير النباتات المفيدة',
    ['ID', 'اسم النبات', 'الاسم العلمي', 'الفائدة', 'المنطقة', 'تاريخ الإضافة'],
    admin_fetch_rows($conn, "
        SELECT p.plant_id, p.local_name_ar, p.scientific_name, p.benefits, r.region_name_ar, p.created_at
        FROM plants p
        LEFT JOIN regions r ON p.region_id = r.region_id
        WHERE p.category = 'beneficial'
        ORDER BY p.plant_id DESC
    "),
    fn($row) => [
        $row['plant_id'], $row['local_name_ar'], $row['scientific_name'],
        $row['benefits'], $row['region_name_ar'], $row['created_at']
    ]
);

admin_write_csv_section(
    $out,
    'تقرير النباتات الضارة',
    ['ID', 'اسم النبات', 'المنطقة', 'نوع الضرر', 'مستوى الخطورة', 'الوصف'],
    admin_fetch_rows($conn, "
        SELECT hp.id, p.local_name_ar, r.region_name_ar, hp.hazard_type, hp.severity_level, hp.description
        FROM harmful_plants hp
        JOIN plants p ON hp.plant_id = p.plant_id
        JOIN regions r ON p.region_id = r.region_id
        ORDER BY hp.id DESC
    "),
    fn($row) => [
        $row['id'], $row['local_name_ar'], $row['region_name_ar'],
        $row['hazard_type'], $row['severity_level'], $row['description']
    ]
);

admin_write_csv_section(
    $out,
    'تقرير الأمراض النباتية',
    ['ID', 'اسم المرض', 'النوع', 'الخطورة', 'المحاصيل المصابة', 'طريقة الانتقال', 'المنطقة'],
    admin_fetch_rows($conn, "
        SELECT disease_id, name, disease_type, severity, affected_crops, transmission_mode, preferred_region
        FROM plant_diseases
        ORDER BY disease_id DESC
    "),
    fn($row) => [
        $row['disease_id'], $row['name'], $row['disease_type'], $row['severity'],
        $row['affected_crops'], $row['transmission_mode'], $row['preferred_region']
    ]
);

admin_write_csv_section(
    $out,
    'تقرير المناطق',
    ['ID', 'المنطقة', 'خط العرض', 'خط الطول'],
    admin_fetch_rows($conn, "
        SELECT region_id, region_name_ar, latitude, longitude
        FROM regions
        ORDER BY region_name_ar ASC
    "),
    fn($row) => [$row['region_id'], $row['region_name_ar'], $row['latitude'], $row['longitude']]
);

fclose($out);
logActivity(
    $conn,
    $_SESSION['user_id'],
    'Export Vegetation',
    'تم تصدير بيانات الغطاء النباتي وعدد السجلات '
    . count($rows)
);
exit;
