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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['post_content'])) {
  $user_id = $_SESSION['id'];
  $post_content = trim($_POST['post_content']);

  // Step 1: insert the post
  $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
  $stmt->bind_param("is", $user_id, $post_content);
  $stmt->execute();
  $post_id = $stmt->insert_id;
  $stmt->close();

  // Step 2: upload media
  $upload_dir = "uploads/";
  if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

  // Images
  if (!empty($_FILES['post_images']['name'][0])) {
    foreach ($_FILES['post_images']['tmp_name'] as $key => $tmp_name) {
      if ($_FILES['post_images']['error'][$key] === UPLOAD_ERR_OK) {
        $file_name = time() . '_' . basename($_FILES['post_images']['name'][$key]);
        $target = $upload_dir . $file_name;
        if (move_uploaded_file($tmp_name, $target)) {
          $mediaStmt = $conn->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, 'image')");
          $mediaStmt->bind_param("is", $post_id, $target);
          $mediaStmt->execute();
          $mediaStmt->close();
        }
      }
    }
  }

  // Videos
  if (!empty($_FILES['post_videos']['name'][0])) {
    foreach ($_FILES['post_videos']['tmp_name'] as $key => $tmp_name) {
      if ($_FILES['post_videos']['error'][$key] === UPLOAD_ERR_OK) {
        $file_name = time() . '_' . basename($_FILES['post_videos']['name'][$key]);
        $target = $upload_dir . $file_name;
        if (move_uploaded_file($tmp_name, $target)) {
          $mediaStmt = $conn->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, 'video')");
          $mediaStmt->bind_param("is", $post_id, $target);
          $mediaStmt->execute();
          $mediaStmt->close();
        }
      }
    }
  }

  header("Location: profile.php");
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

$user = $result->fetch_assoc();

$userId = $_SESSION['id'];

