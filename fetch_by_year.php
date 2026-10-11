<?php
ob_start();
mysqli_report(MYSQLI_REPORT_OFF);

function json_response(array $payload): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once __DIR__ . '/includes/db.php';

if (!isset($conn) || !$conn || !($conn instanceof mysqli)) {
    json_response([
        "success" => false,
        "message" => "فشل الاتصال بقاعدة البيانات. تأكد من ملف includes/db.php"
    ]);
}

$conn->set_charset("utf8mb4");

$year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$currentYear = (int)date('Y');
$mode = isset($_GET['mode']) ? trim((string)$_GET['mode']) : 'table';

function clamp_score($value): float
{
    if ($value === null || $value === '') {
        return 0;
    }

    $n = (float)$value;

    if ($n < 0) {
        return 0;
    }

    if ($n > 100) {
        return 100;
    }

    return $n;
}

function normalize_csv_names(?string $value): array
{
    if ($value === null || trim($value) === '') {
        return [];
    }

    $parts = array_map('trim', explode('،', $value));
    $parts = array_filter($parts, fn($item) => $item !== '');

    return array_values(array_unique($parts));
}

function fetch_region_extra_data(mysqli $conn, int $regionId): array
{
    $plants = [];
    $diseases = [];
    $diseaseIds = [];
    $diseaseTypes = [];
    $rarePlants = [];
    $toxicPlants = [];

    $avgSpread = 0;
    $rareCount = 0;
    $toxicCount = 0;
    $rareScore = 0;
    $toxicityScore = 0;
    $maxToxicityLevel = 0;

    $plantSql = "
        SELECT DISTINCT pt.local_name_ar
        FROM plant_distribution dist
        INNER JOIN plant_types pt ON pt.plant_id = dist.plant_id
        WHERE dist.region_id = ?
          AND pt.local_name_ar IS NOT NULL
          AND TRIM(pt.local_name_ar) <> ''
        ORDER BY pt.local_name_ar
    ";

    $plantStmt = $conn->prepare($plantSql);

    if ($plantStmt) {
        $plantStmt->bind_param("i", $regionId);
        $plantStmt->execute();
        $plantResult = $plantStmt->get_result();

        while ($p = $plantResult->fetch_assoc()) {
            $plants[] = trim((string)$p['local_name_ar']);
        }

        $plantStmt->close();
    }

    $rareSql = "
        SELECT
            COUNT(*) AS rare_count,
            GROUP_CONCAT(DISTINCT plant_name ORDER BY plant_name SEPARATOR '، ') AS rare_names,
            MAX(CASE
                WHEN rarity_level LIKE '%عالي%' THEN 85
                WHEN rarity_level LIKE '%متوسط%' THEN 60
                WHEN rarity_level LIKE '%منخفض%' THEN 35
                ELSE 45
            END) AS level_score,
            MAX(CASE
                WHEN conservation_status LIKE '%مهدد%' THEN 10
                WHEN conservation_status LIKE '%محمي%' THEN 7
                ELSE 0
            END) AS status_boost
        FROM rare_plants
        WHERE region_id = ?
          AND plant_name IS NOT NULL
          AND TRIM(plant_name) <> ''
    ";

    $rareStmt = $conn->prepare($rareSql);

    if ($rareStmt) {
        $rareStmt->bind_param("i", $regionId);
        $rareStmt->execute();
        $rareResult = $rareStmt->get_result();
        $rareRow = $rareResult ? $rareResult->fetch_assoc() : null;

        if ($rareRow) {
            $rareCount = (int)($rareRow['rare_count'] ?? 0);
            $rarePlants = normalize_csv_names($rareRow['rare_names'] ?? null);

            if ($rareCount > 0) {
                $levelScore = (float)($rareRow['level_score'] ?? 45);
                $statusBoost = (float)($rareRow['status_boost'] ?? 0);
                $countBoost = min(12, $rareCount * 4);
                $rareScore = min(100, $levelScore + $statusBoost + $countBoost);
            }
        }

        $rareStmt->close();
    }

    $toxicSql = "
        SELECT
            COUNT(*) AS toxic_count,
            GROUP_CONCAT(DISTINCT plant_name ORDER BY plant_name SEPARATOR '، ') AS toxic_names,
            MAX(IFNULL(toxicity_level, 0)) AS max_toxicity_level
        FROM toxic_plants
        WHERE region_id = ?
          AND plant_name IS NOT NULL
          AND TRIM(plant_name) <> ''
    ";

    $toxicStmt = $conn->prepare($toxicSql);

    if ($toxicStmt) {
        $toxicStmt->bind_param("i", $regionId);
        $toxicStmt->execute();
        $toxicResult = $toxicStmt->get_result();
        $toxicRow = $toxicResult ? $toxicResult->fetch_assoc() : null;

        if ($toxicRow) {
            $toxicCount = (int)($toxicRow['toxic_count'] ?? 0);
            $toxicPlants = normalize_csv_names($toxicRow['toxic_names'] ?? null);
            $maxToxicityLevel = (float)($toxicRow['max_toxicity_level'] ?? 0);

            if ($toxicCount > 0 || $maxToxicityLevel > 0) {
                $levelScore = $maxToxicityLevel > 0 ? max(0, min(100, $maxToxicityLevel * 20)) : 35;
                $countBoost = min(14, $toxicCount * 3);
                $toxicityScore = min(100, $levelScore + $countBoost);
            }
        }

        $toxicStmt->close();
    }

    $diseaseSql = "
        SELECT DISTINCT d.disease_id, d.name, d.disease_type
        FROM plant_distribution dist
        INNER JOIN plant_disease_cases c ON c.plant_id = dist.plant_id
        INNER JOIN plant_diseases d ON d.disease_id = c.disease_id
        WHERE dist.region_id = ?
        ORDER BY d.name
    ";

    $diseaseStmt = $conn->prepare($diseaseSql);

    if ($diseaseStmt) {
        $diseaseStmt->bind_param("i", $regionId);
        $diseaseStmt->execute();
        $diseaseResult = $diseaseStmt->get_result();

        while ($d = $diseaseResult->fetch_assoc()) {
            if (!empty($d['name'])) {
                $diseases[] = trim((string)$d['name']);
            }

            if (!empty($d['disease_id'])) {
                $diseaseIds[] = (string)$d['disease_id'];
            }

            if (!empty($d['disease_type'])) {
                $diseaseTypes[] = trim((string)$d['disease_type']);
            }
        }

        $diseaseStmt->close();
    }

    $spreadSql = "
        SELECT ROUND(AVG(spread_percentage), 2) AS avg_spread
        FROM disease_spread
        WHERE region_id = ?
    ";

    $spreadStmt = $conn->prepare($spreadSql);

    if ($spreadStmt) {
        $spreadStmt->bind_param("i", $regionId);
        $spreadStmt->execute();
        $spreadResult = $spreadStmt->get_result();
        $spreadRow = $spreadResult ? $spreadResult->fetch_assoc() : null;

        if ($spreadRow && $spreadRow['avg_spread'] !== null) {
            $avgSpread = (float)$spreadRow['avg_spread'];
        }

        $spreadStmt->close();
    }

    return [
        "plants" => array_values(array_unique($plants)),
        "diseases" => array_values(array_unique($diseases)),
        "disease_ids" => array_values(array_unique($diseaseIds)),
        "disease_types" => array_values(array_unique($diseaseTypes)),
        "avg_spread" => round((float)$avgSpread, 2),

        "rare_plants" => $rarePlants,
        "rare_count" => $rareCount,
        "actual_rarity_score" => round((float)$rareScore, 2),

        "toxic_plants" => $toxicPlants,
        "toxic_count" => $toxicCount,
        "actual_toxicity_score" => round((float)$toxicityScore, 2),
        "max_toxicity_level" => round((float)$maxToxicityLevel, 2)
    ];
}

