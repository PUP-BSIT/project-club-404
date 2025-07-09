<?php
session_start();
require_once 'configuration.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
  $post_id = intval($_POST['post_id']);
  $user_id = $_SESSION['id'];

  // Log the received post_id for debugging
  file_put_contents("delete_debug.txt", "Deleting post $post_id for user $user_id\n", FILE_APPEND);

  $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
  $stmt->bind_param("ii", $post_id, $user_id);
  $stmt->execute();

  // Log result
  file_put_contents("delete_debug.txt", "Affected rows: " . $stmt->affected_rows . "\n", FILE_APPEND);

  $stmt->close();
}

header("Location: dashboard.php");
exit();
?>