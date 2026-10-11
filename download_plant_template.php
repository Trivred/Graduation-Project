<?php

session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/log_activity.php';
require_once __DIR__ . '/includes/plant_import_config.php';

$category = plant_import_valid_category($_GET['category'] ?? 'beneficial');

$format = strtolower($_GET['format'] ?? 'csv');

$categories = plant_import_categories();

$columns = plant_import_columns();

$examples = plant_import_examples();

$fileBase = 'plant_import_template_' . $category;

$categoryLabel = $categories[$category]['label'] ?? 'النباتات';

if ($format === 'xls') {

    logActivity(
        $conn,
        $_SESSION['user_id'],
        'Download Template',
        'تم تحميل قالب Excel لفئة '
        . $categoryLabel
    );

    download_excel_xml(
        $fileBase . '.xls',
        $columns[$category],
        $examples[$category],
        $categoryLabel
    );

    exit;
}

logActivity(
    $conn,
    $_SESSION['user_id'],
    'Download Template',
    'تم تحميل قالب CSV لفئة '
    . $categoryLabel
);

download_csv(
    $fileBase . '.csv',
    $columns[$category],
    $examples[$category]
);

exit;

function download_csv(string $filename, array $columns, array $rows): void
{
    header('Content-Type: text/csv; charset=UTF-8');

    header('Content-Disposition: attachment; filename="' . $filename . '"');

    header('Pragma: no-cache');

    header('Expires: 0');

    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    fputcsv($out, $columns);

    foreach ($rows as $row) {

        fputcsv($out, $row);

    }

    fclose($out);
}

function download_excel_xml(
    string $filename,
    array $columns,
    array $rows,
    string $sheetName
): void
{
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');

    header('Content-Disposition: attachment; filename="' . $filename . '"');

    header('Pragma: no-cache');

    header('Expires: 0');

    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    echo "<?mso-application progid=\"Excel.Sheet\"?>\n";

    echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" ";

    echo "xmlns:o=\"urn:schemas-microsoft-com:office:office\" ";

    echo "xmlns:x=\"urn:schemas-microsoft-com:office:excel\" ";

    echo "xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";

    echo "<Styles>";

    echo "<Style ss:ID=\"Header\">";

    echo "<Font ss:Bold=\"1\"/>";

    echo "<Interior ss:Color=\"#D9EAD3\" ss:Pattern=\"Solid\"/>";

    echo "</Style>";

    echo "</Styles>\n";

    echo "<Worksheet ss:Name=\"" . xml_escape($sheetName) . "\"><Table>\n";

    echo "<Row>";

    foreach ($columns as $column) {

        echo "<Cell ss:StyleID=\"Header\">";

        echo "<Data ss:Type=\"String\">";

        echo xml_escape($column);

        echo "</Data></Cell>";
    }

    echo "</Row>\n";

    foreach ($rows as $row) {

        echo "<Row>";

        foreach ($row as $cell) {

            $type = is_numeric($cell) ? 'Number' : 'String';

            echo "<Cell><Data ss:Type=\"$type\">";

            echo xml_escape((string)$cell);

            echo "</Data></Cell>";
        }

        echo "</Row>\n";
    }

    echo "</Table></Worksheet></Workbook>";
}

function xml_escape(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_XML1,
        'UTF-8'
    );
}
?>