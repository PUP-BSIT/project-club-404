<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['share_post_id'])) {
  header("Location: dashboard.php");
  exit();
}

$user_id = $_SESSION['id'];
$shared_post_id = intval($_POST['share_post_id']);
$location = isset($_POST['location']) ? trim($_POST['location']) : null; // Get the location input

// Prevent duplicate shares
$check = $conn->prepare("SELECT id FROM posts WHERE user_id = ? AND shared_post_id = ?");
$check->bind_param("ii", $user_id, $shared_post_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
  // Include location in the insert query
  $insert = $conn->prepare("INSERT INTO posts (user_id, content, shared_post_id, location) VALUES (?, '', ?, ?)");
  $insert->bind_param("iis", $user_id, $shared_post_id, $location);
  $insert->execute();
  $new_post_id = $insert->insert_id;
  $insert->close();

  // Copy all media from the original post to the new shared post
  $mediaQuery = $conn->prepare("SELECT file_path, media_type FROM post_media WHERE post_id = ?");
  $mediaQuery->bind_param("i", $shared_post_id);
  $mediaQuery->execute();
  $mediaResult = $mediaQuery->get_result();
  while ($media = $mediaResult->fetch_assoc()) {
    $insertMedia = $conn->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, ?)");
    $insertMedia->bind_param("iss", $new_post_id, $media['file_path'], $media['media_type']);
    $insertMedia->execute();
    $insertMedia->close();
  }
  $mediaQuery->close();
}

$check->close();

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>