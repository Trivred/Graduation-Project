<?php

session_start();

include 'includes/db.php';
include 'includes/log_activity.php';

$id = $_POST['id'];
$name = $_POST['plant_name'];
$symptoms = $_POST['symptoms'];
$lat = $_POST['latitude'];
$lng = $_POST['longitude'];

$conn->query("
UPDATE toxic_plants SET
plant_name='$name',
symptoms='$symptoms',
latitude='$lat',
longitude='$lng'
WHERE id=$id
");

logActivity(
    $conn,
    $_SESSION['user_id'],
    'Edit Toxic Plant',
    'تم تعديل بيانات النبات السام '
    . $name
);

header("Location: admin_toxic.php");

exit();

?>