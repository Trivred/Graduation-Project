<?php

function logActivity($conn, $user_id, $action, $details){

    $sql = "INSERT INTO activity_logs
    (user_id, action, details)
    VALUES
    ('$user_id', '$action', '$details')";

    mysqli_query($conn, $sql);
}
?>