<?php
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "tms_database";

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed");
}
?>
