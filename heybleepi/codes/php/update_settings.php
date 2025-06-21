<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit();
}

// Get the current user id using the session.
$currentId= $_SESSION['id'];

// Get form values
$updatedFirstName = $_POST['first_name'] ?? "";
$updatedMiddleName = $_POST['middle_name'] ?? "";
$updatedLastName = $_POST['last_name'] ?? "";
$updatedUsername = $_POST['user_name'] ?? "";
$updatedEmail = $_POST['email'] ?? "";
$updatedBirthdate = $_POST['birthdate'] ?? "";

// Check if the username exists.
$checkQuery = "SELECT id FROM users WHERE user_name = ? AND id != ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("si", $updatedUsername, $currentId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "Username already taken!";
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Update information query
$sql = "UPDATE users 
        SET user_name = '{$updatedUsername}', 
        first_name = '{$updatedFirstName}', 
        middle_name = '{$updatedMiddleName}', 
        last_name = '{$updatedLastName}',
        email = '{$updatedEmail}', 
        birthdate = '{$updatedBirthdate}' 
        WHERE id = '{$currentId}';
        
        UPDATE messages 
        SET user_name = '{$updatedUsername}'
        WHERE user_id = '{$currentId}';
    ";
      
// Handle error.
if(!mysqli_multi_query($conn, $sql)) {
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