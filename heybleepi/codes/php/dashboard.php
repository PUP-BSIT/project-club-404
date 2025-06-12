<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit();
}

// Fetch notifications
$notificationQuery = "
  SELECT n.*,
         u.first_name AS actor_first_name,
         u.last_name AS actor_last_name,
         p.content AS post_content
  FROM notifications n
  JOIN users u ON n.actor_id = u.id
  LEFT JOIN posts p ON n.post_id = p.id
  WHERE n.user_id = ?
  ORDER BY n.created_at DESC
  LIMIT 10
";

$notificationStmt = $conn->prepare($notificationQuery);
$notificationStmt->bind_param("i", $_SESSION['id']);
$notificationStmt->execute();
$notificationsResult = $notificationStmt->get_result();
$notifications = $notificationsResult->fetch_all(MYSQLI_ASSOC);
$notificationStmt->close();

// Count unread notifications
$unreadCountRes = $conn->query("SELECT COUNT(*) AS unread FROM notifications WHERE user_id = {$_SESSION['id']} AND is_read = 0");
$unread = $unreadCountRes->fetch_assoc()['unread'] ?? 0;

$user_id = $_SESSION['id'];

// Count messages the user hasn't read yet
$lastSeenQuery = $conn->prepare("SELECT last_seen_message_id FROM users WHERE id = ?");
$lastSeenQuery->bind_param("i", $user_id);
$lastSeenQuery->execute();
$lastSeenQuery->bind_result($lastSeenMessageId);
$lastSeenQuery->fetch();
$lastSeenQuery->close();

$lastSeenMessageId = $lastSeenMessageId ?? 0;

$countNewMessages = $conn->query("SELECT COUNT(*) AS unread_messages FROM messages WHERE id > $lastSeenMessageId");
$unreadMessages = $countNewMessages->fetch_assoc()['unread_messages'] ?? 0;

// POST CREATION - handles multiple image/video uploads
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['post_content'])) {
  $user_id = $_SESSION['id'];
  $post_content = trim($_POST['post_content']);
  $upload_dir = "uploads/";
  if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

  // Insert post first to get post_id
  $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
  $stmt->bind_param("is", $user_id, $post_content);
  $stmt->execute();
  $post_id = $stmt->insert_id;
  $stmt->close();

  // Handle multiple image uploads
  if (!empty($_FILES['post_images']['name'][0])) {
    foreach ($_FILES['post_images']['tmp_name'] as $key => $tmp_name) {
      if ($_FILES['post_images']['error'][$key] === UPLOAD_ERR_OK) {
        $filename = time() . '_img_' . $key . '_' . basename($_FILES['post_images']['name'][$key]);
        $target = $upload_dir . $filename;
        if (move_uploaded_file($tmp_name, $target)) {
          $mediaStmt = $conn->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, 'image')");
          $mediaStmt->bind_param("is", $post_id, $target);
          $mediaStmt->execute();
          $mediaStmt->close();
        }
      }
    }
  }

  // Handle multiple video uploads
  if (!empty($_FILES['post_videos']['name'][0])) {
    foreach ($_FILES['post_videos']['tmp_name'] as $key => $tmp_name) {
      if ($_FILES['post_videos']['error'][$key] === UPLOAD_ERR_OK) {
        $filename = time() . '_vid_' . $key . '_' . basename($_FILES['post_videos']['name'][$key]);
        $target = $upload_dir . $filename;
        if (move_uploaded_file($tmp_name, $target)) {
          $mediaStmt = $conn->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, 'video')");
          $mediaStmt->bind_param("is", $post_id, $target);
          $mediaStmt->execute();
          $mediaStmt->close();
        }
      }
    }
  }

  header("Location: dashboard.php");
  exit();
}

function getMediaClass($path) {
    $size = @getimagesize($path);
    if (!$size) return 'landscape'; // fallback

    $width = $size[0];
    $height = $size[1];

    $ratio = $width / $height;

    if ($ratio > 1.2) return 'landscape';
    elseif ($ratio < 0.8) return 'portrait';
    else return 'square';
}

