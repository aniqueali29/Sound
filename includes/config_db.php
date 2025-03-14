<?php

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sound";

// Ensure MySQLi extension is enabled
if (!function_exists('mysqli_connect')) {
    die("MySQLi extension is not enabled.");
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
