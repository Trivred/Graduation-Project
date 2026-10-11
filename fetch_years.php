<?php
include 'includes/db.php';
$result = $conn->query("SELECT DISTINCT year FROM vegetation_cover_admin ORDER BY year");
$years = [];
while($row = $result->fetch_assoc()){
$years[] = $row['year'];
}
echo json_encode($years);