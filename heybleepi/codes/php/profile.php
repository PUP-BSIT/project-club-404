<?php
session_start();
require_once 'configuration.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit();
}

$user_id = $_SESSION['id'];

// Get last seen message ID
$lastSeenRow = $conn->query("SELECT last_seen_message_id FROM users WHERE id = $user_id");
$lastSeenMessageId = $lastSeenRow ? ($lastSeenRow->fetch_assoc()['last_seen_message_id'] ?? 0) : 0;

// Get count of newer messages
$unreadResult = $conn->query("SELECT COUNT(*) AS unread FROM messages WHERE id > $lastSeenMessageId");
$unreadMessages = $unreadResult ? $unreadResult->fetch_assoc()['unread'] : 0;

$username = $_SESSION['username'];

// Fetch latest 10 notifications for the logged-in user
$notifications = [];
$unread_count = 0;

$nstmt = $conn->prepare("
  SELECT n.*, u.first_name AS actor_first_name, u.last_name AS actor_last_name
  FROM notifications n
  JOIN users u ON n.actor_id = u.id
  WHERE n.user_id = ?
  ORDER BY n.created_at DESC
  LIMIT 10
");
$nstmt->bind_param("i", $_SESSION['id']);
$nstmt->execute();
$result = $nstmt->get_result();
while ($row = $result->fetch_assoc()) {
  $notifications[] = $row;
}
$nstmt->close();

// Count unread notifications
$unreadResult = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadResult->bind_param("i", $_SESSION['id']);
$unreadResult->execute();
$unreadResult->bind_result($unread_count);
$unreadResult->fetch();
$unreadResult->close();

// Fetch user data by username
$sql = "SELECT users.*, user_details.bio, user_details.work, user_details.school, user_details.home, user_details.religion, user_details.relationship_status, user_details.profile_picture, user_details.profile_cover
        FROM users
        LEFT JOIN user_details ON users.id = user_details.id_fk
        WHERE users.user_name = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo "User not found.";
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['comment_post_id'], $_POST['comment_text'])) {
    $user_id = $_SESSION['id'];
    $post_id = intval($_POST['comment_post_id']);
    $comment_text = trim($_POST['comment_text']);

    if (!empty($comment_text)) {
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $user_id, $comment_text);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: profile.php");
    exit();
}

// POST CREATION
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['post_content'])) {
  $user_id = $_SESSION['id'];
  $post_content = trim($_POST['post_content']);
  $image_path = null;
  $video_path = null;

  // Handle uploaded image
  if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['post_image']['tmp_name'];
    $filename = basename($_FILES['post_image']['name']);
    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $target = $upload_dir . time() . '_' . $filename;

    if (move_uploaded_file($tmp_name, $target)) {
      $image_path = $target;
    }
  }

  // Handle uploaded video
  if (isset($_FILES['post_video']) && $_FILES['post_video']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['post_video']['tmp_name'];
    $filename = basename($_FILES['post_video']['name']);
    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $target = $upload_dir . time() . '_' . $filename;

    if (move_uploaded_file($tmp_name, $target)) {
      $video_path = $target;
    }
  }

  if (!empty($post_content) || $image_path || $video_path) {
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image_path, video_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $post_content, $image_path, $video_path);
    $stmt->execute();
    $stmt->close();
  }

  header("Location: profile.php");
  exit();
}

$user = $result->fetch_assoc();

$userId = $_SESSION['id'];

