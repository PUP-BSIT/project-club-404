<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['share_post_id'])) {
  header("Location: dashboard.php");
  exit();
}

$user_id = $_SESSION['id'];
$shared_post_id = intval($_POST['share_post_id']);

// Prevent duplicate shares
$check = $conn->prepare("SELECT id FROM posts WHERE user_id = ? AND shared_post_id = ?");
$check->bind_param("ii", $user_id, $shared_post_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
  $insert = $conn->prepare("INSERT INTO posts (user_id, content, shared_post_id) VALUES (?, '', ?)");
  $insert->bind_param("ii", $user_id, $shared_post_id);
  $insert->execute();
  $insert->close();
}

$check->close();

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>