// LIKE A POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['like_post_id'])) {
  $post_id = intval($_POST['like_post_id']);

  $check = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
  $check->bind_param("ii", $user_id, $post_id);
  $check->execute();
  $check->store_result();

  if ($check->num_rows === 0) {
    $like = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $like->bind_param("ii", $user_id, $post_id);
    $like->execute();
    $like->close();

    // Add notification to post owner
    $ownerStmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $ownerStmt->bind_param("i", $post_id);
    $ownerStmt->execute();
    $ownerStmt->bind_result($owner_id);
    $ownerStmt->fetch();
    $ownerStmt->close();

    if ($owner_id != $user_id) {
      $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, actor_id, post_id, type) VALUES (?, ?, ?, 'like')");
      $notifStmt->bind_param("iii", $owner_id, $user_id, $post_id);
      $notifStmt->execute();
      $notifStmt->close();
    }
  }

  $check->close();
  header("Location: dashboard.php");
  exit();
}

// COMMENT ON A POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['comment_post_id'], $_POST['comment_text'])) {
  $post_id = intval($_POST['comment_post_id']);
  $comment = trim($_POST['comment_text']);

  if (!empty($comment)) {
    $stmt = $conn->prepare("INSERT INTO comments (user_id, post_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $post_id, $comment);
    $stmt->execute();
    $stmt->close();

    // Add notification to post owner
    $ownerStmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $ownerStmt->bind_param("i", $post_id);
    $ownerStmt->execute();
    $ownerStmt->bind_result($owner_id);
    $ownerStmt->fetch();
    $ownerStmt->close();

    if ($owner_id != $user_id) {
      $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, actor_id, post_id, type) VALUES (?, ?, ?, 'comment')");
      $notifStmt->bind_param("iii", $owner_id, $user_id, $post_id);
      $notifStmt->execute();
      $notifStmt->close();
    }
  }

  header("Location: dashboard.php");
  exit();
}

// Fetch latest 10 notifications
$notifications = [];
$unread_count = 0;

$nstmt = $conn->prepare("SELECT n.*, u.first_name, u.last_name FROM notifications n JOIN users u ON n.actor_id = u.id WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT 10");
$nstmt->bind_param("i", $user_id);
$nstmt->execute();
$result = $nstmt->get_result();
while ($row = $result->fetch_assoc()) {
  $notifications[] = $row;
}
$nstmt->close();

