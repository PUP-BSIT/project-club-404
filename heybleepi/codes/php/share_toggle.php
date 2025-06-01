<?php
session_start();
require_once 'configuration.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_POST['post_id'])) {
  echo json_encode(['success' => false]);
  exit;
}

$user_id = $_SESSION['id'];
$post_id = intval($_POST['post_id']);

$check = $conn->prepare("SELECT id FROM shares WHERE user_id = ? AND post_id = ?");
$check->bind_param("ii", $user_id, $post_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  $delete = $conn->prepare("DELETE FROM shares WHERE user_id = ? AND post_id = ?");
  $delete->bind_param("ii", $user_id, $post_id);
  $delete->execute();
  $shared = false;
} else {
  $insert = $conn->prepare("INSERT INTO shares (user_id, post_id) VALUES (?, ?)");
  $insert->bind_param("ii", $user_id, $post_id);
  $insert->execute();
  $shared = true;
}

$result = $conn->query("SELECT COUNT(*) AS total FROM shares WHERE post_id = $post_id");
$total = $result->fetch_assoc()['total'] ?? 0;

echo json_encode([
  'success' => true,
  'shared' => $shared,
  'total' => $total
]);