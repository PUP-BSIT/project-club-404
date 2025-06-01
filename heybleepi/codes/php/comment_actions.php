<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['id'])) {
  http_response_code(401);
  exit("Unauthorized");
}

$commentId = intval($_POST['comment_id'] ?? 0);
$action = $_POST['action'] ?? '';
$userId = $_SESSION['id'];

if ($action === 'edit' && isset($_POST['new_text'])) {
  $newText = trim($_POST['new_text']);
  $stmt = $conn->prepare("UPDATE comments SET comment_text = ? WHERE id = ? AND user_id = ?");
  $stmt->bind_param("sii", $newText, $commentId, $userId);
  $stmt->execute();
  echo htmlspecialchars($newText);
} elseif ($action === 'delete') {
  $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
  $stmt->bind_param("ii", $commentId, $userId);
  $stmt->execute();
  echo "deleted";
}
?>