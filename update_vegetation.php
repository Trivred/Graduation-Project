<?php

session_start();

include 'includes/db.php';
include 'includes/log_activity.php';

$id = $_POST['id'];

$query = "UPDATE vegetation_cover_admin SET

plant_name='{$_POST['plant_name']}',
plant_count='{$_POST['plant_count']}',
region_name='{$_POST['region_name']}',
year='{$_POST['year']}',
created_at='{$_POST['created_at']}'

WHERE id='$id'";

$conn->query($query);

logActivity(
    $conn,
    $_SESSION['user_id'],
    'Edit Vegetation',
    'تم تعديل بيانات الغطاء النباتي للنبات '
    . $_POST['plant_name']
    . ' في منطقة '
    . $_POST['region_name']
);

header("Location: admin_planet_management.php");

exit();

?>