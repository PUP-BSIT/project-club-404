<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'configuration.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['username'];

// Get user_id for current user
$stmt = $conn->prepare("SELECT id FROM users WHERE user_name = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$user_row = $result->fetch_assoc();
$stmt->close();

if (!$user_row) {
    die("User not found.");
}

$user_id = $user_row['id'];

// AJAX handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
  isset($_POST['ajax']) && $_POST['ajax'] == '1') {
  // Delete
  if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM messages
                            WHERE id = ? 
                            AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $success]);
    exit;
  }
  // Edit
  if (isset($_POST['update_id']) && isset($_POST['comment'])) {
    $update_id = intval($_POST['update_id']);
    $msg = trim($_POST['comment']);
    $stmt = $conn->prepare("UPDATE messages
                            SET message = ?
                            WHERE id = ?
                            AND user_id = ?");
    $stmt->bind_param("sii", $msg, $update_id, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $success]);
    exit;
  }
  // Add new message
  if (isset($_POST['comment']) && !isset($_POST['update_id']) && 
    !isset($_POST['delete_id'])) {
    $msg = trim($_POST['comment']);
    if (!empty($msg)) {
      $stmt = $conn->prepare("INSERT INTO messages (user_id, user_name,
                              message, created_at) VALUES (?, ?, ?, NOW())");
      $stmt->bind_param("iss", $user_id, $user, $msg);
      $success = $stmt->execute();
      $new_id = $stmt->insert_id;
      $stmt->close();
      
      // Get the new message with user details
      $stmt = $conn->prepare("SELECT m.*, ud.profile_picture 
                            FROM messages m 
                            LEFT JOIN users u ON m.user_id = u.id 
                            LEFT JOIN user_details ud ON u.id = ud.id_fk 
                            WHERE m.id = ?");
      $stmt->bind_param("i", $new_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $new_message = $result->fetch_assoc();
      $stmt->close();
      
      echo json_encode([
        'success' => $success,
        'message' => $new_message
      ]);
      exit;
    }
  }
}

// Get the latest message ID and update user's last_seen_message_id
$latest_msg_query = $conn->query("SELECT MAX(id) as max_id FROM messages");
$latest_msg = $latest_msg_query->fetch_assoc();
$latest_msg_id = $latest_msg['max_id'] ?? 0;

$update_last_seen = $conn->prepare("UPDATE users
                                    SET last_seen_message_id = ? WHERE id = ?");
$update_last_seen->bind_param("ii", $latest_msg_id, $user_id);
$update_last_seen->execute();
$update_last_seen->close();

// Mark all messages as read for the current user
$update = $conn->prepare("UPDATE messages SET is_read = 1 WHERE user_id = ?
                          AND is_read = 0");
$update->bind_param("i", $user_id);
$update->execute();
$update->close();

// Handle non-AJAX form submission (fallback)
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['ajax'])) {
  if (isset($_POST["comment"]) && trim($_POST["comment"]) !== "") {
    $msg = trim($_POST['comment']);
    // Insert new message
    $stmt = $conn->prepare("INSERT INTO messages (user_id, user_name,
                            message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $user, $msg);
    $stmt->execute();
    $stmt->close();
    header("Location: messages.php");
    exit();
  }
}

// Fetch all messages with user avatars
$sql = "SELECT m.*, ud.profile_picture 
        FROM messages m 
        LEFT JOIN users u ON m.user_id = u.id 
        LEFT JOIN user_details ud ON u.id = ud.id_fk 
        ORDER BY m.created_at DESC";

$result = $conn->query($sql);

if (!$result) {
  die("Query failed: " . $conn->error);
}

$messages = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Messages  - HEYBLEEPI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css"
        rel="stylesheet" />
  <link rel="stylesheet" href="../stylesheet/messages.css" />
</head>
<body class="page">
  <header class="top-nav glass">
  <h1 class="brand">
    <a href="dashboard.php" style="color:inherit; text-decoration:none;">
        HEYBLEEPI
    </a>
  </h1>
    <nav class="nav-actions">
      <a 
        class="icon-btn"
        href="dashboard.php"
        title="Home">
        <i class="ri-home-4-line"></i>
      </a>
    </nav>
  </header>

  <div class="container">
    <h2>Messages</h2>

    <form method="POST" id="commentForm" enctype="multipart/form-data">
      <textarea
        class="input"
        id="comment"
        name="comment"
        placeholder="Write your message here..."
        rows="6"
        required></textarea>

        <div class="form-actions-row">
          <button type="button" id="emojiBtn">
            <i class="ri-emotion-line"></i>
          </button>
          <div class="form-actions-right">
            <button type="submit" id="addBtn">Send</button>
            <button type="submit" id="updateBtn" style="display:none;">Update</button>
            <button type="button" id="cancelBtn" style="display:none;">Cancel</button>
          </div>
        </div>
    </form>

    <?php if (count($messages) > 0): ?>
      <?php foreach ($messages as $row): ?>
        <div class="message-preview">
          <div class="comment-box">
            <div class="comment-header">
              <img src="../assets/profile/<?= htmlspecialchars($row['profile_picture'] ?? 'rawr.png') ?>"
                alt="Avatar" class="avatar avatar--sm" />
              <div class="preview-text">
                <h4><?= htmlspecialchars($row['user_name']) ?></h4>
                <p><?= htmlspecialchars($row['message']) ?></p>
              </div>
            </div>
            <span class="timestamp"><?= $row['created_at'] ? 
              date("g:i A", strtotime($row['created_at'])) : "No time" ?></span>
            <?php if ($row['user_name'] === $user): ?>
              <span class="comment-actions">
                <button 
                  type="button"
                  class="action-menu-btn"
                  data-id="<?= $row['id'] ?>">
                  <i class="ri-more-2-fill"></i>
                </button>
                <div class="action-menu" style="display: none;">
                  <button class="comment-edit" data-id="<?= $row['id'] ?>">
                    Edit
                  </button>
                  <button class="comment-delete" data-id="<?= $row['id'] ?>">
                    Delete
                  </button>
                </div>
              </span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="no-messages">No messages found.</p>
    <?php endif; ?>
  </div>

    <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal" style="display:none;">
    <div class="modal-content">
      <p>Are you sure you want to delete this message?</p>
      <div class="modal-actions">
        <button id="confirmDeleteBtn">Delete</button>
        <button id="cancelDeleteBtn">Cancel</button>
      </div>
    </div>
  </div>

  <script src="../script/messages.js"></script>
</body>
</html>