<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['id']) || !isset($_POST['comment_id'])) {
  header("Location: dashboard.php");
  exit();
}

$comment_id = intval($_POST['comment_id']);
$user_id = $_SESSION['id'];

$stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $comment_id, $user_id);
$stmt->execute();
$stmt->close();

header("Location: profile.php");
exit();
?>