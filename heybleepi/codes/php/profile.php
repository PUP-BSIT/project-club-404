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

// Use the username from the URL if present, otherwise use the logged-in user
$username = isset($_GET['user']) ? $_GET['user'] : $_SESSION['username'];

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

$user = $result->fetch_assoc();
$userId = $user['id'];

// --- MOVE POST/COMMENT HANDLERS HERE, BEFORE ANY HTML OUTPUT ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Handle creating a post (only allow if viewing own profile)
  if (isset($_POST['post_content']) && $userId == $_SESSION['id']) {
    $post_content = trim($_POST['post_content']);
    $location = $_POST['location'] ?? null;
    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    // Insert post first to get post_id
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, location) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $post_content, $location);
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

    // Redirect to correct profile
    if ($userId == $_SESSION['id']) {
      header("Location: profile.php");
    } else {
      header("Location: profile.php?user=" . urlencode($user['user_name']));
    }
    exit();
  }

  // Handle commenting on a post (allow for logged-in user)
  if (isset($_POST['comment_post_id'], $_POST['comment_text'])) {
    $post_id = intval($_POST['comment_post_id']);
    $comment = trim($_POST['comment_text']);

    if (!empty($comment)) {
      $stmt = $conn->prepare("INSERT INTO comments (user_id, post_id, comment_text) VALUES (?, ?, ?)");
      $stmt->bind_param("iis", $_SESSION['id'], $post_id, $comment);
      $stmt->execute();
      $stmt->close();

      // Add notification to post owner
      $ownerStmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
      $ownerStmt->bind_param("i", $post_id);
      $ownerStmt->execute();
      $ownerStmt->bind_result($owner_id);
      $ownerStmt->fetch();
      $ownerStmt->close();

      if ($owner_id != $_SESSION['id']) {
        $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, actor_id, post_id, type) VALUES (?, ?, ?, 'comment')");
        $notifStmt->bind_param("iii", $owner_id, $_SESSION['id'], $post_id);
        $notifStmt->execute();
        $notifStmt->close();
      }
    }

    // Redirect to correct profile
    if ($userId == $_SESSION['id']) {
      header("Location: profile.php");
    } else {
      header("Location: profile.php?user=" . urlencode($user['user_name']));
    }
    exit();
  }
}

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

