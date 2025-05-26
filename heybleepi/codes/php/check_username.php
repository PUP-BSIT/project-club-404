<?php
session_start();
require_once 'users.php';

if (isset($_GET['username'])) {
    $username = trim($_GET['username']);
    $username = mysqli_real_escape_string($conn, $username);

    $sql = "SELECT id FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        echo 'taken';
    } else {
        echo 'available';
    }
}
?>