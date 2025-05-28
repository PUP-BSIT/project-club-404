<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit();
}
// Get raw values.
parse_str(file_get_contents('php://input'), $_PATCH);

// Get the current username using the session.
$currentUsername = $_SESSION['username'];

// Get form values
$updatedFirstName = $_PATCH['first_name'] ?? "";
$updatedMiddleName = $_PATCH['middle_name'] ?? "";
$updatedLastName = $_PATCH['last_name'] ?? "";
$updatedUsername = $_PATCH['user_name'] ?? "";
$updatedEmail = $_PATCH['email'] ?? "";
$updatedBirthdate = $_PATCH['birthdate'] ?? "";

// Update query
$sql = "UPDATE users 
        SET user_name ='${updatedUsername}', 
        first_name='${updatedFirstName}', 
        middle_name='${updatedMiddleName}', 
        last_name='${updatedLastName}',
        email='${updatedEmail}', 
        birthdate='${updatedBirthdate}' 
        WHERE user_name='${currentUsername}'";
      
if(!mysqli_query($conn, $sql)) {
  // echo "Error:" . $sql . "<br>" . mysqli_error($conn); 
  echo "Failed to update account information";
}

echo "Account Information Updated!";
$_SESSION['username'] = $updatedUsername;
$_SESSION['email'] = $updatedEmail;
$_SESSION['first_name'] = $updatedFirstName;
$_SESSION['middle_name'] = $updatedMiddleName;
$_SESSION['last_name'] = $updatedLastName;
mysqli_close($conn);
?>