<?php
// Database credentials
$host = "localhost";        // XAMPP default host
$user = "root";             // XAMPP default user
$password = "";             // XAMPP default password (leave blank)
$database = "online_learning";  // Your database name in phpMyAdmin

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
