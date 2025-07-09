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
  $currentDateTime = date("Y-m-d H:i:s");

  if (!$target_user_id || $user_id == $target_user_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
  }

  $check = $conn->prepare("SELECT 1 FROM follow WHERE follower_id = ? AND following_id = ?");
  $check->bind_param("ii", $user_id, $target_user_id);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Already following.']);
    $check->close();
    exit;
  }
  $check->close();

  $query = $conn->prepare("INSERT INTO follow (follower_id, following_id, created_at) VALUES(?, ?, ?)");
  $query->bind_param("iis", $user_id, $target_user_id, $currentDateTime);
  $query->execute();  

  if ($query->affected_rows > 0) {
   // Notify the followed user.
   $type = 'follow';
   $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, actor_id, type, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
   $notifStmt->bind_param("iis", $target_user_id, $user_id, $type);
   $notifStmt->execute();
   $notifStmt->close();
   
    echo json_encode(['status' => 'success', 'message' => 'Followed successfully']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Already following or failed.']);
  }

  $query->close();
?>

