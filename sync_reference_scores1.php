<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "vegetation_platform_new";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$conn->set_charset("utf8mb4");

try {
    $conn->begin_transaction();

    $updateToxicitySql = "
        UPDATE region_year_metrics r
        INNER JOIN (
            SELECT
                region_id,
                ROUND(
                    LEAST(
                        100,
                        GREATEST(
                            0,
                            AVG(COALESCE(toxicity_level, 0)) * 20
                        )
                    ),
                    2
                ) AS direct_toxicity_score
            FROM toxic_plants
            WHERE region_id IS NOT NULL
              AND COALESCE(toxicity_level, 0) > 0
            GROUP BY region_id
        ) t
            ON t.region_id = r.region_id
        SET
            r.toxicity_score = t.direct_toxicity_score,
            r.updated_at = NOW()
    ";

    if (!$conn->query($updateToxicitySql)) {
        throw new Exception('Failed to update toxicity scores: ' . $conn->error);
    }

    $toxicityAffected = $conn->affected_rows;

    $updateRaritySql = "
        UPDATE region_year_metrics r
        INNER JOIN (
            SELECT
                region_id,
                ROUND(
                    AVG(
                        LEAST(
                            100,
                            GREATEST(
                                0,
                                (
                                    CASE
                                        WHEN rarity_level IN ('عالية', 'عالي', 'مرتفع', 'مرتفعة', 'نادرة', 'نادر') THEN 70
                                        WHEN rarity_level IN ('متوسطة', 'متوسط') THEN 45
                                        WHEN rarity_level IN ('منخفضة', 'منخفض', 'عادية') THEN 20
                                        ELSE 35
                                    END
                                ) +
                                (
                                    CASE
                                        WHEN conservation_status IN ('حرج', 'حرجة', 'مهدد جداً', 'مهدد جدًا') THEN 25
                                        WHEN conservation_status IN ('مهدد', 'مهددة') THEN 20
                                        WHEN conservation_status IN ('محمي', 'محمية') THEN 10
                                        WHEN conservation_status IN ('مستقر', 'مستقرة') THEN 0
                                        ELSE 0
                                    END
                                )
                            )
                        )
                    ),
                    2
                ) AS direct_rarity_score
            FROM rare_plants
            WHERE region_id IS NOT NULL
            GROUP BY region_id
        ) rr
            ON rr.region_id = r.region_id
        SET
            r.rarity_score = rr.direct_rarity_score,
            r.updated_at = NOW()
    ";

    if (!$conn->query($updateRaritySql)) {
        throw new Exception('Failed to update rarity scores: ' . $conn->error);
    }

    $rarityAffected = $conn->affected_rows;

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Direct documented toxicity and rarity scores synced successfully.',
        'toxicity_rows_updated' => $toxicityAffected,
        'rarity_rows_updated' => $rarityAffected
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    $conn->rollback();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

$conn->close();