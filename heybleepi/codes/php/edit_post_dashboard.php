<?php
session_start();
require_once 'configuration.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['new_content'])) {
  $postId = intval($_POST['post_id']);
  $newContent = trim($_POST['new_content']);
  $userId = $_SESSION['id'];

  if (!empty($newContent)) {
    $stmt = $conn->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $newContent, $postId, $userId);
    $stmt->execute();
    $stmt->close();
  }
}

header("Location: dashboard.php");
exit();
?>