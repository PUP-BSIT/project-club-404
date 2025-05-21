<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "loginregister";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

//Database: loginregister
//Table: users
//Column: id, username, first_name, middle_name, last_name, birthdate, email, password
?>

