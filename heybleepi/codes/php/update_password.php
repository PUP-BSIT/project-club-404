<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit();
}

$currentId = $_SESSION['id'];
$currentUsername = $_SESSION['username'];
$currentPassword = $_SESSION['password'];

$currentPasswordInput = $_POST['password'];
$newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

if(password_verify($currentPasswordInput, $currentPassword)) {
  $sql = "UPDATE users 
          SET password = '${newPassword}' 
          WHERE id = ${currentId}";
  $result = mysqli_query($conn, $sql);
}

if(!$result) {
   echo "Error:" . $sql . "<br>" . mysqli_error($conn); 
//   echo "Error occurred.";
}

echo "Password change success";
$_SESSION['password'] = $newPassword; 
mysqli_close($conn);
?>