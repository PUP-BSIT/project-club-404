<?php
session_start();
require_once 'configuration.php';

if (isset($_POST['post_id'], $_SESSION['id'])) {
  $post_id = intval($_POST['post_id']);
  $user_id = $_SESSION['id'];

  $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
  $stmt->bind_param("ii", $post_id, $user_id);
  $stmt->execute();
}

header("Location: profile.php");
exit();
?>