<?php
// =============================================
//  StudyVault — Database Configuration
//  File: config/db.php
//  HOW TO USE: include_once '../config/db.php';
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // XAMPP default
define('DB_PASS', '');            // XAMPP default (empty password)
define('DB_NAME', 'study_platform');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
        exit();
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}
?>