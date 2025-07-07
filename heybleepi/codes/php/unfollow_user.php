<?php 
  session_start();
  require_once 'configuration.php';

  header('Content-Type: application/json');

  if(!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
  }

  $user_id = $_SESSION['id'];
  $target_user_id = $_POST['user_id'];

  if (!$target_user_id || $user_id == $target_user_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
  }

  $query = $conn->prepare("DELETE FROM follow WHERE following_id = ? AND follower_id = ?");
  $query->bind_param("ii", $target_user_id, $user_id);
  $query->execute();  

  if ($query->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Unfollowed Success']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Not following or already unfollowed.']);
  }

  $query->close();
?>