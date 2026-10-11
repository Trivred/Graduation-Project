<?php
$conn = new mysqli("localhost","root","","vegetation_platform_new");
$conn->set_charset("utf8mb4");

$year = intval($_GET['year']);

$query = "
SELECT region_id, intensity_score
FROM region_density_over_time
WHERE year = $year
";

$result = $conn->query($query);

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = $row;
}
echo json_encode($data);
?>