$mediaStmt = $conn->prepare("
  SELECT image_path, video_path
  FROM posts
  WHERE user_id = ?
    AND (image_path IS NOT NULL OR video_path IS NOT NULL)
  ORDER BY created_at DESC
");
$mediaStmt->bind_param("i", $userId);
$mediaStmt->execute();
$mediaResult = $mediaStmt->get_result();
$mediaPosts = $mediaResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>HEYBLEEPI | <?php echo htmlspecialchars($user['user_name']); ?>'s Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./stylesheet/dashboard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" />
    <script>
      const CURRENT_USER_NAME = <?= json_encode($user['first_name'] . ' ' . $user['last_name']) ?>;
      const CURRENT_USER_USERNAME = <?= json_encode($user['user_name']) ?>;
      const CURRENT_USER_AVATAR = <?= json_encode("./assets/profile/" . ($user['avatar'] ?? "rawr.png")) ?>;
    </script>
  </head>

  <body class="page">
    <!-- Top Navbar -->
    <header class="top-nav glass">
      <h1 class="brand">HEYBLEEPI</h1>
      <nav class="nav-actions">
        <a class="icon-btn" href="dashboard.php" title="Home"><i class="ri-home-4-line"></i></a>

        <a class="icon-btn" href="messages.php" title="Messages">
          <i class="ri-message-3-line"></i>
          <?php if ($unreadMessages > 0): ?>
            <span class="badge  badge--message"><?= $unreadMessages ?></span>
          <?php endif; ?>
        </a>

        <div class="notification-wrapper" id="notification_wrapper">
          <button class="icon-btn" id="notificationBtn" aria-label="Notifications">
            <i class="ri-notification-3-line ri-lg"></i>
            <?php if ($unread_count > 0): ?>
              <span class="badge" id="notification_count"><?= $unread_count ?></span>
            <?php endif; ?>
          </button>

          <div class="notification-dropdown" id="notification_dropdown">
            <h4>Notifications</h4>
            <ul>
              <?php if (empty($notifications)): ?>
                <li>No new notifications.</li>
              <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                  <li>
                    <strong><?= htmlspecialchars($notification['actor_first_name'] . ' ' . $notification['actor_last_name']) ?></strong>
                    <?= htmlspecialchars($notification['type']) ?> your post.
                    <br><small><?= date("M d, g:i A", strtotime($notification['created_at'])) ?></small>
                  </li>
                <?php endforeach; ?>
              <?php endif; ?>
            </ul>

            <form method="POST" action="mark_notifications_read.php">
              <button class="mark-read" type="submit" name="mark_read" id="markAllReadBtn">Mark all as read</button>
            </form>
          </div>
        </div>
      </nav>
    </header>

    <!-- Main Layout -->
    <main class="profile-container">
      <!-- Banner + Profile info -->
      <div class="profile-top glass">
        <img class="banner-img" src="./assets/profile/<?= htmlspecialchars($user['profile_cover'] ?? 'banner.jpg') ?>" alt="Banner" />
        <div class="profile-info-bar">
          <img class="avatar avatar--sm2" src="./assets/profile/<?= htmlspecialchars($user['profile_picture'] ?? 'rawr.png') ?>" alt="">
          <div class="user-basic-info">
            <h2><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h2>
            <p>@<?= htmlspecialchars($user['user_name']) ?> · 81</p>
          </div>
          <div class="profile-buttons">
            <button class="btn btn--primary">Add to Story</button>
            <button class="btn btn--secondary" onclick="window.location.href='profile_edit.php'">Edit Profile</button>
          </div>
        </div>
      </div>

      <nav class="profile-tabs glass">
        <a class="tab active" href="#">Posts</a>
        <a class="tab" href="#">About</a>
        <a class="tab" href="#">Friends</a>
        <a class="tab" href="#">Photos</a>
        <a class="tab" href="#">More</a>
      </nav>

      <!-- Main 2-column grid -->
      <div class="profile-main-grid">
        <!-- LEFT COLUMN -->
        <aside class="left-column">
          <section class="glass card">
            <h4 class="card-title">Intro</h4>

            <?php if (!empty($user['bio'])): ?>
              <p><?= htmlspecialchars($user['bio']) ?></p>
            <?php endif; ?>

            <?php if (!empty($user['work'])): ?>
              <p><i class="ri-briefcase-line"></i> Works at <?= htmlspecialchars($user['work']) ?></p>
            <?php endif; ?>

            <?php if (!empty($user['school'])): ?>
              <p><i class="ri-graduation-cap-line"></i> Studies at <?= htmlspecialchars($user['school']) ?></p>
            <?php endif; ?>

            <?php if (!empty($user['home'])): ?>
              <p><i class="ri-map-pin-line"></i> Lives in <?= htmlspecialchars($user['home']) ?></p>
            <?php endif; ?>

            <?php if (!empty($user['religion'])): ?>
              <p><i class="ri-heart-pulse-line"></i> Religion: <?= htmlspecialchars($user['religion']) ?></p>
            <?php endif; ?>

            <?php if (!empty($user['relationship_status'])): ?>
              <p><i class="ri-heart-line"></i> <?= ucwords(str_replace('_', ' ', htmlspecialchars($user['relationship_status']))) ?></p>
            <?php endif; ?>
          </section>

          <section class="glass card">
            <h4 class="card-title">Photos</h4>
              <div class="photo-grid">
                <?php foreach ($mediaPosts as $media): ?>
                  <?php if (!empty($media['image_path'])): ?>
                    <img src="<?= htmlspecialchars($media['image_path']) ?>"
                        data-type="image"
                        data-src="<?= htmlspecialchars($media['image_path']) ?>"
                        alt="User Image" />
                  <?php endif; ?>

                  <?php if (!empty($media['video_path'])): ?>
                    <video muted
                          data-type="video"
                          data-src="<?= htmlspecialchars($media['video_path']) ?>">
                      <source src="<?= htmlspecialchars($media['video_path']) ?>" type="video/mp4" />
                    </video>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
          </section>
        </aside>

        <!-- Create Post -->
        <section class="right-column">
          <div class="glass create-post">
            <form method="POST" action="profile.php"  enctype="multipart/form-data">
              <div class="create-post-header">
                <img class="avatar avatar--sm" src="./assets/profile/<?= htmlspecialchars($user['profile_picture'] ?? 'rawr.png') ?>" alt="">
                <div class="poster-info">
                  <a href="profile.php" class="poster-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></a>
                  <p>@<?php echo htmlspecialchars($user['user_name']); ?></p>
                </div>
              </div>

              <textarea class="create-post-input" name="post_content" placeholder="What's happening in your galaxy?" required></textarea>

              <!-- Image Preview -->
              <div id="imagePreviewContainer" style="display: none; position: relative; margin-top: 10px;">
                <img id="imagePreview" src="" style="max-width: 150px; max-height: 150px; border-radius: 8px;">
                <button type="button" id="removeImageBtn"
                  style="position: absolute; top: -8px; right: -8px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
              </div>

              <!-- Video Preview -->
              <div id="videoPreviewContainer" style="display: none; position: relative; margin-top: 10px;">
                <video id="videoPreview" controls style="max-width: 200px; border-radius: 10px;"></video>
                <button type="button" id="removeVideoBtn"
                  style="position: absolute; top: -8px; right: -8px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
              </div>

              <div class="create-post-actions">
                <div class="action-group">
                  <!-- Image -->
                  <label class="icon-btn">
                    <i class="ri-image-line"></i>
                    <input type="file" name="post_image" accept="image/*" id="postImageInput" style="display: none;">
                  </label>

                  <!-- Video -->
                  <label class="icon-btn">
                    <i class="ri-vidicon-line"></i>
                    <input type="file" name="post_video" accept="video/*" id="postVideoInput" style="display: none;">
                  </label>

                  <!-- Emoji -->
                  <button class="icon-btn" type="button"><i class="ri-emotion-line"></i></button>

                  <!-- Location -->
                  <button class="icon-btn" type="button"><i class="ri-map-pin-line"></i></button>
                </div>
                <button class="btn btn--primary" type="submit">Post</button>
              </div>
            </form>
          </div>

          <!-- Posts will appear here -->
          <?php
          $userId = $_SESSION['id'];

          $query = "
            SELECT
              p.id AS post_id,
              p.content,
              p.created_at,
              p.shared_post_id,
              p.image_path,
              p.video_path,
              u.first_name,
              u.last_name,
              u.user_name,
              sp.content AS shared_content,
              sp.video_path AS shared_video_path,
              sp.image_path AS shared_image_path,
              sp.video_path AS shared_video_path,
              su.first_name AS shared_first_name,
              su.last_name AS shared_last_name
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN posts sp ON p.shared_post_id = sp.id
            LEFT JOIN users su ON sp.user_id = su.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
          ";

          $stmt = $conn->prepare($query);
          $stmt->bind_param("i", $_SESSION['id']);
          $stmt->execute();
          $result = $stmt->get_result();

          ?>

          <?php while ($post = $result->fetch_assoc()): ?>

            <?php
              $likeRes = $conn->query("SELECT COUNT(*) AS total FROM likes WHERE post_id = {$post['post_id']}");
              $countLikes = $likeRes ? $likeRes->fetch_assoc() : ['total' => 0];

              $userLikedRes = $conn->query("SELECT 1 FROM likes WHERE post_id = {$post['post_id']} AND user_id = {$_SESSION['id']}");
              $userLiked = $userLikedRes && $userLikedRes->num_rows > 0;

              $commentRes = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE post_id = {$post['post_id']}");
              $countComments = $commentRes ? $commentRes->fetch_assoc() : ['total' => 0];

              $shareRes = $conn->query("SELECT COUNT(*) AS total FROM posts WHERE shared_post_id = {$post['post_id']}");
              $countShares = $shareRes ? $shareRes->fetch_assoc() : ['total' => 0];
            ?>

            <article class="glass post">
              <header class="post-header">
                <img class="avatar avatar--sm" src="./assets/profile/<?= htmlspecialchars($user['profile_picture'] ?? 'rawr.png') ?>" alt="User Avatar">
                <div>
                  <h4><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></h4>
                  <time><?= date("M d, g:i A", strtotime($post['created_at'])) ?></time>
                </div>
                <div class="post-options">
                  <button class="icon-btn toggle-options"><i class="ri-more-fill"></i></button>
                  <ul class="dropdown hidden">
                    <li><button class="btn--sm btn-edit-post" data-id="<?= $post['post_id'] ?>">Edit Post</button></li>
                    <li>
                      <form method="POST" action="delete_post_profile.php" style="display:inline;">
                        <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                        <button type="submit" onclick="return confirm('Delete this post?')">Delete Post</button>
                      </form>
                    </li>
                  </ul>
                </div>
              </header>

              <!-- SHARED POST BLOCK -->
              <?php if ($post['shared_post_id']): ?>
                <div class="shared-post glass" style="padding: 10px; background-color: rgba(255, 255, 255, 0.05); border-left: 3px solid var(--primary); border-radius: 10px; margin-bottom: 10px;">
                  <small>Shared from <strong><?= htmlspecialchars($post['shared_first_name'] . ' ' . $post['shared_last_name']) ?></strong></small>
                  <p><?= htmlspecialchars($post['shared_content']) ?></p>

                  <!-- Image shared post -->
                  <?php if (!empty($post['shared_image_path'])): ?>
                    <img src="<?= htmlspecialchars($post['shared_image_path']) ?>" alt="Shared Post Image" style="max-width: 150px; max-height: 150px; margin-top: 10px; border-radius: 10px;">
                  <?php endif; ?>

                  <!-- Video shared post -->
                  <?php if (!empty($post['shared_video_path'])): ?>
                    <video controls style="max-width: 100%; margin-top: 10px; border-radius: 10px;">
                      <source src="<?= htmlspecialchars($post['shared_video_path']) ?>" type="video/mp4">
                      Your browser does not support the video tag.
                    </video>
                  <?php endif; ?>

                </div>

              <?php endif; ?>

              <!-- MAIN POST CONTENT -->
              <div class="post-content" data-post-id="<?= $post['post_id'] ?>">
                <p class="post-text"><?= htmlspecialchars($post['content']) ?></p>
              </div>

              <!-- Display uploaded image -->
              <?php if (!empty($post['image_path'])): ?>
                <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="Post Image" style="max-width: 150px; margin-top: 10px; border-radius: 10px;">
              <?php endif; ?>

              <?php if (!empty($post['video_path'])): ?>
                <video controls style="width: 100%; max-width: 300px; border-radius: 10px; margin-top: 10px;">
                  <source src="<?= htmlspecialchars($post['video_path']) ?>" type="video/mp4">
                  Your browser does not support the video tag.
                </video>
              <?php endif; ?>


              <footer class="post-footer">
                <div class="post-actions">
                  <!-- LIKE -->
                  <form method="POST" class="like-form" style="display:inline;">
                    <input type="hidden" name="like_post_id" value="<?= $post['post_id'] ?>">
                    <button type="button" class="icon-btn like-button <?= $userLiked ? 'liked' : '' ?>" data-post-id="<?= $post['post_id'] ?>">
                      <i class="<?= $userLiked ? 'ri-heart-fill' : 'ri-heart-line' ?>"></i>
                      <span><?= $countLikes['total'] ?></span>
                    </button>
                  </form>

                  <!-- COMMENT TOGGLE -->
                  <button class="icon-btn" onclick="document.getElementById('comment-form-<?= $post['post_id'] ?>').classList.toggle('hidden')">
                    <i class="ri-chat-1-line"></i>
                    <span><?= $countComments['total'] ?></span>
                  </button>

                  <!-- SHARE -->
                  <form method="POST" action="share_post.php" style="display:inline;">
                    <input type="hidden" name="share_post_id" value="<?= $post['post_id'] ?>">
                    <button type="submit" class="icon-btn">
                      <i class="ri-share-forward-line"></i>
                      <span><?= $countShares['total'] ?></span>
                    </button>
                  </form>
                </div>
                <button class="icon-btn"><i class="ri-bookmark-line"></i></button>
              </footer>

              <!-- COMMENTS SECTION -->
              <div id="comment-form-<?= $post['post_id'] ?>" class="hidden" style="margin-top:10px;">
                <form method="POST" action="profile.php">
                  <input type="hidden" name="comment_post_id" value="<?= $post['post_id'] ?>">
                  <input type="text" name="comment_text" placeholder="Write a comment…" required style="width: 100%; padding: 8px;">
                  <button type="submit" class="btn btn--primary btn--sm" style="margin-top:5px;">Comment</button>
                </form>

                <!-- Load existing comments -->
                <div style="margin-top:10px;">
                  <?php
                    $comments = $conn->query("SELECT comments.*, users.first_name, users.last_name FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = {$post['post_id']} ORDER BY commented_at ASC");
                    while ($comment = $comments->fetch_assoc()):
                  ?>
                    <div class="comment" style="margin-bottom: 8px;">
                      <strong><?= htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) ?>:</strong>
                      <span><?= htmlspecialchars($comment['comment_text']) ?></span>
                      <small style="color:gray;"> – <?= date("M d, g:i A", strtotime($comment['commented_at'])) ?></small>

                      <?php if ($comment['user_id'] == $_SESSION['id']): ?>
                        <button class="btn--sm btn-edit-comment" data-id="<?= $comment['id'] ?>">Edit</button>
                        <button class="btn--sm btn-delete-comment" data-id="<?= $comment['id'] ?>">Delete</button>
                      <?php endif; ?>
                    </div>
                  <?php endwhile; ?>
                </div>
              </div>
            </article>

          <?php endwhile; ?>

        </section>
      </div>
    </main>
    <div id="lightbox" class="lightbox" style="display:none;">
      <span class="close" onclick="closeLightbox()">×</span>
      <div class="lightbox-content" id="lightboxContent"></div>
    </div>

    <script src="./script/dashboard.js"></script>
  </body>
</html>