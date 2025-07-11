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

// Check for existing email
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param("si", $updatedEmail, $currentId);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
   echo "Email already taken!";
   $stmt->close();
   $conn->close();
   exit();
}
$stmt->close();

// Update information query
$stmt1 = $conn->prepare("UPDATE users 
                    SET user_name = ?, 
                    first_name = ?, 
                    middle_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    birthdate = ? 
                    WHERE id = ?");
            
$stmt1->bind_param("ssssssi", 
                $updatedUsername, 
                $updatedFirstName, 
                $updatedMiddleName, 
                $updatedLastName, 
                $updatedEmail, 
                $updatedBirthdate, 
                $currentId);

if (!$stmt1->execute()) {
    echo "Failed to update users table: " . $stmt1->error;
    $stmt1->close();
    $conn->close();
    exit();
}
$stmt1->close();

// Update username in messages.
$stmt2 = $conn->prepare("UPDATE messages SET user_name = ? WHERE user_id = ?");
$stmt2->bind_param("si", $updatedUsername, $currentId);
if (!$stmt2->execute()) {
    echo "Failed to update messages table: " . $stmt2->error;
    $stmt2->close();
    $conn->close();
    exit();
}
$stmt2->close();
echo "Changes Saved!";

// Update session information
$_SESSION['username'] = $updatedUsername;
$_SESSION['email'] = $updatedEmail;
$_SESSION['first_name'] = $updatedFirstName;
$_SESSION['middle_name'] = $updatedMiddleName;
$_SESSION['last_name'] = $updatedLastName;
?>