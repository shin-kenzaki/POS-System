<?php
// Database connection parameters
$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "pos_inventory";

// Establish database connection
try {
    $conn = new mysqli($host, $user, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
