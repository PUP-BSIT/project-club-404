<?php
session_start();
require_once 'configuration.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
  $post_id = intval($_POST['post_id']);

  // Ensure only the post owner can delete
  $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
  $stmt->bind_param("ii", $post_id, $_SESSION['id']);
  $stmt->execute();
}

header("Location: dashboard.php");
exit();
?>