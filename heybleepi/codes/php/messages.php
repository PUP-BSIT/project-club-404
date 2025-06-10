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

// Get the latest message ID and update user's last_seen_message_id
$latest_msg_query = $conn->query("SELECT MAX(id) as max_id FROM messages");
$latest_msg = $latest_msg_query->fetch_assoc();
$latest_msg_id = $latest_msg['max_id'] ?? 0;

$update_last_seen = $conn->prepare("UPDATE users SET last_seen_message_id = ? WHERE id = ?");
$update_last_seen->bind_param("ii", $latest_msg_id, $user_id);
$update_last_seen->execute();
$update_last_seen->close();

// Mark all messages as read for the current user
$update = $conn->prepare("UPDATE messages SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$update->bind_param("i", $user_id);
$update->execute();
$update->close();

// Handle adding or updating a message
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["comment"]) && trim($_POST["comment"]) !== "") {
        $msg = trim($_POST['comment']);

        if (isset($_POST['update_id'])) {
            // Update existing message
            $update_id = intval($_POST['update_id']);
            $stmt = $conn->prepare("UPDATE messages SET message = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $msg, $update_id, $user_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new message
            $stmt = $conn->prepare("INSERT INTO messages (user_id, user_name, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iss", $user_id, $user, $msg);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: messages.php");
        exit();
    }

    // Delete message
    if (isset($_POST['delete_id'])) {
        $delete_id = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $delete_id, $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: messages.php");
        exit();
    }

    // Load message for editing
    if (isset($_POST['edit_id'])) {
        $edit_id = intval($_POST['edit_id']);
        $stmt = $conn->prepare("SELECT * FROM messages WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $edit_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_message = $result->fetch_assoc();
        $stmt->close();
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
  <title>Messages - HEYBLEEPI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/@joeattardi/emoji-button@4.6.4/dist/index.min.js"></script>
  <link rel="stylesheet" href="./stylesheet/messages.css" />
</head>
<body class="page">
  <header class="top-nav glass">
    <h1 class="brand">HEYBLEEPI</h1>
    <nav class="nav-actions">
      <a class="icon-btn" href="dashboard.php" title="Home"><i class="ri-home-4-line"></i></a>
      <a class="icon-btn" href="messages.php" title="Messages"><i class="ri-message-3-line"></i></a>
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
        required><?= isset($edit_message) ? htmlspecialchars($edit_message['message']) : '' ?></textarea>

      <?php if (isset($edit_message)): ?>
        <input type="hidden" name="update_id" value="<?= htmlspecialchars($edit_message['id']) ?>">
        <div class="button-row">
          <button type="submit">Update Message</button>
          <button type="button" onclick="window.location='messages.php'">Cancel</button>
        </div>
      <?php else: ?>
        <button type="submit">Add Message</button>
      <?php endif; ?>
    </form>

    <div class="message-actions">
      <button type="button" id="emojiBtn"><i class="ri-emotion-line"></i></button>
    </div>

    <?php if (count($messages) > 0): ?>
      <?php foreach ($messages as $row): ?>
        <div class="message-preview">
          <div class="comment-box">
            <div class="comment-header">
            <img src="./assets/profile/<?= htmlspecialchars($row['profile_picture'] ?? 'rawr.png') ?>" alt="Avatar" class="avatar avatar--sm" />
              <div class="preview-text">
                <h4><?= htmlspecialchars($row['user_name']) ?></h4>
                <p><?= htmlspecialchars($row['message']) ?></p>
              </div>
            </div>
            <span class="timestamp"><?= $row['created_at'] ? date("g:i A", strtotime($row['created_at'])) : "No time" ?></span>
            <?php if ($row['user_name'] === $user): ?>
              <span class="comment-actions">
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                  <span class="comment-edit" onclick="this.closest('form').submit();">Edit</span>
                </form>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                  <span class="comment-delete" onclick="if(confirm('Delete this message?')) this.closest('form').submit();">Delete</span>
                </form>
              </span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="no-messages">No messages found.</p>
    <?php endif; ?>
  </div>

  <script src="./script/messages.js"></script>
</body>
</html>