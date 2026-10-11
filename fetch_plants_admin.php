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
SELECT MIN(id) as id, region_name, plant_name, plant_count, created_at, year
FROM vegetation_cover_admin
$whereSQL
GROUP BY region_name, plant_name, plant_count, year
ORDER BY created_at DESC
";

$result = $conn->query($query);

while($row = $result->fetch_assoc()){

$date = date("Y-m-d", strtotime($row['created_at']));

echo "<tr>
<td>{$row['region_name']}</td>
<td>{$row['plant_name']}</td>
<td><span class='count-badge'>{$row['plant_count']}</span></td>
<td><span class='year-badge'>{$row['year']}</span></td>
<td><span class='date-badge'>{$date}</span></td>

<td>
<button class='btn-edit'
data-id='{$row['id']}'
data-name='{$row['plant_name']}'
data-count='{$row['plant_count']}'
data-region='{$row['region_name']}'
data-year='{$row['year']}'
data-date='{$row['created_at']}'>
✏️
</button>

<a class='btn-delete' href='delete_vegetation.php?id={$row['id']}' onclick='return confirm(\"متأكدة؟\")'>🗑️</a>
</td>

</tr>";
}