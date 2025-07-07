<?php
session_start();
require_once 'configuration.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_POST['post_id'])) {
  echo json_encode(['success' => false, 'error' => 'Missing parameters']);
  exit;
}

$user_id = $_SESSION['id'];
$post_id = intval($_POST['post_id']);
$shared = false;

// Check if user already shared this post
$check = $conn->prepare("SELECT id FROM shares WHERE user_id = ? AND post_id = ?");
$check->bind_param("ii", $user_id, $post_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  // UN-SHARE: Remove share record
  $delete = $conn->prepare("DELETE FROM shares WHERE user_id = ? AND post_id = ?");
  $delete->bind_param("ii", $user_id, $post_id);
  $delete->execute();
  $shared = false;
} else {
  // NEW SHARE
  $insert = $conn->prepare("INSERT INTO shares (user_id, post_id) VALUES (?, ?)");
  $insert->bind_param("ii", $user_id, $post_id);
  $insert->execute();
  $shared = true;

  // 🔍 GET POST OWNER
  $getOwner = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
  $getOwner->bind_param("i", $post_id);
  $getOwner->execute();
  $ownerResult = $getOwner->get_result();
  $ownerData = $ownerResult->fetch_assoc();
  $postOwnerId = $ownerData['user_id'] ?? null;
  $getOwner->close();

  // Only notify if not sharing own post
  if ($postOwnerId && $postOwnerId != $user_id) {
    $type = 'share';

    // INSERT notification with post_id
    $notif = $conn->prepare("INSERT INTO notifications (user_id, actor_id, post_id, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    $notif->bind_param("iiis", $postOwnerId, $user_id, $post_id, $type);
    $notif->execute();
    $notif->close();
  }
}

// Return share count
$countResult = $conn->query("SELECT COUNT(*) AS total FROM shares WHERE post_id = $post_id");
$total = $countResult->fetch_assoc()['total'] ?? 0;

echo json_encode([
  'success' => true,
  'shared' => $shared,
  'total' => $total
]);
?>