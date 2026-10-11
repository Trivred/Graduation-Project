<?php

session_start();

include 'includes/db.php';
include 'includes/log_activity.php';

$id = $_POST['id'];
$benefit = $_POST['benefit_type'];
$desc = $_POST['description'];
$lat = $_POST['latitude'];
$lng = $_POST['longitude'];

$conn->query("
UPDATE plant_benefits SET
benefit_type='$benefit',
description='$desc',
latitude='$lat',
longitude='$lng'
WHERE id=$id
");

logActivity(
    $conn,
    $_SESSION['user_id'],
    'Edit Beneficial Plant',
    'تم تعديل بيانات النباتات النافعة - نوع الفائدة: '
    . $benefit
);

header("Location: admin_beneficial.php");

exit();

?>