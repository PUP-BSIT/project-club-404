<?php
$database = "localhost";
$username = "u937067793_club_404_mem";
$password = "Club-404-!_!";
$dbname = "u937067793_club_404";

// Create connection
$conn = new mysqli($database, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>