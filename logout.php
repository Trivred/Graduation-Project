<?php

session_start();

include 'includes/db.php';
include 'includes/log_activity.php';

$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {

    logActivity(
        $conn,
        $user_id,
        'Logout',
        'User logged out from the system'
    );
}

session_unset();

session_destroy();

header("Location: login_register.php");

exit();

?>