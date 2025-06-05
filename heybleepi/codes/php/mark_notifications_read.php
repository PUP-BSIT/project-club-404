<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['id'])) {
  header("Location: index.php");
  exit();
}

$user_id = $_SESSION['id'];

// Update all unread notifications to read
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

header("Location: dashboard.php");
exit();
?>