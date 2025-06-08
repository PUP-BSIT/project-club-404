<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit();
}

// Get the current username using the session.
$currentId= $_SESSION['id'];

// Get form values
$updatedFirstName = $_POST['first_name'] ?? "";
$updatedMiddleName = $_POST['middle_name'] ?? "";
$updatedLastName = $_POST['last_name'] ?? "";
$updatedUsername = $_POST['user_name'] ?? "";
$updatedEmail = $_POST['email'] ?? "";
$updatedBirthdate = $_POST['birthdate'] ?? "";

// Update query
$sql = "UPDATE users 
        SET user_name ='${updatedUsername}', 
        first_name='${updatedFirstName}', 
        middle_name='${updatedMiddleName}', 
        last_name='${updatedLastName}',
        email='${updatedEmail}', 
        birthdate='${updatedBirthdate}' 
        WHERE id='${currentId}'";
      
if(!mysqli_query($conn, $sql)) {
  echo "Error:" . $sql . "<br>" . mysqli_error($conn); 
  echo "Failed to update account information";
}

$_SESSION['username'] = $updatedUsername;
$_SESSION['email'] = $updatedEmail;
$_SESSION['first_name'] = $updatedFirstName;
$_SESSION['middle_name'] = $updatedMiddleName;
$_SESSION['last_name'] = $updatedLastName;

echo "Account Information Updated!";
mysqli_close($conn);
?>