$unreadResult = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadResult->bind_param("i", $user_id);
$unreadResult->execute();
$unreadResult->bind_result($unread_count);
$unreadResult->fetch();
$unreadResult->close();

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HEYBLEEPI! – Social Media Dashboard</title>

    <link rel="stylesheet" href="./stylesheet/dashboard.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" />
  </head>

  <body class="page">
    <!-- ☰ TOP BAR -->
    <header class="top-nav glass">
      <h1 class="brand">HEYBLEEPI</h1>

      <div class="nav-actions">
        <!-- Search -->
        <form class="search">
          <input class="search-input"
            type="text"
            placeholder="Search in space…" />
          <i class="ri-search-line search-icon"></i>
        </form>

        <!-- Create Post -->
        <button class="icon-btn icon-btn--primary" aria-label="Create post">
          <i class="ri-add-line ri-lg"></i>
        </button>

        <!-- Notification Wrapper -->
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
                    <strong><?= htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']) ?></strong>
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

        <!-- Profile -->
        <img class="avatar avatar--sm" src="./assets/profile/<?= htmlspecialchars($_SESSION['avatar'] ?? 'default.png') ?>" alt="">
      </div>
    </header>

    <!-- ☰ MAIN LAYOUT -->
    <main class="layout">

      <!-- LEFT SIDEBAR -->
      <aside class="sidebar sidebar--left">

        <!-- Profile Card -->
        <section class="glass card card--profile">
          <?php
          $postAvatarPath = './assets/profile/' . ($_SESSION['avatar'] ?? 'default.png');
          if (!file_exists($postAvatarPath)) {
            $postAvatarPath = './assets/profile/default.png';
          }
          ?>
          <img class="avatar avatar--sm" src="<?= $postAvatarPath ?>" alt="">

          <h3 class="card-title"><?= htmlspecialchars($_SESSION['first_name'] . " " . $_SESSION['last_name']) ?></h3>
          <p class="card-subtitle">@<?= htmlspecialchars($_SESSION['username']) ?></p>

          <ul class="stats">
            <li><strong>
              <?php
                // Count all posts (original + shared) for the user
                $userPostCount = $conn->query("SELECT COUNT(*) AS total FROM posts WHERE user_id = " . intval($_SESSION['id']));
                $postCount = $userPostCount ? $userPostCount->fetch_assoc()['total'] : 0;
                echo $postCount;
              ?>
            </strong><span>Posts</span></li>
            <li><strong>
              <?php
                // Count all users except the current user (for followers)
                $followersCountRes = $conn->query("SELECT COUNT(*) AS total FROM users WHERE id != " . intval($_SESSION['id']));
                $followersCount = $followersCountRes ? $followersCountRes->fetch_assoc()['total'] : 0;
                echo number_format($followersCount);
              ?>
            </strong><span>Followers</span></li>
            <li><strong>
              <?php
                // Count all users except the current user (for following)
                $followingCountRes = $conn->query("SELECT COUNT(*) AS total FROM users WHERE id != " . intval($_SESSION['id']));
                $followingCount = $followingCountRes ? $followingCountRes->fetch_assoc()['total'] : 0;
                echo number_format($followingCount);
              ?>
            </strong><span>Following</span></li>
          </ul>
        </section>

        <!-- Navigation -->
        <nav class="glass card nav-list">
          <a class="nav-item" href="#">
            <i class="ri-home-4-line"></i>
            Home
          </a>

          <a class="nav-item" href="messages.php">
            <i class="ri-message-3-line"></i>
            <span class="nav-label">
              Messages
              <?php if ($unreadMessages > 0): ?>
                <span class="badge badge-inline"><?= $unreadMessages ?></span>
              <?php endif; ?>
            </span>
          </a>

          <a class="nav-item" href="profile.php">
            <i class="ri-user-line"></i>
            Profile
          </a>

          <a class="nav-item" href="settings.php">
            <i class="ri-settings-4-line"></i>
            Settings & Privacy
          </a>

          <a class="nav-item" href="bookmarks.php"> <!-- Link to saved items -->
            <i class="ri-bookmark-line"></i>
            Saved
          </a>

          <a class="nav-item" href="logout.php">
            <i class="ri-logout-box-line"></i>
            Logout
          </a>
        </nav>
      </aside>

      <!-- FEED -->
      <section class="feed" id="mainFeed">

        <!-- Create Post -->
        <form method="POST" action="dashboard.php" enctype="multipart/form-data">
          <div class="glass create-post">
            <div class="create-post-header">

              <?php
              $postAvatarPath = './assets/profile/' . ($_SESSION['avatar'] ?? 'default.png');
              if (!file_exists($postAvatarPath)) {
                $postAvatarPath = './assets/profile/default.png';
              }
              ?>
              <img class="avatar avatar--sm" src="<?= $postAvatarPath ?>" alt="">

              <div class="poster-info">
                <a href="profile.php" class="poster-name"><?= $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] ?></a>
                <p>@<?= $_SESSION['username'] ?></p>
              </div>
            </div>

            <textarea class="create-post-input" name="post_content" placeholder="What's happening in your galaxy?"></textarea>

            <!-- Media Preview Grid -->
            <div id="mediaPreviewGrid" class="media-preview-grid"></div>

            <div class="create-post-actions">
              <div class="media-actions">
                <button type="button" class="media-upload-btn photo" onclick="document.getElementById('postImageInput').click()">+ Photo</button>
                <button type="button" class="media-upload-btn video" onclick="document.getElementById('postVideoInput').click()">+ Video</button>
                <!-- Hidden File Inputs -->
                <input type="file" name="post_images[]" accept="image/*" multiple id="postImageInput" hidden>
                <input type="file" name="post_videos[]" accept="video/*" multiple id="postVideoInput" hidden>
              </div>
              <div class="minor-actions">
                <button class="icon-btn" type="button"><i class="ri-emotion-line"></i></button>
                <button class="icon-btn" type="button"><i class="ri-map-pin-line"></i></button>
              </div>
              <button class="btn btn--primary" type="submit">Post</button>
            </div>
          </div>
        </form>

        <!-- DISPLAY POSTS (original + shared) -->
        <?php
        $query = "
          SELECT
            p.*,
            u.first_name, u.last_name, u.user_name,
            ud.profile_picture,
            sp.content AS shared_content,
            sp.image_path AS shared_image_path,
            sp.video_path AS shared_video_path,
            su.first_name AS shared_first_name,
            su.last_name AS shared_last_name
          FROM posts p
          JOIN users u ON p.user_id = u.id
          LEFT JOIN user_details ud ON ud.id_fk = u.id
          LEFT JOIN posts sp ON p.shared_post_id = sp.id
          LEFT JOIN users su ON sp.user_id = su.id
          ORDER BY p.created_at DESC
        ";
        $posts = $conn->query($query);
        ?>

        <?php while ($post = $posts->fetch_assoc()): ?>
          <article class="glass post">
            <header class="post-header">
              <a href="profile.php?user=<?= urlencode($post['user_name']) ?>">
                <img class="avatar avatar--sm" src="./assets/profile/<?= htmlspecialchars($post['profile_picture'] ?? 'default.png') ?>" alt="">
              </a>
              <div>
                <!-- Make user's name a link to their profile page -->
                <a href="profile.php?user=<?= urlencode($post['user_name']) ?>" class="poster-name">
                  <?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?>
                </a>
                <div style="font-size:0.9em;color:#888;">
                  @<?= htmlspecialchars($post['user_name']) ?>
                </div>
                <time><?= date("g:i A", strtotime($post['created_at'])) ?></time>
              </div>
              <?php if ($post['user_id'] == $_SESSION['id']): ?>
                <div class="post-options" style="margin-left: auto;">
                  <button class="icon-btn toggle-options"><i class="ri-more-fill"></i></button>
                  <ul class="dropdown hidden">
                    <li><button class="btn--sm btn-edit-post" data-id="<?= $post['id'] ?>">Edit Post</button></li>
                    <li>
                      <form method="POST" action="delete_post_dashboard.php" style="display:inline;">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <button type="submit" onclick="return confirm('Delete this post?')">Delete Post</button>
                      </form>
                    </li>
                  </ul>
                </div>
              <?php endif; ?>
            </header>

            <div class="post-content" data-post-id="<?= $post['id'] ?>">
              <p class="post-text"><?= htmlspecialchars($post['content']) ?></p>
              <?php if (empty($post['shared_post_id'])): ?>
                <?php
                  $mediaStmt = $conn->prepare("SELECT file_path, media_type FROM post_media WHERE post_id = ?");
                  $mediaStmt->bind_param("i", $post['id']);
                  $mediaStmt->execute();
                  $mediaResult = $mediaStmt->get_result();
                  if ($mediaResult->num_rows > 0) {
                    echo '<div class="post-media-grid">';
                    while ($media = $mediaResult->fetch_assoc()) {
                      if ($media['media_type'] === 'image') {
                        echo '<img src="' . htmlspecialchars($media['file_path']) . '" class="post-image" alt="Post Image">';
                      } elseif ($media['media_type'] === 'video') {
                        echo '<video controls class="post-video"><source src="' . htmlspecialchars($media['file_path']) . '" type="video/mp4"></video>';
                      }
                    }
                    echo '</div>';
                  }
                  $mediaStmt->close();
                ?>
              <?php endif; ?>
            </div>

            <?php
            $imageClass = !empty($post['image_path']) ? getMediaClass($post['image_path']) : '';
            ?>

            <?php
              // Load multiple media for this post
              $mediaStmt = $conn->prepare("SELECT file_path, media_type FROM post_media WHERE post_id = ?");
              $mediaStmt->bind_param("i", $post['id']);
              $mediaStmt->execute();
              $mediaResult = $mediaStmt->get_result();
            ?>

            <!-- SHARE COUNT AND USER SHARE STATUS -->
            <?php
              $shareResult = $conn->query("SELECT COUNT(*) AS total FROM shares WHERE post_id = {$post['id']}");
              $countShares = $shareResult ? $shareResult->fetch_assoc() : ['total' => 0];

              $userSharedResult = $conn->query("SELECT 1 FROM shares WHERE post_id = {$post['id']} AND user_id = {$_SESSION['id']}");
              $userShared = $userSharedResult && $userSharedResult->num_rows > 0;
            ?>

            <!-- If shared, show shared content block -->
            <?php if (!empty($post['shared_post_id'])): ?>
              <div class="shared-post glass" style="margin-top: 10px; padding: 10px; border-left: 3px solid var(--primary); background-color: rgba(255,255,255,0.05);">
                <small>Shared from <strong><?= htmlspecialchars($post['shared_first_name'] . ' ' . $post['shared_last_name']) ?></strong></small>
                <p><?= htmlspecialchars($post['shared_content']) ?></p>

                <?php
                // Load multiple media for the shared post
                if (!empty($post['shared_post_id'])) {
                  $sharedMediaStmt = $conn->prepare("SELECT file_path, media_type FROM post_media WHERE post_id = ?");
                  $sharedMediaStmt->bind_param("i", $post['shared_post_id']);
                  $sharedMediaStmt->execute();
                  $sharedMediaResult = $sharedMediaStmt->get_result();
                  if ($sharedMediaResult->num_rows > 0) {
                    echo '<div class="post-media-grid">';
                    while ($media = $sharedMediaResult->fetch_assoc()) {
                      if ($media['media_type'] === 'image') {
                        echo '<img src="' . htmlspecialchars($media['file_path']) . '" class="post-image" alt="Shared Post Image">';
                      } elseif ($media['media_type'] === 'video') {
                        echo '<video controls class="post-video"><source src="' . htmlspecialchars($media['file_path']) . '" type="video/mp4"></video>';
                      }
                    }
                    echo '</div>';
                  }
                  $sharedMediaStmt->close();
                }
                ?>
              </div>
            <?php endif; ?>

            <footer class="post-footer">
              <div class="post-actions">
                <!-- LIKE -->
                <?php
                $likes = $conn->query("SELECT COUNT(*) AS total FROM likes WHERE post_id = {$post['id']}")->fetch_assoc();
                $liked = $conn->query("SELECT 1 FROM likes WHERE user_id = {$_SESSION['id']} AND post_id = {$post['id']}")->num_rows > 0;
                ?>
                <form method="POST" style="display:inline;" onsubmit="event.preventDefault(); return false;">
                  <input type="hidden" name="like_post_id" value="<?= $post['id'] ?>">
                  <button type="button" class="icon-btn like-button <?= $liked ? 'liked' : '' ?>" data-post-id="<?= $post['id'] ?>">
                    <i class="<?= $liked ? 'ri-heart-fill' : 'ri-heart-line' ?>"></i>
                    <span><?= $likes['total'] ?></span>
                  </button>
                </form>

                <!-- COMMENT COUNT -->
                <?php
                $comments = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE post_id = {$post['id']}")->fetch_assoc();
                ?>
                <button class="icon-btn" onclick="document.getElementById('comment-form-<?= $post['id'] ?>').classList.toggle('hidden')">
                  <i class="ri-chat-1-line"></i>
                  <span><?= $comments['total'] ?></span>
                </button>

                <!-- SHARE COUNT -->
                <?php
                  // Count shares for this post
                  $shareCountRes = $conn->query("SELECT COUNT(*) AS total FROM posts WHERE shared_post_id = " . intval($post['id']));
                  $shareCount = $shareCountRes ? $shareCountRes->fetch_assoc()['total'] : 0;
                ?>
                <form method="POST" action="share_post.php" style="display:inline;">
                  <input type="hidden" name="share_post_id" value="<?= $post['id'] ?>">
                  <button type="submit" class="icon-btn">
                    <i class="ri-share-forward-line"></i>
                    <span><?= $shareCount ?></span>
                  </button>
                </form>
              </div>
              <button class="icon-btn"><i class="ri-bookmark-line"></i></button>
            </footer>

            <!-- COMMENT FORM -->
            <div id="comment-form-<?= $post['id'] ?>" class="hidden" style="margin-top:10px;">
              <form method="POST">
                <input type="hidden" name="comment_post_id" value="<?= $post['id'] ?>">
                <input type="text" name="comment_text" placeholder="Write a comment…" required style="width: 100%; padding: 8px;">
                <button type="submit" class="btn btn--primary btn--sm" style="margin-top:5px;">Comment</button>
              </form>

              <!-- LOAD COMMENTS -->
              <div style="margin-top:10px;">
                <?php
                  $comments = $conn->query("SELECT comments.*, users.first_name, users.last_name FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = {$post['id']} ORDER BY commented_at ASC");
                  while ($comment = $comments->fetch_assoc()):
                ?>
                  <div class="comment" data-comment-id="<?= $comment['id'] ?>" style="margin-bottom: 8px;">
                    <strong><?= htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) ?>:</strong>
                    <span class="comment-text"><?= htmlspecialchars($comment['comment_text']) ?></span>
                    <small style="color:gray;"> – <?= date("M d, g:i A", strtotime($comment['commented_at'])) ?></small>

                    <?php if ($comment['user_id'] == $_SESSION['id']): ?>
                      <button class="btn--sm btn-edit-comment-dashboard" data-id="<?= $comment['id'] ?>">Edit</button>
                      <button class="btn--sm btn-delete-comment-dashboard" data-id="<?= $comment['id'] ?>">Delete</button>
                    <?php endif; ?>
                  </div>
                <?php endwhile; ?>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
      </section>

      <!-- RIGHT SIDEBAR -->
      <aside class="sidebar sidebar--right">

        <!-- Suggested Friends -->
        <section class="glass card">
          <h3 class="card-title">Friends</h3>
          <ul class="suggestions" id="suggestion_list">
            <?php
              // Fetch all users except the current user, join user_details for profile_picture
              $suggestedUsers = $conn->query("
                SELECT u.id, u.first_name, u.last_name, u.user_name, ud.profile_picture
                FROM users u
                LEFT JOIN user_details ud ON u.id = ud.id_fk
                WHERE u.id != " . intval($_SESSION['id'])
              );
              if ($suggestedUsers && $suggestedUsers->num_rows > 0):
                $count = 0;
                while ($user = $suggestedUsers->fetch_assoc()):
                  $profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.png';
                  $avatarPath = './assets/profile/' . $profilePic;
                  if (!file_exists($avatarPath)) {
                    $avatarPath = './assets/profile/default.png';
                  }
                  $isHidden = $count >= 2 ? 'hidden' : '';
            ?>
              <li class="suggestion <?= $isHidden ?>">
                <a href="profile.php?user=<?= urlencode($user['user_name']) ?>">
                  <img class="avatar avatar--sm" src="<?= htmlspecialchars($avatarPath) ?>" alt="">
                </a>
                <div class="user-meta">
                  <a href="profile.php?user=<?= urlencode($user['user_name']) ?>" style="text-decoration:none;color:inherit;">
                    <h4><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                    <p>@<?= htmlspecialchars($user['user_name']) ?></p>
                  </a>
                </div>
                <button class="btn btn--primary btn--sm">Connect</button>
              </li>
            <?php
                $count++;
                endwhile;
              else:
            ?>
              <li class="suggestion">No users to suggest.</li>
            <?php endif; ?>
          </ul>
          <button class="see-more" id="seeMoreBtn">See More</button>
        </section>
      </aside>
    </main>

    <!-- ☰ MOBILE NAV -->
    <nav class="mobile-nav">
      <a class="mobile-link mobile-link--active" href="#"><i class="ri-home-4-fill"></i><span>Home</span></a>
      <a class="mobile-link" href="#"><i class="ri-compass-3-line"></i><span>Explore</span></a>
      <a class="mobile-link" href="#"><i class="ri-message-3-line"></i><span>Messages</span></a>
      <a class="mobile-link" href="#"><i class="ri-notification-3-line"></i><span>Alerts</span></a>
      <a class="mobile-link" href="#"><i class="ri-user-line"></i><span>Profile</span></a>
    </nav>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox" style="display: none;">
      <span class="lightbox-close" onclick="closeLightbox()">×</span>
      <div class="lightbox-content" id="lightboxContent"></div>
    </div>

    <script src="./script/dashboard.js"></script>
  </body>
</html>