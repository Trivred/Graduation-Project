<?php

session_start();

include 'includes/db.php';
include 'includes/log_activity.php';

$id = $_POST['id'];
$name = $_POST['plant_name'];
$rarity = $_POST['rarity_level'];
$status = $_POST['conservation_status'];
$lat = $_POST['latitude'];
$lng = $_POST['longitude'];

$conn->query("
UPDATE rare_plants SET
plant_name='$name',
rarity_level='$rarity',
conservation_status='$status',
latitude='$lat',
longitude='$lng'
WHERE id=$id
");

logActivity(
    $conn,
    $_SESSION['user_id'],
    'Edit Rare Plant',
    'تم تعديل بيانات النبات النادر '
    . $name
);

header("Location: admin_rare.php");

exit();

?>