<?php
session_start();
require_once 'configuration.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_id'], $_POST['updated_comment'])) {
  $comment_id = intval($_POST['comment_id']);
  $user_id = $_SESSION['id'];
  $updated_comment = trim($_POST['updated_comment']);

  if (!empty($updated_comment)) {
    $stmt = $conn->prepare("UPDATE comments SET comment_text = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $updated_comment, $comment_id, $user_id);
    $stmt->execute();
    $stmt->close();
  }

  header("Location: dashboard.php");
  exit();
}
?>