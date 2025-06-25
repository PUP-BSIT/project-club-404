<?php
// Set PHP timezone for date() and time() functions
date_default_timezone_set('Asia/Manila');

$database = "srv678.hstgr.io";
$username = "u937067793_club_404_mem";
$password = "Club-404-!_!";
$dbname = "u937067793_club_404";

// Create MySQL connection
$conn = new mysqli($database, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set MySQL session timezone to Asia/Manila (UTC+8)
if (!$conn->query("SET time_zone = '+08:00'")) {
    die("Failed to set MySQL timezone: " . $conn->error);
}
?>
