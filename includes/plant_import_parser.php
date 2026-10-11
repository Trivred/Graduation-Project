<?php
/**
 * File parsing helpers for CSV, XLS Spreadsheet XML, and simple XLSX.
 */

if (!function_exists('pi_clean_text')) {
    function pi_clean_text($value): string
    {
        $value = (string)$value;
        $value = str_replace("\xEF\xBB\xBF", '', $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        return trim($value);
    }
}

if (!function_exists('pi_normalize_header')) {
    function pi_normalize_header(string $header): string
    {
        $header = mb_strtolower(pi_clean_text($header), 'UTF-8');
        return str_replace([' ', '_', '-', '/', '\\', '.', ':', '(', ')', '[', ']'], '', $header);
    }
}

if (!function_exists('pi_header_map')) {
    function pi_header_map(string $header): ?string
    {
        static $map = null;

        if ($map === null) {
            $aliases = [
                'plant_name' => ['plantname', 'localnamear', 'plant', 'اسمالنبات', 'اسم النبات', 'النبات'],
                'scientific_name' => ['scientificname', 'الاسمالعلمي', 'الاسم العلمي'],
                'region_name' => ['regionname', 'region', 'اسمالمنطقة', 'اسم المنطقة', 'المنطقة'],
                'region_id' => ['regionid', 'رقمالمنطقة', 'رقم المنطقة', 'معرفالمنطقة'],
                'plant_type' => ['planttype', 'category', 'نوعالنبات', 'نوع النبات', 'الفئة'],
                'plant_count' => ['plantcount', 'count', 'quantity', 'عددالنباتات', 'عدد النباتات', 'العدد'],
                'year' => ['year', 'السنة', 'عام'],
                'latitude' => ['latitude', 'lat', 'خطالعرض', 'خط العرض'],
                'longitude' => ['longitude', 'lng', 'lon', 'خطالطول', 'خط الطول'],
                'benefit_type' => ['benefittype', 'benefit', 'نوعالفائدة', 'نوع الفائدة', 'الفائدة'],
                'description' => ['description', 'desc', 'الوصف', 'ملاحظات', 'تفاصيل'],
                'toxicity_level' => ['toxicitylevel', 'toxicity', 'درجةالسمية', 'درجة السمية', 'مستوىالسمية'],
                'symptoms' => ['symptoms', 'الأعراض', 'الاعراض', 'اعراض'],
                'rarity_level' => ['raritylevel', 'rarity', 'درجةالندرة', 'درجة الندرة', 'مستوىالندرة'],
                'conservation_status' => ['conservationstatus', 'حالةالحفظ', 'حالة الحفظ', 'الحالة'],
                'disease_name' => ['diseasename', 'name', 'اسمالمرض', 'اسم المرض', 'المرض', 'الوباء'],
                'disease_type' => ['diseasetype', 'نوعالمرض', 'نوع المرض', 'نوعالوباء', 'نوع الوباء'],
                'severity' => ['severity', 'الشدة', 'خطورة', 'درجةالخطورة', 'درجة الخطورة'],
                'affected_crops' => ['affectedcrops', 'المحاصيلالمتأثرة', 'المحاصيل المتأثرة', 'النباتاتالمتأثرة'],
                'transmission_mode' => ['transmissionmode', 'طريقةالانتقال', 'طريقة الانتقال', 'انتقال'],
                'infected_area_km2' => ['infectedareakm2', 'infectedarea', 'المساحةالمصابة', 'المساحة المصابة'],
                'spread_percentage' => ['spreadpercentage', 'spread', 'نسبةالانتشار', 'نسبة الانتشار'],
            ];

            $map = [];
            foreach ($aliases as $key => $items) {
                foreach ($items as $item) {
                    $map[pi_normalize_header($item)] = $key;
                }
            }
        }

        return $map[pi_normalize_header($header)] ?? null;
    }
}

if (!function_exists('pi_rows_from_matrix')) {
    function pi_rows_from_matrix(array $matrix): array
    {
        if (!$matrix) {
            return [];
        }

        $headers = array_shift($matrix);
        $mappedHeaders = [];

        foreach ($headers as $header) {
            $mappedHeaders[] = pi_header_map((string)$header);
        }

        $rows = [];
        foreach ($matrix as $rawRow) {
            $row = [];
            foreach ($rawRow as $index => $value) {
                $key = $mappedHeaders[$index] ?? null;
                if ($key) {
                    $row[$key] = pi_clean_text($value);
                }
            }

            if ($row && count(array_filter($row, static fn($v) => pi_clean_text($v) !== '')) > 0) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('pi_detect_csv_delimiter')) {
    function pi_detect_csv_delimiter(string $path): string
    {
        $line = (string)file_get_contents($path, false, null, 0, 4096);
        $delimiters = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($delimiters);
        return array_key_first($delimiters) ?: ',';
    }
}

if (!function_exists('pi_parse_csv')) {
    function pi_parse_csv(string $path): array
    {
        $rows = [];
        $delimiter = pi_detect_csv_delimiter($path);
        $handle = fopen($path, 'r');

        if (!$handle) {
            throw new RuntimeException('تعذر قراءة ملف CSV.');
        }

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count(array_filter($data, static fn($v) => pi_clean_text($v) !== '')) > 0) {
                $rows[] = $data;
            }
        }

        fclose($handle);
        return pi_rows_from_matrix($rows);
    }
}

if (!function_exists('pi_parse_xls_xml')) {
    function pi_parse_xls_xml(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            throw new RuntimeException('تعذر قراءة ملف Excel.');
        }

        $matrix = [];
        preg_match_all('/<Row[^>]*>(.*?)<\/Row>/si', $content, $rowMatches);

        if ($rowMatches[1]) {
            foreach ($rowMatches[1] as $rowXml) {
                preg_match_all('/<Data[^>]*>(.*?)<\/Data>/si', $rowXml, $cellMatches);
                $cells = array_map(static function ($cell) {
                    return pi_clean_text(html_entity_decode(strip_tags($cell), ENT_QUOTES, 'UTF-8'));
                }, $cellMatches[1]);
                if ($cells) {
                    $matrix[] = $cells;
                }
            }
        }

        if (!$matrix) {
            throw new RuntimeException('صيغة XLS غير مدعومة. استخدمي القالب الموجود في الصفحة أو ارفعي CSV.');
        }

        return pi_rows_from_matrix($matrix);
    }
}

