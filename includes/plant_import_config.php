<?php
/**
 * Plant import configuration.
 * This file contains only category labels, required columns, and template examples.
 */

if (!function_exists('plant_import_categories')) {
    function plant_import_categories(): array
    {
        return [
            'beneficial' => [
                'label' => 'نباتات نافعة',
                'table' => 'plant_benefits',
                'icon' => 'bi-heart-fill',
                'color' => '#16a34a',
            ],
            'toxic' => [
                'label' => 'نباتات سامة',
                'table' => 'toxic_plants',
                'icon' => 'bi-radioactive',
                'color' => '#dc2626',
            ],
            'rare' => [
                'label' => 'نباتات نادرة',
                'table' => 'rare_plants',
                'icon' => 'bi-star-fill',
                'color' => '#7c3aed',
            ],
            'disease' => [
                'label' => 'أوبئة وأمراض',
                'table' => 'plant_diseases',
                'icon' => 'bi-virus2',
                'color' => '#ea580c',
            ],
            'vegetation' => [
                'label' => 'غطاء نباتي عام',
                'table' => 'vegetation_cover_admin',
                'icon' => 'bi-tree-fill',
                'color' => '#0f766e',
            ],
        ];
    }
}

if (!function_exists('plant_import_valid_category')) {
    function plant_import_valid_category(?string $category): string
    {
        $category = trim((string)$category);
        return array_key_exists($category, plant_import_categories()) ? $category : 'beneficial';
    }
}

if (!function_exists('plant_import_columns')) {
    function plant_import_columns(): array
    {
        return [
            'beneficial' => [
                'plant_name',
                'scientific_name',
                'region_name',
                'region_id',
                'benefit_type',
                'description',
                'latitude',
                'longitude',
                'plant_count',
                'year',
            ],
            'toxic' => [
                'plant_name',
                'scientific_name',
                'region_name',
                'region_id',
                'toxicity_level',
                'symptoms',
                'latitude',
                'longitude',
                'plant_count',
                'year',
            ],
            'rare' => [
                'plant_name',
                'scientific_name',
                'region_name',
                'region_id',
                'rarity_level',
                'conservation_status',
                'latitude',
                'longitude',
                'plant_count',
                'year',
            ],
            'disease' => [
                'disease_name',
                'disease_type',
                'severity',
                'description',
                'affected_crops',
                'transmission_mode',
                'region_name',
                'region_id',
                'infected_area_km2',
                'spread_percentage',
                'plant_count',
            ],
            'vegetation' => [
                'region_name',
                'plant_name',
                'plant_type',
                'plant_count',
                'year',
            ],
        ];
    }
}

if (!function_exists('plant_import_required_columns')) {
    function plant_import_required_columns(): array
    {
        return [
            'beneficial' => 'plant_name, benefit_type, description — اختياري: scientific_name, region_name, region_id, latitude, longitude, plant_count, year',
            'toxic' => 'plant_name, toxicity_level, symptoms — اختياري: scientific_name, region_name, region_id, latitude, longitude, plant_count, year',
            'rare' => 'plant_name, rarity_level, conservation_status — اختياري: scientific_name, region_name, region_id, latitude, longitude, plant_count, year',
            'disease' => 'disease_name, disease_type, severity, description — اختياري: affected_crops, transmission_mode, region_name, region_id, infected_area_km2, spread_percentage, plant_count',
            'vegetation' => 'region_name, plant_name, plant_type, plant_count, year',
        ];
    }
}

if (!function_exists('plant_import_examples')) {
    function plant_import_examples(): array
    {
        $year = date('Y');

        return [
            'beneficial' => [
                ['السدر', 'Ziziphus spina-christi', 'منطقة الرياض', '', 'طبي / غذائي', 'نبات نافع يستخدم في الغذاء والظل ودعم التنوع الحيوي', '24.713600', '46.675300', '120', $year],
                ['الأراك', 'Salvadora persica', 'منطقة مكة المكرمة', '', 'طبي', 'يستخدم تقليدياً للسواك وله أهمية بيئية', '21.389100', '39.857900', '75', $year],
            ],
            'toxic' => [
                ['الدفلى', 'Nerium oleander', 'منطقة عسير', '', '85.00', 'غثيان ودوخة واضطراب نبضات القلب', '18.216400', '42.505300', '30', $year],
                ['الحرمل', 'Peganum harmala', 'منطقة القصيم', '', '60.00', 'تهيج وأعراض عصبية عند الاستخدام الخاطئ', '26.207800', '43.483700', '45', $year],
            ],
            'rare' => [
                ['الغاف', 'Prosopis cineraria', 'منطقة الشرقية', '', 'عالية', 'مهدد محلياً ويحتاج متابعة وحماية', '26.420700', '50.088800', '18', $year],
                ['العرعر', 'Juniperus procera', 'منطقة الباحة', '', 'متوسطة', 'نبات جبلي يحتاج مراقبة', '20.012900', '41.467700', '22', $year],
            ],
            'disease' => [
                ['صدأ القمح', 'فطري', 'متوسطة', 'مرض يصيب الأوراق ويقلل جودة المحصول', 'القمح والشعير', 'الرياح والرطوبة', 'منطقة القصيم', '', '12.5', '18.4', '250'],
                ['البياض الدقيقي', 'فطري', 'منخفضة', 'بقع بيضاء على الأوراق وتزداد مع الرطوبة', 'الخضروات والورقيات', 'الرطوبة والانتقال بالهواء', 'منطقة الرياض', '', '8.0', '10.2', '160'],
            ],
            'vegetation' => [
                ['منطقة الرياض', 'السدر', 'نافعة', '120', $year],
                ['منطقة عسير', 'العرعر', 'نادرة', '35', $year],
            ],
        ];
    }
}
