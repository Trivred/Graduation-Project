<?php
$conn = new mysqli("localhost","root","","vegetation_platform_new");
$conn->set_charset("utf8mb4");

$disease_id = $_GET['disease_id'];

$query = "
SELECT 
r.region_id,
r.region_name_ar,
hp.latitude,
hp.longitude,

COUNT(DISTINCT pt.plant_id) as total_plants,
AVG(
    CASE 
        WHEN pd.density_level = 'عالية' THEN 80
        WHEN pd.density_level = 'متوسطة' THEN 50
        ELSE 20
    END
) as avg_density,

SUM(pt.is_toxic) as toxicity

FROM regions r

JOIN heatmap_points hp ON r.region_id = hp.region_id

JOIN plant_distribution pd ON r.region_id = pd.region_id
JOIN plant_types pt ON pd.plant_id = pt.plant_id
JOIN plant_disease_cases pdc ON pt.plant_id = pdc.plant_id

WHERE pdc.disease_id = $disease_id

GROUP BY r.region_id
";

$res = $conn->query($query);

$data = [];

while($row = $res->fetch_assoc()){
    $data[] = [
        "region"=>$row["region_name_ar"],
        "lat"=>(float)$row["latitude"],
        "lon"=>(float)$row["longitude"],
        "total_plants"=>(int)$row["total_plants"],
        "avg_density"=>(float)$row["avg_density"],
        "toxicity"=>(int)$row["toxicity"]
    ];
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);