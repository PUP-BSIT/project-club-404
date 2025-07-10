<?php
session_start();
require_once __DIR__ . '/configuration.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_POST['post_id'])) {
  echo json_encode(['success' => false]);
  exit;
}

$user_id = $_SESSION['id'];
$post_id = intval($_POST['post_id']);

// Check if already liked
$check = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
$check->bind_param("ii", $user_id, $post_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  // Unlike
  $delete = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
  $delete->bind_param("ii", $user_id, $post_id);
  $delete->execute();
  $liked = false;
} else {
  // Like
  $insert = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
  $insert->bind_param("ii", $user_id, $post_id);
  $insert->execute();
  $liked = true;

  // Get post owner
  $getOwner = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
  $getOwner->bind_param("i", $post_id);
  $getOwner->execute();
  $ownerResult = $getOwner->get_result();
  $owner = $ownerResult->fetch_assoc()['user_id'] ?? null;
  $getOwner->close();

  if ($owner && $owner != $user_id) {
    $type = 'like';
    $notif = $conn->prepare("INSERT INTO notifications (user_id, actor_id, type, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $notif->bind_param("iis", $owner, $user_id, $type);
    $notif->execute();
    $notif->close();
  }
}

// Get updated count
$result = $conn->query("SELECT COUNT(*) AS total FROM likes WHERE post_id = $post_id");
$total = $result->fetch_assoc()['total'] ?? 0;

echo json_encode([
  'success' => true,
  'liked' => $liked,
  'total' => $total
]);