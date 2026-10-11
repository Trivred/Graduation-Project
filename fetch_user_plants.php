<?php
include 'includes/db.php';

$year = $_GET['year'] ?? 'all';
$search = $_GET['search'] ?? '';

$where = [];

if($year != 'all'){
$where[] = "year = '$year'";
}

if($search != ''){
$where[] = "(region_name LIKE '%$search%' OR plant_name LIKE '%$search%')";
}

$whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "
SELECT region_name, plant_name, plant_count, year
FROM vegetation_cover_admin
$whereSQL
ORDER BY plant_count DESC
";

$result = $conn->query($query);

while($row = $result->fetch_assoc()){

echo "<tr>
<td>{$row['year']}</td>
<td>{$row['region_name']}</td>
<td>{$row['plant_name']}</td>
<td>".number_format($row['plant_count'])."</td>
</tr>";
}