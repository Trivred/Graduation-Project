<?php
/**
 * Central database connection file.
 * Keep credentials and connection settings here only.
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'vegetation_platform_new';

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
date_default_timezone_set('Asia/Riyadh');