// Fetch albums for the profile owner
$albumStmt = $conn->prepare("
  SELECT a.*, COUNT(ap.id) AS media_count
  FROM albums a
  LEFT JOIN album_photos ap ON a.id = ap.album_id
  WHERE a.user_id = ?
  GROUP BY a.id
  ORDER BY a.created_at DESC
");
$albumStmt->bind_param("i", $userId);
$albumStmt->execute();
$albumResult = $albumStmt->get_result();
$albums = $albumResult->fetch_all(MYSQLI_ASSOC);

function getAlbumCover($albumId, $conn) {
  $path = null;
  $stmt = $conn->prepare("SELECT file_path FROM album_photos WHERE album_id = ? ORDER BY id ASC LIMIT 1");
  $stmt->bind_param("i", $albumId);
  $stmt->execute();
  $stmt->bind_result($path);
  $stmt->fetch();
  $stmt->close();
  return $path ? $path : '../assets/profile/default.png';
}

// Fetch all user images and videos for tabs
$userImages = array_filter($mediaPosts, function($m) { return $m['media_type'] === 'image'; });
$userVideos = array_filter($mediaPosts, function($m) { return $m['media_type'] === 'video'; });
// For gallery, get only the 9 latest images
$galleryImages = array_slice($userImages, 0, 10);

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

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return $diff . "s";
    } elseif ($diff < 3600) {
        return floor($diff / 60) . "m";
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . "h";
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . "d";
    } else {
        return floor($diff / 604800) . "w";
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
    <link rel="stylesheet" href="../stylesheet/dashboard.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=close" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  </head>

  <body class="page profile-page">
    <!-- Sidebar Navigation -->
    <aside class="sidebar sidebar--icononly">
      <!-- Logo at the top -->
      <div class="sidebar-logo">
        <img src="../assets/logo-hb.png" alt="HEYBLEEPI Logo" style="width:36px;height:36px;">
      </div>

      <nav class="sidebar-nav">
        <a class="sidebar-icon-link <?= basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : '' ?>"
          href="search.php"
          title="Search">
          <i class="ri-search-line"></i>
        </a>
        <a class="sidebar-icon-link <?= basename($_SERVER['PHP_SELF']) === 'notification.php' ? 'active' : '' ?>"
          href="notification.php"
          title="Notifications">
          <i class="ri-notification-3-line"></i>
          <?php if ($unread_count > 0): ?>
            <span class="badge" id="notification_count"><?= $unread_count ?></span>
          <?php endif; ?>
        </a>
        <a class="sidebar-icon-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php" title="Home">
          <i class="ri-home-4-line"></i>
        </a>
        <a class="sidebar-icon-link <?= basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : '' ?>" href="messages.php" title="Messages">
          <i class="ri-message-3-line"></i>
          <?php if ($unreadMessages > 0): ?>
            <span class="sidebar-badge"></span>
          <?php endif; ?>
        </a>
        <a class="sidebar-icon-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>" href="profile.php" title="Profile">
          <i class="ri-user-line"></i>
        </a>
      </nav>

      <button class="sidebar-more-btn" id="sidebarMoreBtn" title="More">
        <i class="ri-menu-line"></i>
      </button>
    </aside>

    <div class="notification-dropdown" id="notification_dropdown">
      <h4>Notifications</h4>
      <ul>
        <?php if (empty($notifications)): ?>
          <li>No new notifications.</li>
        <?php else: ?>
          <?php foreach ($notifications as $notification): ?>
            <li>
              <strong><?= htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']) ?></strong>
              <?php if ($notification['type'] === 'like'): ?>
                liked your post.
              <?php elseif ($notification['type'] === 'comment'): ?>
                commented on your post.
              <?php elseif ($notification['type'] === 'share'): ?>
                shared your post.
              <?php else: ?>
                <?= htmlspecialchars($notification['type']) ?> your post.
              <?php endif; ?>
              <br><small><?= date("M d, g:i A", strtotime($notification['created_at'])) ?></small>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
      <form method="POST" action="mark_notifications_read.php">
        <button class="mark-read" type="submit" name="mark_read" id="markAllReadBtn">Mark all as read</button>
      </form>
    </div>

    <!-- More Menu Popup -->
    <div id="sidebarMoreMenu" class="sidebar-more-menu hidden">
      <ul>
        <li>
          <a href="settings.php"><i class="ri-settings-4-line"></i> Settings</a>
        </li>
        <li>
          <a href="logout.php" style="color:#ff4d4f;"><i class="ri-logout-box-line"></i> Log out</a>
        </li>
      </ul>
    </div>


    <!-- Main Layout -->
    <main class="profile-container">
      <!-- Banner + Profile info -->
      <div class="profile-top glass">
        <img class="banner-img" src="../assets/profile/<?= htmlspecialchars($user['profile_cover'] ?? 'banner.jpg') ?>" alt="Banner" />
        <div class="profile-info-bar">
          <img class="avatar avatar--sm2" src="../assets/profile/<?= htmlspecialchars($user['profile_picture'] ?? 'rawr.png') ?>" alt="">
          <div class="user-basic-info">
            <h2><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h2>
            <p>@<?= htmlspecialchars($user['user_name']) ?></p>
          </div>
          <?php if ($userId == $_SESSION['id']): ?>
          <div class="profile-buttons">
            <button class="profile-btn profile-btn--edit" onclick="window.location.href='profile_edit.php'">
              <i class="ri-pencil-line"></i> Edit profile
            </button>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <nav class="profile-tabs glass" id="profileTabs">
        <a class="tab active" href="#" data-tab="posts">Posts</a>
        <a class="tab" href="#" data-tab="friends">Users</a>
        <a class="tab" href="#" data-tab="photos">Photos</a>
        <a class="tab" href="#" data-tab="videos">Videos</a>
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
        </aside>

        <!-- Create Post -->
        <section class="right-column">
          <?php if ($userId == $_SESSION['id']): ?>
          <div class="glass create-post">
            <form>
              <div class="create-post-header">
                <img class="avatar avatar--sm" src="../assets/profile/<?= htmlspecialchars($user['profile_picture'] ?? 'rawr.png') ?>" alt="">
                <div class="poster-info">
                  <a href="profile.php" class="poster-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></a>
                  <p>@<?= htmlspecialchars($user['user_name']); ?></p>
                </div>
              </div>

              <textarea
                class="create-post-input"
                name="post_content"
                placeholder="What's happening in your galaxy?"
                onClick="showCreatePostPreview();"
              ></textarea>

              <!-- Media Preview Grid -->
              <div class="create-post-actions">
                <div class="media-actions">
                  <button
                    type="button"
                    class="media-upload-btn photo"
                    onclick="showCreatePostPreview();">
                      + Photo
                  </button>
                  <button
                    type="button"
                    class="media-upload-btn video"
                    onclick="showCreatePostPreview();">
                      + Video
                  </button>
                </div>
                <div class="minor-actions">
                  <button
                    class="icon-btn"
                    type="button"
                    id="getLocationBtn"
                    title="Add location"
                    onClick="showCreatePostPreview();">
                    <i class="ri-map-pin-line"></i>
                  </button>
                </div>
                <button
                  class="btn btn--action"
                  onClick="showCreatePostPreview();";
                  type="button">
                    Post
                </button>
              </div>
            </form>
          </div>

          <!-- Map Location Modal -->
          <div id="mapModal" class="map-modal" style="display:none;">
            <div class="map-modal-content">
              <span id="cancelMapModal" class="close-button">&times;</span>

              <div id="geocoder" style="margin-bottom: 10px;"></div>
              <div id="map" style="height: 400px; border-radius: 12px;"></div>

              <div style="text-align: right; margin-top: 1rem;">
                <button id="confirmLocationBtn" class="btn btn--primary">Use This Location</button>
              </div>
            </div>
          </div>

          <!-- Create Post Preview -->
          <div id="post_preview_overlay" class="post-preview-overlay hidden">
            <div class="create-post-preview-container">
              <div id="create_post_preview" class="create-post-preview">
                <span
                  id="close_preview_btn"
                  class="material-symbols-outlined"
                  onClick="closeCreatePostPreview()">close</span>
                <form
                  method="POST"
                  action="profile.php"
                  enctype="multipart/form-data"
                  class="preview-form">
                  <h1>Create Post</h1>
                  <div
                    id="create_post_input"
                    class="create-post-div"
                    contenteditable="true"
                    onInput="updateHiddenInput(); isTextAreaEmpty();"
                    data-placeholder="What's happening in your galaxy?"></div>
                  <input type="hidden" name="post_content" id="post_content_hidden">

                  <!-- WYSWYG -->
                  <div>
                    <button
                      type="button"
                      class="wyswyg-btn"
                      title="Bold"
                      onClick="formatText('bold')"><b>b</b></button>
                    <button
                      type="button"
                      class="wyswyg-btn"
                      title="Italic"
                      onClick="formatText('italic')"><i>I</i></button>
                    <button
                      type="button"
                      class="wyswyg-btn"
                      title="Underline"
                      onClick="formatText('underline')"><u>U</u></button>
                  </div>

                  <!-- Media Preview Grid -->
                  <div id="mediaPreviewGrid" class="media-preview-grid"></div>

                  <!-- Buttons container -->
                  <div id="buttons_container" class="buttons-container">
                    <button type="button" class="media-upload-btn photo" onclick="document.getElementById('postImageInput').click()">+ Photo</button>
                    <button type="button" class="media-upload-btn video" onclick="document.getElementById('postVideoInput').click()">+ Video</button>
                    <input type="hidden" name="location" id="postLocation">
                    <button id="openMapModal" type="button" class="btn btn--action">
                      <i class="ri-map-pin-user-line"></i> Select Location
                    </button>

                    <input type="file" name="post_images[]" accept="image/*" multiple id="postImageInput" hidden>
                    <input type="file" name="post_videos[]" accept="video/*" multiple id="postVideoInput" hidden>

                    <button
                      type="submit"
                      class="btn btn--primary disabled"
                      id="post_preview_submit_btn"
                      disabled>
                        Post
                    </button>
                  </div>

                  <div class="styled-hr"></div>

                  <!-- Other social media -->
                  <div class="share-options">
                    <a href="#" class="share-to-other">Share to DevHive</a>
                    <a href="#" class="share-to-other">Share to Hershive</a>
                  </div>
                </form>
              </div>
              <div>
                <!-- Location Text Preview -->
                <div class="create-post-location-preview" id="locationTextPreview" style="display: none;">
                  📍 <span id="locationNamePreview">Selected location</span>
                </div>

                <!-- Location Map Preview -->
                <div id="locationMapPreviewContainer" style="display:none; position: relative; margin: 12px 0;">
                  <div id="locationMapPreview"></div>
                  <button type="button" id="removeLocationBtn" class="remove-location-btn" title="Remove location">&times;</button>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Share post to other social media modal -->
          <div id="share_preview_overlay" class="post-preview-overlay hidden">
            <!-- Share post container -->
            <div class="share-post-container">
              <div class="share-post-preview">
                <span
                  id="close_shared_preview_btn"
                  class="material-symbols-outlined"
                  onClick="closeSharePostPreview()">close</span>
                <form method="POST" action="share_post.php" class="preview-form">
                  <h1>Share Post</h1>
                  <div
                    id="share_post_input"
                    class="create-post-div"
                    contenteditable="true"
                    onInput="updateHiddenInputShare();"
                    data-placeholder="What's happening in your galaxy?"></div>
                  <input type="hidden" name="share_post_content" id="share_post_content_hidden">
                  <input type="hidden" name="share_post_id" id="share_post_id_modal">

                  <!-- WYSWYG -->
                  <div>
                    <button
                      type="button"
                      class="wyswyg-btn"
                      title="Bold"
                      onClick="formatText('bold')"><b>b</b></button>
                    <button
                      type="button"
                      class="wyswyg-btn"
                      title="Italic"
                      onClick="formatText('italic')"><i>I</i></button>
                    <button
                      type="button"
                      class="wyswyg-btn"
                      title="Underline"
                      onClick="formatText('underline')"><u>U</u></button>
                  </div>

                  <!-- Post Container: The post to be shared. -->
                  <div class="user-post-container">
                    <h4>You are sharing a post by <span id="sharedFullname"></span></h4>
                  </div>

                  <!-- INTERNAL SHARING -->
                  <button 
                    type="submit" 
                    class="btn btn--primary"
                    id="post_preview_submit_btn">
                      Share Now
                  </button>

                  <div class="styled-hr"></div>
                </form>

                <!-- EXTERNAL SHARING -->
                <form action="create-post.php" method="POST">
                    <input type="hidden" name="share_post_id" id="share_post_id_external">
                  
                    <div class="share-options">
                      <button type="submit" name="share_to_other" value="devhive" class="share-to-other">Share to DevHive</button>
                      <button type="submit" name="share_to_other" value="hershive" class="share-to-other">Share to Hershive</button>
                    </div>
                </form>
              </div>
            </div>
          </div>

          <!-- Posts will appear here -->
          <?php
          $query = "
            SELECT
              p.id AS post_id,
              p.content,
              p.created_at,
              p.shared_post_id,
              p.image_path,
              p.video_path,
              p.location,
              u.first_name,
              u.last_name,
              u.user_name,
              sp.content AS shared_content,
              sp.video_path AS shared_video_path,
              sp.image_path AS shared_image_path,
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
          $stmt->bind_param("i", $userId);
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
                <img class="avatar avatar--sm" src="../assets/profile/<?= htmlspecialchars($user['profile_picture'] ?? 'rawr.png') ?>" alt="User Avatar">
                <div class="poster-meta" style="display: flex; align-items: center; gap: 8px;">
                  <h4 style="margin:0; font-size:1.08em;"><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></h4>
                  <span title="Posted at:  <?= date("F j, Y g:i A", strtotime($post['created_at']))?>" class="post-time" style="color:#aaa; font-size:0.98em;">
                    <?= timeAgo($post['created_at']) ?>
                  </span>
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
                <p class="post-text"><?= $post['content'] ?></p>

                <?php if (!empty($post['location'])): ?>
                  <div class="post-location" style="margin: 8px 0;">
                    <div id="postMap<?= $post['post_id'] ?>" style="width:150%;height:220px;border-radius:10px;"></div>
                    <div style="font-size:0.9em;color:#aaa;margin-top:4px;">
                      <i class="ri-map-pin-user-line"></i> <?= htmlspecialchars($post['location']) ?>
                    </div>
                  </div>
                <?php endif; ?>

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
                      echo '<p>' . $post['shared_content'] . '</p>';
                    }
                  ?>

                  <?php if (!empty($post['shared_location'])): ?>
                    <div class="post-location" style="margin: 8px 0;">
                      <div id="postMap<?= $post['post_id'] ?>_shared" style="width:100%;height:220px;border-radius:10px;"></div>
                      <div style="font-size:0.9em;color:#aaa;margin-top:4px;">
                        <i class="ri-map-pin-user-line"></i> <?= htmlspecialchars($post['shared_location']) ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php
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
                  <form style="display:inline;">
                    <button type="button" class="icon-btn"
                      onClick="showSharePostPreview(
                        <?= $post['post_id'] ?>,
                        '<?= htmlspecialchars($post['first_name']) ?>',
                        '<?= htmlspecialchars($post['last_name']) ?>'
                      )">
                      <i class="ri-share-forward-line"></i>
                      <span><?= $countShares['total'] ?></span>
                    </button>
                  </form>
                  </div>
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
                        <div class="comment-options">
                          <button class="icon-btn toggle-comment-options" aria-label="Options">
                            <i class="ri-more-fill"></i>
                          </button>
                          <ul class="comment-dropdown hidden">
                            <li><button class="btn--sm btn-edit-comment" data-id="<?= $comment['id'] ?>">Edit</button></li>
                            <li><button class="btn--sm btn-delete-comment" data-id="<?= $comment['id'] ?>">Delete</button></li>
                          </ul>
                        </div>

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
                $profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'rawr.png';
              ?>
              <li style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                <img class="avatar avatar--sm" src="../assets/profile/<?= htmlspecialchars($profilePic) ?>" alt="">
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../script/dashboard.js"></script>
  </body>
</html>