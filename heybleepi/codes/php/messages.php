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

// Handle adding or updating a message
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["comment"]) && trim($_POST["comment"]) !== "") {
        $msg = trim($_POST['comment']);

        if (isset($_POST['update_id'])) {
            // Update existing message
            $update_id = intval($_POST['update_id']);
            $stmt = $conn->prepare("UPDATE messages SET message = ? WHERE id = ? AND user_name = ?");
            $stmt->bind_param("sis", $msg, $update_id, $user);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new message
            $stmt = $conn->prepare("INSERT INTO messages (user_name, message, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $user, $msg);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: messages.php");
        exit();
    }

    // Delete message
    if (isset($_POST['delete_id'])) {
        $delete_id = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_name = ?");
        $stmt->bind_param("is", $delete_id, $user);
        $stmt->execute();
        $stmt->close();

        header("Location: messages.php");
        exit();
    }

    // Load message for editing
    if (isset($_POST['edit_id'])) {
        $edit_id = intval($_POST['edit_id']);
        $stmt = $conn->prepare("SELECT * FROM messages WHERE id = ? AND user_name = ?");
        $stmt->bind_param("is", $edit_id, $user);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_message = $result->fetch_assoc();
        $stmt->close();
    }
} 

// Fetch all messages with user avatars
$sql = "SELECT m.*, u.avatar FROM messages m LEFT JOIN users u ON m.user_name = u.user_name ORDER BY m.created_at DESC";
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
  <link rel="stylesheet" href="../stylesheet/messages.css" />
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
      <button type="button" id="emojiBtn" onclick="alert('Emoji picker not implemented yet')"><i class="ri-emotion-line"></i></button>
      <button type="button" onclick="document.getElementById('imageInput').click()"><i class="ri-image-line"></i></button>
      <button type="button" onclick="document.getElementById('fileInput').click()"><i class="ri-attachment-line"></i></button>
    </div>

    <?php if (count($messages) > 0): ?>
      <?php foreach ($messages as $row): ?>
        <div class="message-preview">
          <div class="comment-box">
            <div class="comment-header">
              <img src="../assets/profile/<?= htmlspecialchars($row['avatar'] ?? 'default.png') ?>" alt="Avatar" class="avatar avatar--sm" />
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
      <p>No messages found.</p>
    <?php endif; ?>
  </div>

  <script src="../script/messages.js"></script>
</body>
</html>