<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['user_name'])) {
  header("Location: index.php");
  exit();
}

$user = $_SESSION['user_name'];
$edit_message = null;

// POST: Add or update a message
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["comment"])) {
  $msg = trim($_POST['comment']);

  if (!empty($msg)) {
    if (isset($_POST['update_id'])) {
      $update_id = intval($_POST['update_id']);
      $stmt = $conn->prepare("UPDATE messages SET message = ? WHERE id = ? AND user_name = ?");
      $stmt->bind_param("sis", $msg, $update_id, $user);
    } else {
      $stmt = $conn->prepare("INSERT INTO messages (user_name, message) VALUES (?, ?)");
      $stmt->bind_param("ss", $user, $msg);
    }
    $stmt->execute();
    $stmt->close();
  }

  header("Location: messages.php");
  exit();
}

// POST: Delete message
if (isset($_POST['delete_id'])) {
  $delete_id = intval($_POST['delete_id']);
  $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_name = ?");
  $stmt->bind_param("is", $delete_id, $user);
  $stmt->execute();
  $stmt->close();
  header("Location: messages.php");
  exit();
}

// POST: Load message for editing
if (isset($_POST['edit_id'])) {
  $edit_id = intval($_POST['edit_id']);
  $stmt = $conn->prepare("SELECT * FROM messages WHERE id = ? AND user_name = ?");
  $stmt->bind_param("is", $edit_id, $user);
  $stmt->execute();
  $result = $stmt->get_result();
  $edit_message = $result->fetch_assoc();
  $stmt->close();
}

// Fetch all messages with user avatars
$sql = "SELECT m.*, u.avatar FROM messages m JOIN users u ON m.user_name = u.user_name ORDER BY m.created_at DESC";
$result = $conn->query($sql);
$messages = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Messages</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css"
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="./stylesheet/messages.css" />
  </head>

  <body class="page">
    <!-- TOP NAVIGATION BAR -->
    <header class="top-nav glass">
      <h1 class="brand">HEYBLEEPI</h1>
      <nav class="nav-actions">
        <a class="icon-btn" href="dashboard.php" title="Home"><i class="ri-home-4-line"></i></a>
        <a class="icon-btn" href="messages.php" title="Messages"><i class="ri-message-3-line"></i></a>

        <div class="notification-wrapper" id="notificationWrapper">
          <button class="icon-btn" id="notificationBtn" aria-label="Notifications">
            <i class="ri-notification-3-line"></i>
            <span class="badge" id="notificationCount">3</span>
          </button>
          <div class="notification-dropdown hidden" id="notificationDropdown">
            <h4>Notifications</h4>
            <ul>
              <li><strong>John</strong> liked your post.</li>
              <li><strong>Alice</strong> followed you.</li>
              <li><strong>Jane</strong> commented.</li>
            </ul>
            <button class="mark-read" id="markAllReadBtn">Mark all as read</button>
          </div>
        </div>
      </nav>
    </header>

    <!--  MESSAGE CONTAINER -->
    <div class="container">
      <h2>Messages</h2>
      <!--  Store uploaded files -->
      <form method="POST" id="commentForm" enctype="multipart/form-data">
        <textarea
          class="input"
          name="comment"
          placeholder="Write your message here..."
          rows="6"
          required
        ><?= $edit_message ? htmlspecialchars($edit_message['message']) : '' ?></textarea>

        <!-- Hidden Inputs -->
        <input type="file" id="imageInput" name="image" accept="image/*" hidden>
        <input type="file" id="fileInput" name="attachment" hidden>

        <?php if ($edit_message): ?>
          <input type="hidden" name="update_id" value="<?= $edit_message['id'] ?>">
          <div class="button-row">
            <button type="submit">Update Message</button>
            <button type="button" class="cancel" onclick="window.location='messages.php'">Cancel</button>
          </div>
        <?php else: ?>
          <button type="submit">Add Message</button>
        <?php endif; ?>
      </form>

      <div class="message-actions">
        <button type="button" onclick="alert('Emoji picker not implemented yet')">
          <i class="ri-emotion-line"></i>
        </button>
        <button type="button" onclick="document.getElementById('imageInput').click()">
          <i class="ri-image-line"></i>
        </button>
        <button type="button" onclick="document.getElementById('fileInput').click()">
          <i class="ri-attachment-line"></i>
        </button>
      </div>

      <!-- Messages Output -->
      <?php foreach ($messages as $row): ?>
        <div class="message-preview">
          <img src="./assets/profile/<?= htmlspecialchars($msg['avatar']) ?>" class="avatar avatar--sm" />
          <div class="preview-text">
            <h4><?= htmlspecialchars($msg['user_name']) ?></h4>
            <p><?= htmlspecialchars($msg['message']) ?></p>
          </div>
          <span class="timestamp"><?= date("g:i A", strtotime($msg['created_at'])) ?></span>

          <?php if ($msg['user_name'] === $_SESSION['user_name']): ?>
            <div class="comments">
              <!-- Edit Form -->
              <form method="POST" class="edit-form" style="display:inline;">
                  <input type="hidden" name="edit_id" value="<?= $msg['id'] ?>">
                  <button class="comment-edit" type="submit">Edit</button>
              </form>

              <!-- Delete Form -->
              <form method="POST" class="delete-form" style="display:inline;">
                <input type="hidden" name="delete_id" value="<?= $msg['id'] ?>">
                <button class="comment-delete" type="submit">Delete</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <script src="./script/messages.js"></script>
  </body>
</html>