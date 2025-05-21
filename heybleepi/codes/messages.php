<?php
session_start();
require_once 'configuration.php';

// Handle update
if (isset($_POST['update_id'])) {
  $update_id = intval($_POST['update_id']);
  $username = mysqli_real_escape_string($conn, $_POST['username']);
  $comment = mysqli_real_escape_string($conn, $_POST['comment']);
  $conn->query("UPDATE comment SET username='$username', comment='$comment' WHERE id = $update_id");
  header("Location: " . $_SERVER['PHP_SELF']);
  exit();
}

// Insert new comment
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['comment']) && !isset($_POST['update_id'])) {
  $username = mysqli_real_escape_string($conn, $_SESSION['username']); // use session
  $comment = mysqli_real_escape_string($conn, $_POST['comment']);

  $sql = "INSERT INTO comment (username, comment) VALUES ('$username', '$comment')";
  $conn->query($sql);

  // Redirect to avoid resubmission on reload
  header("Location: " . $_SERVER['PHP_SELF']);
  exit();
}

// Fetch all comments
$comments = $conn->query("SELECT * FROM comment ORDER BY created_at DESC");

// Handle delete
if (isset($_POST['delete_id'])) {
  $delete_id = intval($_POST['delete_id']);
  $conn->query("DELETE FROM comment WHERE id = $delete_id");
  header("Location: " . $_SERVER['PHP_SELF']);
  exit();
}

// Handle edit request (load comment into form)
$edit_comment = null;
if (isset($_POST['edit_id'])) {
  $edit_id = intval($_POST['edit_id']);
  $result = $conn->query("SELECT * FROM comment WHERE id = $edit_id");
  $edit_comment = $result->fetch_assoc();
}
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
    <link rel="stylesheet" href="messages.css" />
  </head>

  <body>
    <div class="container">
      <h2>Messages</h2>
      <form method="POST" id="commentForm">
        <!-- <input
          class="input"
          type="text"
          name="username"
          placeholder="Your username"
          required
          value="<?= $edit_comment ? htmlspecialchars($edit_comment['username']) : '' ?>"
        /> -->
        <textarea
          class="input"
          name="comment"
          placeholder="Write your message here..."
          rows="6"
          required
        ><?= $edit_comment ? htmlspecialchars($edit_comment['comment']) : '' ?></textarea>
        <?php if ($edit_comment): ?>
          <input type="hidden" name="update_id" value="<?= $edit_comment['id'] ?>">
          <div class="button-row">
            <button type="submit">Update Message</button>
            <button type="button" class="cancel" onclick="window.location='<?= $_SERVER['PHP_SELF'] ?>'">Cancel</button>
          </div>
        <?php else: ?>
          <button type="submit">Add Message</button>
        <?php endif; ?>
      </form>

      <div class="comment-box">
        <?php while ($row = $comments->fetch_assoc()) { ?>
        <div class="comment">
          <strong><?= htmlspecialchars($row['username']) ?></strong>
          <p><?= nl2br(htmlspecialchars($row['comment'])) ?></p>
          <small><?= $row['created_at'] ?></small>
          <div class="comments">
            <!-- Edit Form -->
            <form method="POST" class="edit-form" style="display:none;">
              <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
            </form>
            <span class="comment-edit" style="background:none; border:none; margin-right:16px; cursor:pointer;"
              onclick="this.previousElementSibling.submit();">Edit</span>
            <!-- Delete Form -->
            <form method="POST" class="delete-form" style="display:none;">
              <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
            </form>
            <span class="comment-delete" style="background:none; border:none; cursor:pointer;"
              onclick="this.previousElementSibling.submit();">Delete</span>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>

    <script src="messages.js"></script>
  </body>
</html>

