<?php
/**
 * Plant import service.
 * Handles validation, optional server saving, and database insertion.
 */

require_once __DIR__ . '/plant_import_config.php';
require_once __DIR__ . '/plant_import_parser.php';

if (!function_exists('pi_row_value')) {
    function pi_row_value(array $row, string $key, string $default = ''): string
    {
        $value = pi_clean_text($row[$key] ?? '');
        return $value === '' ? $default : $value;
    }
}

if (!function_exists('pi_float')) {
    function pi_float($value, float $default = 0.0): float
    {
        $value = str_replace(',', '.', pi_clean_text($value));
        return is_numeric($value) ? (float)$value : $default;
    }
}

if (!function_exists('pi_int')) {
    function pi_int($value, int $default = 0): int
    {
        $value = pi_clean_text($value);
        return is_numeric($value) ? (int)$value : $default;
    }
}

if (!function_exists('pi_nullable_int')) {
    function pi_nullable_int($value): ?int
    {
        $value = pi_clean_text($value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        $value = (int)$value;
        return $value > 0 ? $value : null;
    }
}

if (!function_exists('pi_get_region_id')) {
    function pi_get_region_id(mysqli $conn, array $row): ?int
    {
        $regionId = pi_nullable_int($row['region_id'] ?? '');
        if ($regionId !== null) {
            $stmt = $conn->prepare('SELECT region_id FROM regions WHERE region_id = ? LIMIT 1');
            $stmt->bind_param('i', $regionId);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result && $result->num_rows > 0;
            $stmt->close();

            if ($exists) {
                return $regionId;
            }
        }

        $regionName = pi_row_value($row, 'region_name');
        if ($regionName === '') {
            return null;
        }

        $like = '%' . $regionName . '%';
        $stmt = $conn->prepare('
            SELECT region_id
            FROM regions
            WHERE region_name_ar = ? OR region_name_ar LIKE ?
            ORDER BY region_id ASC
            LIMIT 1
        ');
        $stmt->bind_param('ss', $regionName, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $region = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $region ? (int)$region['region_id'] : null;
    }
}

if (!function_exists('pi_get_region_name')) {
    function pi_get_region_name(mysqli $conn, ?int $regionId, string $fallback = ''): string
    {
        $fallback = pi_clean_text($fallback);
        if ($fallback !== '') {
            return $fallback;
        }

        if ($regionId === null) {
            return 'غير محدد';
        }

        $stmt = $conn->prepare('SELECT region_name_ar FROM regions WHERE region_id = ? LIMIT 1');
        $stmt->bind_param('i', $regionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $region = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $region['region_name_ar'] ?? 'غير محدد';
    }
}

if (!function_exists('pi_find_or_create_plant_type')) {
    function pi_find_or_create_plant_type(
        mysqli $conn,
        string $plantName,
        string $scientificName,
        string $category,
        ?int $regionId,
        float $toxicityLevel = 0.0
    ): int {
        $stmt = $conn->prepare('
            SELECT plant_id
            FROM plant_types
            WHERE local_name_ar = ?
            ORDER BY plant_id ASC
            LIMIT 1
        ');
        $stmt->bind_param('s', $plantName);
        $stmt->execute();
        $result = $stmt->get_result();
        $plant = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($plant) {
            return (int)$plant['plant_id'];
        }

        $stmt = $conn->prepare('
            INSERT INTO plant_types
            (
                local_name_ar,
                scientific_name,
                plant_category,
                gas_emission_level,
                toxicity_level,
                region_id,
                climate_type,
                soil_type,
                water_need_level,
                growth_rate
            )
            VALUES (?, ?, ?, 0, ?, ?, NULL, NULL, NULL, NULL)
        ');
        $stmt->bind_param('sssdi', $plantName, $scientificName, $category, $toxicityLevel, $regionId);
        $stmt->execute();
        $plantId = (int)$stmt->insert_id;
        $stmt->close();

        return $plantId;
    }
}

if (!function_exists('pi_insert_vegetation_summary')) {
    function pi_insert_vegetation_summary(mysqli $conn, array $row, string $plantName, string $plantType, string $regionName): void
    {
        $plantCountText = pi_row_value($row, 'plant_count');
        if ($plantCountText === '') {
            return;
        }

        $plantCount = pi_int($plantCountText, 0);
        $year = pi_int($row['year'] ?? date('Y'), (int)date('Y'));

        $stmt = $conn->prepare('
            INSERT INTO vegetation_cover_admin
            (region_name, plant_name, plant_type, plant_count, created_at, year)
            VALUES (?, ?, ?, ?, NOW(), ?)
        ');
        $stmt->bind_param('sssii', $regionName, $plantName, $plantType, $plantCount, $year);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('pi_import_beneficial')) {
    function pi_import_beneficial(mysqli $conn, array $row): void
    {
        $plantName = pi_row_value($row, 'plant_name');
        if ($plantName === '') {
            throw new RuntimeException('اسم النبات مطلوب للنباتات النافعة.');
        }

        $regionId = pi_get_region_id($conn, $row);
        $regionName = pi_get_region_name($conn, $regionId, pi_row_value($row, 'region_name'));
        $scientificName = pi_row_value($row, 'scientific_name');
        $benefitType = pi_row_value($row, 'benefit_type', 'فائدة عامة');
        $description = pi_row_value($row, 'description', 'تم الاستيراد من ملف Excel.');
        $latitude = pi_float($row['latitude'] ?? 24.713600, 24.713600);
        $longitude = pi_float($row['longitude'] ?? 46.675300, 46.675300);

        $plantId = pi_find_or_create_plant_type($conn, $plantName, $scientificName, 'نافعة', $regionId, 0.0);

        $stmt = $conn->prepare('
            INSERT INTO plant_benefits
            (plant_id, benefit_type, description, latitude, longitude)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->bind_param('issdd', $plantId, $benefitType, $description, $latitude, $longitude);
        $stmt->execute();
        $stmt->close();

        pi_insert_vegetation_summary($conn, $row, $plantName, 'نافعة', $regionName);
    }
}

if (!function_exists('pi_import_toxic')) {
    function pi_import_toxic(mysqli $conn, array $row): void
    {
        $plantName = pi_row_value($row, 'plant_name');
        if ($plantName === '') {
            throw new RuntimeException('اسم النبات مطلوب للنباتات السامة.');
        }

        $regionId = pi_get_region_id($conn, $row);
        $regionName = pi_get_region_name($conn, $regionId, pi_row_value($row, 'region_name'));
        $scientificName = pi_row_value($row, 'scientific_name');
        $toxicityLevel = pi_float($row['toxicity_level'] ?? 0, 0.0);
        $symptoms = pi_row_value($row, 'symptoms', 'غير محدد');
        $latitude = pi_float($row['latitude'] ?? 0, 0.0);
        $longitude = pi_float($row['longitude'] ?? 0, 0.0);

        pi_find_or_create_plant_type($conn, $plantName, $scientificName, 'سامة', $regionId, $toxicityLevel);

        $stmt = $conn->prepare('
            INSERT INTO toxic_plants
            (toxicity_level, symptoms, latitude, longitude, plant_name, region_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->bind_param('dsddsi', $toxicityLevel, $symptoms, $latitude, $longitude, $plantName, $regionId);
        $stmt->execute();
        $stmt->close();

        pi_insert_vegetation_summary($conn, $row, $plantName, 'سامة', $regionName);
    }
}

if (!function_exists('pi_import_rare')) {
    function pi_import_rare(mysqli $conn, array $row): void
    {
        $plantName = pi_row_value($row, 'plant_name');
        if ($plantName === '') {
            throw new RuntimeException('اسم النبات مطلوب للنباتات النادرة.');
        }

        $regionId = pi_get_region_id($conn, $row);
        $regionName = pi_get_region_name($conn, $regionId, pi_row_value($row, 'region_name'));
        $scientificName = pi_row_value($row, 'scientific_name');
        $rarityLevel = pi_row_value($row, 'rarity_level', 'نادرة');
        $conservationStatus = pi_row_value($row, 'conservation_status', 'بحاجة إلى متابعة');
        $latitude = pi_float($row['latitude'] ?? 0, 0.0);
        $longitude = pi_float($row['longitude'] ?? 0, 0.0);

        pi_find_or_create_plant_type($conn, $plantName, $scientificName, 'نادرة', $regionId, 0.0);

        $stmt = $conn->prepare('
            INSERT INTO rare_plants
            (rarity_level, conservation_status, latitude, longitude, region_id, plant_name)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->bind_param('ssddis', $rarityLevel, $conservationStatus, $latitude, $longitude, $regionId, $plantName);
        $stmt->execute();
        $stmt->close();

        pi_insert_vegetation_summary($conn, $row, $plantName, 'نادرة', $regionName);
    }
}

if (!function_exists('pi_import_disease')) {
    function pi_import_disease(mysqli $conn, array $row): void
    {
        $diseaseName = pi_row_value($row, 'disease_name');
        if ($diseaseName === '') {
            throw new RuntimeException('اسم المرض أو الوباء مطلوب.');
        }

        $regionId = pi_get_region_id($conn, $row);
        $regionName = pi_get_region_name($conn, $regionId, pi_row_value($row, 'region_name'));
        $diseaseType = pi_row_value($row, 'disease_type', 'غير محدد');
        $severity = pi_row_value($row, 'severity', 'متوسطة');
        $description = pi_row_value($row, 'description', 'تم الاستيراد من ملف Excel.');
        $affectedCrops = pi_row_value($row, 'affected_crops', 'غير محدد');
        $transmissionMode = pi_row_value($row, 'transmission_mode', 'غير محدد');
        $plantCount = pi_int($row['plant_count'] ?? 0, 0);

        $stmt = $conn->prepare('
            INSERT INTO plant_diseases
            (name, disease_type, severity, description, affected_crops, transmission_mode, preferred_region, plant_count)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->bind_param('sssssssi', $diseaseName, $diseaseType, $severity, $description, $affectedCrops, $transmissionMode, $regionName, $plantCount);
        $stmt->execute();
        $diseaseId = (int)$stmt->insert_id;
        $stmt->close();

        $infectedArea = pi_row_value($row, 'infected_area_km2') === '' ? null : pi_float($row['infected_area_km2']);
        $spreadPercentage = pi_row_value($row, 'spread_percentage') === '' ? null : pi_float($row['spread_percentage']);

        if ($regionId !== null && ($infectedArea !== null || $spreadPercentage !== null)) {
            $stmt = $conn->prepare('
                INSERT INTO disease_spread
                (disease_id, region_id, infected_area_km2, spread_percentage)
                VALUES (?, ?, ?, ?)
            ');
            $stmt->bind_param('iidd', $diseaseId, $regionId, $infectedArea, $spreadPercentage);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if (!function_exists('pi_import_vegetation')) {
    function pi_import_vegetation(mysqli $conn, array $row): void
    {
        $regionName = pi_row_value($row, 'region_name');
        $plantName = pi_row_value($row, 'plant_name');
        $plantType = pi_row_value($row, 'plant_type', 'غير محدد');
        $plantCount = pi_int($row['plant_count'] ?? 0, 0);
        $year = pi_int($row['year'] ?? date('Y'), (int)date('Y'));

        if ($regionName === '' || $plantName === '') {
            throw new RuntimeException('اسم المنطقة واسم النبات مطلوبان للغطاء النباتي العام.');
        }

        $stmt = $conn->prepare('
            INSERT INTO vegetation_cover_admin
            (region_name, plant_name, plant_type, plant_count, created_at, year)
            VALUES (?, ?, ?, ?, NOW(), ?)
        ');
        $stmt->bind_param('sssii', $regionName, $plantName, $plantType, $plantCount, $year);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('pi_import_rows')) {
    function pi_import_rows(mysqli $conn, string $category, array $rows): array
    {
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                match ($category) {
                    'beneficial' => pi_import_beneficial($conn, $row),
                    'toxic' => pi_import_toxic($conn, $row),
                    'rare' => pi_import_rare($conn, $row),
                    'disease' => pi_import_disease($conn, $row),
                    'vegetation' => pi_import_vegetation($conn, $row),
                    default => throw new RuntimeException('الفئة المحددة غير صحيحة.'),
                };

                $success++;
            } catch (Throwable $e) {
                $failed++;
                if (count($errors) < 10) {
                    $errors[] = 'السطر ' . ($index + 2) . ': ' . $e->getMessage();
                }
            }
        }

        return ['success' => $success, 'failed' => $failed, 'errors' => $errors];
    }
}

if (!function_exists('handle_plant_import')) {
    function handle_plant_import(mysqli $conn, array $post, array $files): array
    {
        $category = plant_import_valid_category($post['category'] ?? 'beneficial');
        $saveToServer = isset($post['save_to_server']);
        $file = $files['excel'] ?? null;

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['type' => 'danger', 'title' => 'لم يتم رفع الملف', 'message' => 'اختاري ملف Excel أو CSV ثم أعيدي المحاولة.', 'errors' => []];
        }

        $originalName = basename((string)$file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['csv', 'txt', 'xls', 'xlsx'];

        if (!in_array($extension, $allowed, true)) {
            return ['type' => 'danger', 'title' => 'صيغة غير مدعومة', 'message' => 'الصيغ المدعومة هي CSV و XLS و XLSX.', 'errors' => []];
        }

        try {
            $readPath = (string)$file['tmp_name'];
            $storedPath = '';

            if ($saveToServer) {
                $uploadDir = dirname(__DIR__) . '/uploads/plant_imports';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $safeName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $originalName);
                $storedPath = $uploadDir . '/' . $safeName;

                if (!move_uploaded_file((string)$file['tmp_name'], $storedPath)) {
                    throw new RuntimeException('تعذر حفظ نسخة الملف على السيرفر. تأكدي من صلاحية مجلد uploads.');
                }

                $readPath = $storedPath;
            }

            $rows = pi_parse_uploaded_file($readPath, $originalName);
            if (!$rows) {
                throw new RuntimeException('الملف لا يحتوي على بيانات. تأكدي من وجود صف العناوين وصفوف البيانات.');
            }

            $conn->begin_transaction();
            $summary = pi_import_rows($conn, $category, $rows);
            $conn->commit();

            $message = 'تم استيراد ' . $summary['success'] . ' سجل بنجاح، وفشل ' . $summary['failed'] . ' سجل.';
            if ($storedPath !== '') {
                $message .= ' وتم حفظ نسخة من الملف على السيرفر.';
            }

            return [
                'type' => $summary['failed'] > 0 ? 'warning' : 'success',
                'title' => 'تمت معالجة الملف',
                'message' => $message,
                'errors' => $summary['errors'],
            ];
        } catch (Throwable $e) {
            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
            }

            return ['type' => 'danger', 'title' => 'تعذر استيراد البيانات', 'message' => $e->getMessage(), 'errors' => []];
        }
    }
}