function build_map_rows(mysqli $conn, mysqli_result $result, int $selectedYear): array
{
    $data = [];
    $extraCache = [];

    while ($row = $result->fetch_assoc()) {
        $regionId = (int)$row['region_id'];

        if (!isset($extraCache[$regionId])) {
            $extraCache[$regionId] = fetch_region_extra_data($conn, $regionId);
        }

        $extra = $extraCache[$regionId];

        $ndviMean = $row["ndvi_mean"] !== null ? (float)$row["ndvi_mean"] : null;

        $densityScore = $row["density_score"] !== null
            ? clamp_score($row["density_score"])
            : ($ndviMean !== null ? clamp_score((($ndviMean - 0.015) / 0.320) * 100) : 0);

        $rarityScore = $row["rarity_score"] !== null
            ? clamp_score($row["rarity_score"])
            : clamp_score($extra["actual_rarity_score"]);

        $toxicityScore = $row["toxicity_score"] !== null
            ? clamp_score($row["toxicity_score"])
            : clamp_score($extra["actual_toxicity_score"]);

        $lat = (float)$row["latitude"];
        $lon = (float)$row["longitude"];

        if ($lat < 16 || $lat > 33 || $lon < 34 || $lon > 56) {
            continue;
        }

        $data[] = [
            "region_id" => $regionId,
            "region" => (string)$row["region_name_ar"],
            "lat" => $lat,
            "lon" => $lon,

            "year" => $selectedYear,
            "data_year" => $selectedYear,
            "is_exact_year" => true,

            "ndvi_mean" => $ndviMean !== null ? round($ndviMean, 4) : null,
            "density" => round($densityScore, 2),
            "rarity" => round($rarityScore, 2),
            "toxicity" => round($toxicityScore, 2),

            "rare_count" => (int)$extra["rare_count"],
            "toxic_count" => (int)$extra["toxic_count"],
            "max_toxicity_level" => (float)$extra["max_toxicity_level"],

            "plants" => $extra["plants"],
            "rare_plants" => $extra["rare_plants"],
            "toxic_plants" => $extra["toxic_plants"],
            "diseases" => $extra["diseases"],
            "disease_ids" => $extra["disease_ids"],
            "disease_types" => $extra["disease_types"],
            "avg_spread" => $extra["avg_spread"]
        ];
    }

    return $data;
}

