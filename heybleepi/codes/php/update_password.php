<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit();
}

$currentId = $_SESSION['id'];

$sql = "SELECT * FROM users WHERE id = {$currentId}";
$result = mysqli_query($conn, $sql);
$currentPassword = null;

if ($row = mysqli_fetch_assoc($result)) {
    $currentPassword = $row['password'];
} else {
    die("Fetch Error.");
}

$currentPasswordInput = $_POST['password'];
$newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

if(password_verify($currentPasswordInput, $currentPassword)) {
  $sql = "UPDATE users 
          SET password = '${newPassword}' 
          WHERE id = ${currentId}";
  $result = mysqli_query($conn, $sql);
} else {
    die("Incorrect Current Password");
}

if(!$result) {
   echo "Error:" . $sql . "<br>" . mysqli_error($conn); 
}

echo "Password change success";
mysqli_close($conn);
?>