// Fetch all media for the user's posts from post_media table
$mediaStmt = $conn->prepare("
  SELECT pm.file_path, pm.media_type
  FROM post_media pm
  JOIN posts p ON pm.post_id = p.id
  WHERE p.user_id = ?
  ORDER BY pm.id DESC
");
$mediaStmt->bind_param("i", $userId);
$mediaStmt->execute();
$mediaResult = $mediaStmt->get_result();
$mediaPosts = $mediaResult->fetch_all(MYSQLI_ASSOC);

$albumStmt = $conn->prepare("
  SELECT a.*, COUNT(ap.id) AS media_count
  FROM albums a
  LEFT JOIN album_photos ap ON a.id = ap.album_id
  WHERE a.user_id = ?
  GROUP BY a.id
  ORDER BY a.created_at DESC
");
$albumStmt->bind_param("i", $_SESSION['id']);
$albumStmt->execute();
$albumResult = $albumStmt->get_result();
$albums = $albumResult->fetch_all(MYSQLI_ASSOC);

function getAlbumCover($albumId, $conn) {
  $stmt = $conn->prepare("SELECT file_path FROM album_photos WHERE album_id = ? ORDER BY id ASC LIMIT 1");
  $stmt->bind_param("i", $albumId);
  $stmt->execute();
  $stmt->bind_result($path);
  $stmt->fetch();
  $stmt->close();
  return $path ? $path : './assets/profile/default.png';
}

// Fetch all user images and videos for tabs
$userImages = array_filter($mediaPosts, function($m) { return $m['media_type'] === 'image'; });
$userVideos = array_filter($mediaPosts, function($m) { return $m['media_type'] === 'video'; });
// For gallery, get only the 9 latest images
$galleryImages = array_slice($userImages, 0, 9);

// Fetch all users except the current user for the friends tab
$allUsers = [];
$usersResult = $conn->query("
  SELECT u.id, u.first_name, u.last_name, u.user_name, ud.profile_picture
  FROM users u
  LEFT JOIN user_details ud ON u.id = ud.id_fk
  WHERE u.id != " . intval($_SESSION['id'])
);
if ($usersResult) {
  while ($row = $usersResult->fetch_assoc()) {
    $allUsers[] = $row;
  }
}
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

  <body class="page profile-page">
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
            <p>@<?= htmlspecialchars($user['user_name']) ?></p>
          </div>
          <div class="profile-buttons">
            <button class="btn btn--primary">Add to Story</button>
            <button class="btn btn--secondary" onclick="window.location.href='profile_edit.php'">Edit Profile</button>
          </div>
        </div>
      </div>

      <nav class="profile-tabs glass" id="profileTabs">
        <a class="tab active" href="#" data-tab="posts">Posts</a>
        <a class="tab" href="#" data-tab="friends">Friends</a>
        <a class="tab" href="#" data-tab="photos">Photos</a>
        <a class="tab" href="#" data-tab="videos">Videos</a>
        <a class="tab" href="#" data-tab="more">More</a>
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

          <!-- Gallery Section -->
          <section class="glass card">
            <h4 class="card-title">Gallery</h4>
            <div class="photo-grid">
              <?php foreach ($galleryImages as $media): ?>
                <img
                  src="<?= htmlspecialchars($media['file_path']) ?>"
                  class="gallery-item"
                  data-type="image"
                  data-src="<?= htmlspecialchars($media['file_path']) ?>"
                  alt="User Image"
                />
              <?php endforeach; ?>
            </div>
          </section>

          <!-- Albums Section -->
          <section class="glass card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
              <h4 class="card-title" style="margin: 0;">Albums</h4>
              <a href="create_album.php" class="btn btn--primary" style="font-size: 0.95em; padding: 6px 16px;">+ Create Album</a>
            </div>
            <div class="photo-grid">
              <?php if (empty($albums)): ?>
                <p style="padding: 1rem;">No albums created yet.</p>
              <?php else: ?>
                <?php foreach ($albums as $album): ?>
                  <div class="album-item" onclick="window.location.href='view_album.php?album_id=<?= $album['id'] ?>'">
                    <img src="<?= htmlspecialchars(getAlbumCover($album['id'], $conn)) ?>" alt="Album Cover" />
                    <div class="album-info">
                      <strong><?= htmlspecialchars($album['title']) ?></strong>
                      <p><?= $album['media_count'] ?> item(s)</p>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
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

              <!-- Media Preview Grid -->
              <div id="mediaPreviewGrid" class="media-preview-grid"></div>

              <div class="create-post-actions">
                <div class="media-actions">
                  <!-- Add Media Buttons -->
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
                <div class="post-options" style="margin-left: auto;">
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

              <!-- POST CONTENT -->
              <div class="post-content" data-post-id="<?= $post['post_id'] ?>">
                <p class="post-text"><?= htmlspecialchars($post['content']) ?></p>
                <?php if (empty($post['shared_post_id'])): ?>
                  <?php
                    // Load multiple media for this post (only if not a shared post)
                    $mediaStmt = $conn->prepare("SELECT file_path, media_type FROM post_media WHERE post_id = ?");
                    $mediaStmt->bind_param("i", $post['post_id']);
                    $mediaStmt->execute();
                    $mediaResult = $mediaStmt->get_result();
                    if ($mediaResult->num_rows > 0) {
                      echo '<div class="post-media-grid">';
                      while ($media = $mediaResult->fetch_assoc()) {
                        if ($media['media_type'] === 'image') {
                          echo '<img src="' . htmlspecialchars($media['file_path']) . '" class="post-image" alt="Post Image" onclick="openLightbox(\'' . htmlspecialchars($media['file_path']) . '\')">';
                        } elseif ($media['media_type'] === 'video') {
                          echo '<video controls class="post-video" onclick="openLightboxVideo(\'' . htmlspecialchars($media['file_path']) . '\')"><source src="' . htmlspecialchars($media['file_path']) . '" type="video/mp4"></video>';
                        }
                      }
                      echo '</div>';
                    }
                    $mediaStmt->close();
                  ?>
                <?php endif; ?>
              </div>

              <!-- SHARED POST (if any) -->
              <?php if ($post['shared_post_id']): ?>
                <div class="shared-post glass" style="padding: 10px; background-color: rgba(255, 255, 255, 0.05); border-left: 3px solid var(--primary); border-radius: 10px; margin-bottom: 10px;">
                  <small>Shared from <strong><?= htmlspecialchars($post['shared_first_name'] . ' ' . $post['shared_last_name']) ?></strong></small>
                  <?php
                    // Show shared post caption above media grid
                    if (!empty($post['shared_content'])) {
                      echo '<p>' . htmlspecialchars($post['shared_content']) . '</p>';
                    }
                    // Load multiple media for the shared post
                    $sharedMediaStmt = $conn->prepare("SELECT file_path, media_type FROM post_media WHERE post_id = ?");
                    $sharedMediaStmt->bind_param("i", $post['shared_post_id']);
                    $sharedMediaStmt->execute();
                    $sharedMediaResult = $sharedMediaStmt->get_result();
                    if ($sharedMediaResult->num_rows > 0) {
                      echo '<div class="post-media-grid">';
                      while ($media = $sharedMediaResult->fetch_assoc()) {
                        if ($media['media_type'] === 'image') {
                          echo '<img src="' . htmlspecialchars($media['file_path']) . '" class="post-image" alt="Shared Post Image" onclick="openLightbox(\'' . htmlspecialchars($media['file_path']) . '\')">';
                        } elseif ($media['media_type'] === 'video') {
                          echo '<video controls class="post-video" onclick="openLightboxVideo(\'' . htmlspecialchars($media['file_path']) . '\')"><source src="' . htmlspecialchars($media['file_path']) . '" type="video/mp4"></video>';
                        }
                      }
                      echo '</div>';
                    }
                    $sharedMediaStmt->close();
                  ?>
                </div>
              <?php endif; ?>

              <!-- FOOTER -->
              <footer class="post-footer">
                <div class="post-actions">
                  <!-- Like -->
                  <form method="POST" class="like-form" style="display:inline;">
                    <input type="hidden" name="like_post_id" value="<?= $post['post_id'] ?>">
                    <button type="button" class="icon-btn like-button <?= $userLiked ? 'liked' : '' ?>" data-post-id="<?= $post['post_id'] ?>">
                      <i class="<?= $userLiked ? 'ri-heart-fill' : 'ri-heart-line' ?>"></i>
                      <span><?= $countLikes['total'] ?></span>
                    </button>
                  </form>

                  <!-- Comment toggle -->
                  <button class="icon-btn" onclick="document.getElementById('comment-form-<?= $post['post_id'] ?>').classList.toggle('hidden')">
                    <i class="ri-chat-1-line"></i>
                    <span><?= $countComments['total'] ?></span>
                  </button>

                  <!-- Share -->
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

      <!-- Add tab content containers below main grid -->
      <div id="tab-photos" class="profile-tab-content" style="display:none;">
        <section class="glass card">
          <h4 class="card-title">All Photos</h4>
          <div class="photo-grid">
            <?php foreach ($userImages as $media): ?>
              <img
                src="<?= htmlspecialchars($media['file_path']) ?>"
                class="gallery-item"
                data-type="image"
                data-src="<?= htmlspecialchars($media['file_path']) ?>"
                alt="User Image"
              />
            <?php endforeach; ?>
          </div>
        </section>
      </div>
      <div id="tab-videos" class="profile-tab-content" style="display:none;">
        <section class="glass card">
          <h4 class="card-title">All Videos</h4>
          <div class="photo-grid">
            <?php foreach ($userVideos as $media): ?>
              <video
                class="gallery-item"
                muted
                data-type="video"
                data-src="<?= htmlspecialchars($media['file_path']) ?>"
              >
                <source src="<?= htmlspecialchars($media['file_path']) ?>" type="video/mp4" />
              </video>
            <?php endforeach; ?>
          </div>
        </section>
      </div>

      <!-- Friends Tab Content -->
      <div id="tab-friends" class="profile-tab-content" style="display:none;">
        <section class="glass card">
          <h4 class="card-title">All Users</h4>
          <ul style="list-style:none; padding:0; margin:0;">
            <?php foreach ($allUsers as $user): ?>
              <?php
                $profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.png';
              ?>
              <li style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                <img class="avatar avatar--sm" src="./assets/profile/<?= htmlspecialchars($profilePic) ?>" alt="">
                <div>
                  <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                  <div style="font-size:0.85em;color:#aaa;">@<?= htmlspecialchars($user['user_name']) ?></div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      </div>
    </main>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox" style="display:none;">
      <span class="lightbox-close" onclick="closeLightbox()">×</span>
      <div class="lightbox-content" id="lightboxContent"></div>
    </div>

    <script>
      // Tab switching logic
      const tabs = document.querySelectorAll('#profileTabs .tab');
      const tabContents = {
        posts: document.querySelector('.profile-main-grid'),
        friends: document.getElementById('tab-friends'),
        photos: document.getElementById('tab-photos'),
        videos: document.getElementById('tab-videos'),
        more: null // implement if needed
      };
      tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
          e.preventDefault();
          tabs.forEach(t => t.classList.remove('active'));
          tab.classList.add('active');
          Object.values(tabContents).forEach(c => { if (c) c.style.display = 'none'; });
          const tabKey = tab.getAttribute('data-tab');
          if (tabKey === 'posts') {
            tabContents.posts.style.display = '';
          } else if (tabContents[tabKey]) {
            tabContents[tabKey].style.display = '';
          }
        });
      });
    </script>
    <script src="./script/dashboard.js"></script>
  </body>
</html>