<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['user_name'])) {
  header("Location: index.php");
  exit();
}

$user_name = $_SESSION['user_name'];

// Get form values
$first_name = $_POST['first_name'] ?? '';
$middle_name = $_POST['middle_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$birthdate = $_POST['birthdate'] ?? '';

// Update query
$sql = "UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, birthdate=? WHERE user_name=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $first_name, $middle_name, $last_name, $email, $birthdate, $user_name);

if ($stmt->execute()) {
  // Success message and redirect
  header("Location: settings.php?status=success");
  exit();
} else {
  echo "Error updating profile: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>