if (!function_exists('pi_excel_column_index')) {
    function pi_excel_column_index(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }
}

if (!function_exists('pi_parse_xlsx')) {
    function pi_parse_xlsx(string $path): array
    {
        if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
            throw new RuntimeException('رفع XLSX يحتاج تفعيل ZipArchive و SimpleXML في PHP. الحل الأسهل: احفظي الملف CSV ثم ارفعيه.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('تعذر فتح ملف XLSX.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $shared = simplexml_load_string($sharedXml);
            if ($shared) {
                foreach ($shared->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string)$si->t;
                    } else {
                        foreach ($si->r as $run) {
                            $text .= (string)$run->t;
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('لم يتم العثور على الورقة الأولى داخل ملف XLSX.');
        }

        $sheet = simplexml_load_string($sheetXml);
        if (!$sheet) {
            throw new RuntimeException('تعذر قراءة بيانات XLSX.');
        }

        $matrix = [];
        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            $lastIndex = 0;

            foreach ($row->c as $cell) {
                $ref = (string)$cell['r'];
                $letters = preg_replace('/\d+/', '', $ref);
                $index = pi_excel_column_index($letters);

                while ($lastIndex < $index) {
                    $cells[] = '';
                    $lastIndex++;
                }

                $value = '';
                if (isset($cell->v)) {
                    $value = (string)$cell->v;
                    if ((string)$cell['t'] === 's') {
                        $value = $sharedStrings[(int)$value] ?? '';
                    }
                } elseif (isset($cell->is->t)) {
                    $value = (string)$cell->is->t;
                }

                $cells[] = pi_clean_text($value);
                $lastIndex++;
            }

            if (count(array_filter($cells, static fn($v) => pi_clean_text($v) !== '')) > 0) {
                $matrix[] = $cells;
            }
        }

        return pi_rows_from_matrix($matrix);
    }
}

if (!function_exists('pi_parse_uploaded_file')) {
    function pi_parse_uploaded_file(string $path, string $name): array
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv', 'txt' => pi_parse_csv($path),
            'xls' => pi_parse_xls_xml($path),
            'xlsx' => pi_parse_xlsx($path),
            default => throw new RuntimeException('صيغة الملف غير مدعومة. الصيغ المدعومة: CSV, XLS, XLSX.'),
        };
    }
}
