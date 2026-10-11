<?php

session_start();

include 'includes/db.php';
include 'includes/log_activity.php';

$id   = $_POST['id'] ?? '';
$name = $_POST['plant_name'] ?? '';
$type = $_POST['plant_type'] ?? '';
$desc = $_POST['description'] ?? '';

$conn->query("
UPDATE vegetation_cover_admin 
SET 
plant_name='$name',
plant_type='$type',
description='$desc'
WHERE id=$id
");

logActivity(
    $conn,
    $_SESSION['user_id'],
    'Edit Vegetation',
    'تم تعديل بيانات النبات '
    . $name .
    ' من نوع '
    . $type
);

header("Location: vegetation_admin.php");

exit();

?>