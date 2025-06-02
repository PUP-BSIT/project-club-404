<?php
session_start();
require_once 'configuration.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['post_id'], $_POST['new_content'])) {
  $post_id = intval($_POST['post_id']);
  $new_content = trim($_POST['new_content']);

  if (!empty($new_content)) {
    $stmt = $conn->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $new_content, $post_id, $_SESSION['id']);
    $stmt->execute();
  }
}

header("Location: dashboard.php");
exit();
?>