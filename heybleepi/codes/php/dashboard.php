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
  $location = $_POST['location'] ?? null;
  $upload_dir = "uploads/";
  if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

  $stmt = $conn->prepare("INSERT INTO posts (user_id, content, location) VALUES (?, ?, ?)");
  $stmt->bind_param("iss", $user_id, $post_content, $location);
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

// SHARE A POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['share_post_id'], $_POST['share_post_content'])) {
  $original_post_id = intval($_POST['share_post_id']);
  $share_caption = trim($_POST['share_post_content']);
  $user_id = $_SESSION['id'];

  // Insert new post with shared_post_id pointing to the original
  $location = $_POST['location'] ?? null;
  $stmt = $conn->prepare("INSERT INTO posts (user_id, content, shared_post_id, location) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("isis", $user_id, $share_caption, $original_post_id, $location);
  $stmt->execute();
  $new_post_id = $stmt->insert_id;
  $stmt->close();

  // Get owner of original post
  $ownerStmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
  $ownerStmt->bind_param("i", $original_post_id);
  $ownerStmt->execute();
  $ownerStmt->bind_result($postOwnerId);
  $ownerStmt->fetch();
  $ownerStmt->close();

  // Notify if not sharing own post
  if ($postOwnerId && $postOwnerId != $user_id) {
    $type = 'share';
    $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, actor_id, post_id, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    $notifStmt->bind_param("iiis", $postOwnerId, $user_id, $original_post_id, $type);
    $notifStmt->execute();
    $notifStmt->close();
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Home • Heybleepi</title>
    <link rel="icon" href="../assets/logo.png" type="image/png" />
    <link rel="stylesheet" href="../stylesheet/dashboard.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=close" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body class="page">
    <div id="logoutConfirmModal" class="modal hidden">
      <div class="modal-content glass">
        <h3>Log out</h3>
        <p>Are you sure you want to logout?</p>
        <div class="modal-actions">
          <a href="logout.php" class="btn-danger" style="text-decoration: none;">Log out</a>
          <button type="button" class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Main Layout -->
    <main class="layout" style="padding-top:0;">
        <!-- LEFT SIDEBAR -->
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
            <a class="sidebar-icon-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>" href="profile.php?user_id=<?= $_SESSION['id']?>" title="Profile">
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
              <button onclick="openLogoutModal()" class="sidebar-more-menu-btn logout">
                <i class="ri-logout-box-line"></i> Log out
              </button>
            </li>
          </ul>
        </div>

        <!-- FEED -->
        <section class="feed" id="mainFeed">

          <!-- Create Post -->
          <form class="simple-create-post" autocomplete="off" onsubmit="return false;">
            <div class="simple-create-post-inner">
              <?php
                $currentUserId = $_SESSION['id'];
              
                $query = $conn->prepare("SELECT profile_picture from user_details WHERE id_fk = ?");
                $query->bind_param("i", $currentUserId);
                $query->execute();
                $query->bind_result($profilePicture);
                $query->fetch();
                $query->close();
              
                $postAvatarPath = '../assets/profile/' . ($profilePicture ?? 'default.png');
                if (!file_exists($postAvatarPath)) {
                  $postAvatarPath = '../assets/profile/default.png';
                }
              ?>
              <img class="avatar avatar--sm" src="<?= $postAvatarPath ?>" alt="Profile">
              <input
                type="text"
                class="simple-create-post-input"
                placeholder="What's new, <?= $_SESSION['first_name']?>?"
                autocomplete="off"
                readonly
                onclick="openCreatePostPreview();"
                style="cursor:pointer;"
              >
              <button
                type="button"
                class="btn btn--primary simple-post-btn"
                onclick="openCreatePostPreview();"
              >Post</button>
            </div>
          </form>

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

          <!-- Create Post Preview / Share post OAuth -->
          <div id="post_preview_overlay" class="post-preview-overlay hidden">
            <div class="create-post-preview-container">
              <div id="create_post_preview" class="create-post-preview">
                <span
                  id="close_preview_btn"
                  class="material-symbols-outlined"
                  onClick="closeCreatePostPreview();">close</span>
                <form
                  method="POST"
                  action="dashboard.php"
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

          <div class="popup-modal hidden">
            <h4>Shared Success to Hershive</h4>
          </div>

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
                  <input type="hidden" name="share_post_id" id="share_post_id_internal">
                  <input type="hidden" name="location" id="shareLocationInput">

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

                  <!-- Internal Sharing -->
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

          <!-- DISPLAY POSTS (original + shared) -->
          <?php
          $query = "
            SELECT
              p.id as post_id,
              p.user_id,
              p.content,
              p.created_at,
              p.shared_post_id,
              p.image_path,
              p.video_path,
              p.location,
              p.post_provider,
              u.id,
              u.first_name, u.last_name, u.user_name,
              ud.profile_picture,
              sp.content AS shared_content,
              sp.image_path AS shared_image_path,
              sp.video_path AS shared_video_path,
              sp.location AS shared_location,
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
                <a href="profile.php?user=<?= urlencode($post['user_name']) ?>&user_id=<?= $post['id']?>">
                  <img class="avatar avatar--sm" src="../assets/profile/<?= htmlspecialchars($post['profile_picture'] ?? 'default.png') ?>" alt="">
                </a>
                <div class="poster-meta">
                  <a href="profile.php?user=<?= urlencode($post['user_name']) ?>&user_id=<?= $post['id']?>" class="poster-name" style="display:inline;">
                    <?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?>
                  </a>
                  <span title="Posted at: <?= date("F j, Y g:i A", strtotime($post['created_at']))?>" class="post-time" style="color:#aaa; font-size:0.98em; margin-left:8px;">
                    <?= timeAgo($post['created_at']) ?> 
                  </span>
                </div>

                <?php if ($post['user_id'] == $_SESSION['id']): ?>
                  <div class="post-options" style="margin-left: auto;">
                    <button class="icon-btn toggle-options"><i class="ri-more-fill"></i></button>
                    <ul class="dropdown hidden">
                      <li><button class="btn--sm btn-edit-post" data-id="<?= $post['post_id'] ?>">Edit Post</button></li>
                      <li>
                        <button type="button" class="btn-delete-post" data-id="<?= $post['id'] ?>">Delete Post</button>
                      </li>
                    </ul>
                  </div>
                <?php endif; ?>
              </header>

              <div class="post-content" data-post-id="<?= $post['post_id'] ?>">
                <?php if (!empty($post['post_provider'])): ?>
                  <p class="post-text-auth">
                    <strong>Original post from <i><?= htmlspecialchars($post['post_provider']) ?></i></strong>
                  </p>
                <?php endif; ?>
                <p class="post-text"><?= $post['content'] ?></p>

                <?php if (!empty($post['location'])): ?>
                  <div class="post-location" style="margin: 8px 0;">
                    <div id="postMap<?= $post['post_id'] ?>" style="width:100%;height:220px;border-radius:10px;"></div>
                    <div style="font-size:0.9em;color:#aaa;margin-top:4px;">
                      <i class="ri-map-pin-user-line"></i> <?= htmlspecialchars($post['location']) ?>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if (empty($post['shared_post_id'])): ?>
                  <?php
                    $mediaStmt = $conn->prepare("SELECT file_path, media_type FROM post_media WHERE post_id = ?");
                    $mediaStmt->bind_param("i", $post['post_id']);
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
                $mediaStmt->bind_param("i", $post['post_id']);
                $mediaStmt->execute();
                $mediaResult = $mediaStmt->get_result();
              ?>

              <!-- SHARE COUNT AND USER SHARE STATUS -->
              <?php
                $shareResult = $conn->query("SELECT COUNT(*) AS total FROM shares WHERE post_id = {$post['post_id']}");
                $countShares = $shareResult ? $shareResult->fetch_assoc() : ['total' => 0];

                $userSharedResult = $conn->query("SELECT 1 FROM shares WHERE post_id = {$post['post_id']} AND user_id = {$_SESSION['id']}");
                $userShared = $userSharedResult && $userSharedResult->num_rows > 0;
              ?>

              <!-- If shared, show shared content block -->
              <?php if ($post['shared_post_id']): ?>
                <div class="shared-post glass" style="padding: 10px; background-color: rgba(255, 255, 255, 0.05); border-left: 3px solid var(--primary); border-radius: 10px; margin-bottom: 10px;">
                  <small>Shared from <strong><?= htmlspecialchars($post['shared_first_name'] . ' ' . $post['shared_last_name'])?></strong></small>
                  <p><?= $post['shared_content'] ?></p>

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

              <footer class="post-footer">
                <div class="post-actions">
                  <!-- LIKE -->
                  <?php
                  $likes = $conn->query("SELECT COUNT(*) AS total FROM likes WHERE post_id = {$post['post_id']}")->fetch_assoc();
                  $liked = $conn->query("SELECT 1 FROM likes WHERE user_id = {$_SESSION['id']} AND post_id = {$post['post_id']}")->num_rows > 0;
                  ?>
                  <form method="POST" style="display:inline;" onsubmit="event.preventDefault(); return false;">
                    <input type="hidden" name="like_post_id" value="<?= $post['post_id'] ?>">
                    <button type="button" class="icon-btn like-button <?= $liked ? 'liked' : '' ?>" data-post-id="<?= $post['post_id'] ?>">
                      <i class="<?= $liked ? 'ri-heart-fill' : 'ri-heart-line' ?>"></i>
                      <span><?= $likes['total'] ?></span>
                    </button>
                  </form>

                  <!-- COMMENT COUNT -->
                  <?php
                  $comments = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE post_id = {$post['post_id']}")->fetch_assoc();
                  ?>
                  <button class="icon-btn" onclick="document.getElementById('comment-form-<?= $post['post_id'] ?>').classList.toggle('hidden')">
                    <i class="ri-chat-1-line"></i>
                    <span><?= $comments['total'] ?></span>
                  </button>

                   <!-- SHARE COUNT -->
                  <?php
                    // Count shares for this post
                    $shareCountRes = $conn->query("SELECT COUNT(*) AS total FROM shares WHERE post_id = " . intval($post['post_id']));
                    $shareCount = $shareCountRes ? $shareCountRes->fetch_assoc()['total'] : 0;
                  ?>

                  <!-- Get the post creator name -->
                  <?php
                    $postId = $post['post_id'];
                    $stmt = $conn->prepare("
                      SELECT users.first_name, users.last_name
                      FROM posts
                      JOIN users ON posts.user_id = users.id
                      WHERE posts.id = ?
                    ");

                    $stmt->bind_param("i", $postId);
                    $stmt->execute();
                    $stmt->bind_result($firstName, $lastName);
                    $stmt->fetch();
                    $stmt->close();

                    $postCreator = [
                      'first_name' => $firstName,
                      'last_name' => $lastName
                    ];
                  ?>

                  <form style="display:inline;">
                    <button type="button" class="icon-btn"
                      onClick="showSharePostPreview(
                        <?= $post['post_id'] ?>,
                        '<?= htmlspecialchars($postCreator['first_name'] ?? $post['shared_first_name']) ?>',
                        '<?= htmlspecialchars($postCreator['last_name'] ?? $post['shared_last_name']) ?>'
                      )">
                      <i class="ri-share-forward-line"></i>
                      <span><?= $shareCount ?></span>
                    </button>
                  </form>
                </div>
              </footer>

              <!-- COMMENT FORM -->
              <div id="comment-form-<?= $post['post_id'] ?>" class="hidden" style="margin-top:10px;">
                <form method="POST">
                  <input type="hidden" name="comment_post_id" value="<?= $post['post_id'] ?>">
                  <input type="text" name="comment_text" placeholder="Write a comment…" required style="width: 100%; padding: 8px;">
                  <button type="submit" class="btn btn--primary btn--sm" style="margin-top:5px;">Comment</button>
                </form>

                <!-- LOAD COMMENTS -->
                <div style="margin-top:10px;">
                  <?php
                    $comments = $conn->query("SELECT comments.*, users.first_name, users.last_name FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = {$post['post_id']} ORDER BY commented_at ASC");
                    while ($comment = $comments->fetch_assoc()):
                  ?>
                    <div class="comment" data-comment-id="<?= $comment['id'] ?>" style="margin-bottom: 8px;">
                      <strong><?= htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) ?>:</strong>
                      <span class="comment-text"><?= htmlspecialchars($comment['comment_text']) ?></span>
                      <small style="color:gray;"> – <?= date("M d, g:i A", strtotime($comment['commented_at'])) ?></small>

                      <?php if ($comment['user_id'] == $_SESSION['id']): ?>
                        <div class="comment-options">
                          <button class="icon-btn toggle-comment-options" aria-label="Options">
                            <i class="ri-more-fill"></i>
                          </button>
                          <ul class="comment-dropdown hidden">
                            <li><button class="btn-edit-comment-dashboard" data-id="<?= $comment['id'] ?>">Edit</button></li>
                            <li><button class="btn-delete-comment-dashboard" data-id="<?= $comment['id'] ?>">Delete</button></li>
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

    <!-- DELETE CONFIRMATION MODAL -->
    <div id="deleteConfirmModal" class="modal hidden">
      <div class="modal-content glass">
        <h3>Delete Post?</h3>
        <p>Are you sure you want to delete this post? This action cannot be undone.</p>
        <form id="deleteForm" method="POST" action="delete_post_dashboard.php">
          <input type="hidden" name="post_id" id="deletePostId">
          <div class="modal-actions">
            <button type="submit" class="btn-danger">Delete</button>
            <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      document.querySelectorAll('.btn-delete-post').forEach(button => {
        button.addEventListener('click', () => {
          const postId = button.dataset.id;
          console.log(" Deleting post ID:", postId);

          const form = document.getElementById('deleteForm');
          const input = document.getElementById('deletePostId');
          const modal = document.getElementById('deleteConfirmModal');

          input.value = postId;

          form.action = "delete_post_dashboard.php";

          modal.classList.remove('hidden');
        });
      });
    </script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../script/dashboard.js"></script>
  </body>
</html>