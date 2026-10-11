<?php

session_start();

include 'includes/db.php';
include 'includes/log_activity.php';

$id = $_GET['id'];


$userQuery = $conn->prepare("SELECT username FROM users WHERE user_id=?");

$userQuery->bind_param("i", $id);

$userQuery->execute();

$result = $userQuery->get_result();

$user = $result->fetch_assoc();

$username = $user['username'] ?? 'غير معروف';

$stmt = $conn->prepare("UPDATE users SET role='admin' WHERE user_id=?");

$stmt->bind_param("i", $id);

$stmt->execute();

logActivity(
    $conn,
    $_SESSION['user_id'],
    'Change User Role',
    'تم ترقية المستخدم '
    . $username .
    ' إلى مدير'
);

header("Location: manage_users.php");

exit();

?>