/*
    وضع الخريطة JSON
    مهم جدًا:
    لا يوجد fallback.
    يتم جلب نفس السنة المختارة فقط.
*/
if ($mode === 'map') {
    if ($year < 1990 || $year > $currentYear) {
        json_response([
            "success" => false,
            "message" => "السنة غير صحيحة"
        ]);
    }

    $sql = "
        SELECT
            r.region_id,
            r.region_name_ar,
            hp.point_id,
            hp.latitude,
            hp.longitude,
            rym.year AS data_year,
            rym.ndvi_mean,
            rym.density_score,
            rym.rarity_score,
            rym.toxicity_score
        FROM regions r
        INNER JOIN heatmap_points hp
            ON hp.region_id = r.region_id
        INNER JOIN region_year_metrics rym
            ON rym.region_id = r.region_id
           AND rym.year = ?
        WHERE hp.latitude IS NOT NULL
          AND hp.longitude IS NOT NULL
          AND hp.latitude BETWEEN 16 AND 33
          AND hp.longitude BETWEEN 34 AND 56
        ORDER BY r.region_name_ar ASC, hp.point_id ASC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        json_response([
            "success" => false,
            "message" => "خطأ SQL في استعلام الخريطة: " . $conn->error
        ]);
    }

    $stmt->bind_param("i", $year);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = build_map_rows($conn, $result, $year);

    $stmt->close();

    json_response([
        "success" => true,
        "year" => $year,
        "count" => count($data),
        "data" => $data
    ]);
}


while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: text/html; charset=utf-8');

if ($year <= 0) {
    echo "<tr><td colspan='5'>السنة غير صحيحة</td></tr>";
    exit;
}

$safeYear = (int)$year;

$query = "
    SELECT *, YEAR(created_at) AS year
    FROM vegetation_cover_admin
    WHERE YEAR(created_at) = $safeYear
    ORDER BY id DESC
";

$result = $conn->query($query);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $date = date("Y-m-d", strtotime($row['created_at']));

        $regionName = htmlspecialchars((string)$row['region_name'], ENT_QUOTES, 'UTF-8');
        $plantName = htmlspecialchars((string)$row['plant_name'], ENT_QUOTES, 'UTF-8');
        $plantType = htmlspecialchars((string)$row['plant_type'], ENT_QUOTES, 'UTF-8');
        $plantCount = (int)$row['plant_count'];
        $id = (int)$row['id'];

        echo "<tr>
            <td>
                <div class='region-cell'>
                    <span>{$regionName}</span>
                </div>
            </td>

            <td>{$plantName}</td>

            <td>
                <span class='badge count'>" . number_format($plantCount) . "</span>
            </td>

            <td>
                <span class='badge date'>{$date}</span>
            </td>

            <td>
                <button class='btn-edit open-edit'
                    data-id='{$id}'
                    data-name='{$plantName}'
                    data-type='{$plantType}'
                    data-count='{$plantCount}'
                    data-date='{$date}'
                    data-region='{$regionName}'>
                    ✏️
                </button>

                <a href='delete_vegetation.php?id={$id}'
                   onclick='return confirm(\"متأكدة من الحذف؟\")'>🗑️</a>
            </td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='5'>لا توجد بيانات لهذه السنة</td></tr>";
}
?>