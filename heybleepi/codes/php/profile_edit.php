<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit;
}

$username = $_SESSION['username'];

$stmt = $conn->prepare(
  "SELECT users.*, user_details.*
   FROM users
   LEFT JOIN user_details
   ON users.id = user_details.id_fk
   WHERE users.user_name = ?"
);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id']; // ✅ Add this line to fix the SQL error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $newUsername = $_POST['username'];
  $firstName = $_POST['first_name'];
  $lastName = $_POST['last_name'];
  $bio = $_POST['bio'];
  $work = $_POST['work'];
  $school = $_POST['school'];
  $home = $_POST['home'];
  $religion = $_POST['religion'];
  $relationshipStatus = $_POST['relationship'] ?? '';
  $oldUsername = $_SESSION['username'];

  // Update users table
  $stmt = $conn->prepare(
    "UPDATE users SET user_name = ?, first_name = ?, last_name = ? WHERE user_name = ?"
  );
  $stmt->bind_param("ssss", $newUsername, $firstName, $lastName, $oldUsername);
  $stmt->execute();

  // Get updated user ID
  $stmt = $conn->prepare("SELECT id FROM users WHERE user_name = ?");
  $stmt->bind_param("s", $newUsername);
  $stmt->execute();
  $result = $stmt->get_result();
  $userIdRow = $result->fetch_assoc();
  $userId = $userIdRow['id'];

  // Check if user_details exists
  $stmt = $conn->prepare("SELECT id_fk FROM user_details WHERE id_fk = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $stmt = $conn->prepare(
      "UPDATE user_details SET bio = ?, work = ?, school = ?, home = ?, religion = ?, relationship_status = ? WHERE id_fk = ?"
    );
    $stmt->bind_param("ssssssi", $bio, $work, $school, $home, $religion, $relationshipStatus, $userId);
  } else {
    $stmt = $conn->prepare(
      "INSERT INTO user_details (id_fk, bio, work, school, home, religion, relationship_status) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issssss", $userId, $bio, $work, $school, $home, $religion, $relationshipStatus);
  }
  $stmt->execute();

  // Handle avatar upload
  if (isset($_FILES['file_input']) && $_FILES['file_input']['error'] === UPLOAD_ERR_OK) {
    $avatarName = basename($_FILES['file_input']['name']);
    $avatarTmp = $_FILES['file_input']['tmp_name'];
    $uploadPath = __DIR__ . "/../assets/profile/" . $avatarName;

    if (move_uploaded_file($avatarTmp, $uploadPath)) {
      $stmt = $conn->prepare("UPDATE user_details SET profile_picture = ? WHERE id_fk = ?");
      $stmt->bind_param("si", $avatarName, $userId);
      $stmt->execute();
      $_SESSION['avatar'] = $avatarName;
    }
  }

  // Handle cover photo upload
  if (isset($_FILES['cover_input']) && $_FILES['cover_input']['error'] === UPLOAD_ERR_OK) {
    $coverName = basename($_FILES['cover_input']['name']);
    $coverTmp = $_FILES['cover_input']['tmp_name'];
    $coverPath = __DIR__ . "/../assets/profile/" . $coverName;

    if (move_uploaded_file($coverTmp, $coverPath)) {
      $stmt = $conn->prepare("UPDATE user_details SET profile_cover = ? WHERE id_fk = ?");
      $stmt->bind_param("si", $coverName, $userId);
      $stmt->execute();
    }
  }

  // Update session variables
  $_SESSION['username'] = $newUsername;
  $_SESSION['first_name'] = $firstName;
  $_SESSION['last_name'] = $lastName;

  if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    header("Location: profile_edit.php");
    exit;
  } else {
    echo json_encode(['success' => true]);
    exit;
  }
}

// Notifications
$notificationQuery = "
  SELECT n.*, u.first_name AS actor_first_name, u.last_name AS actor_last_name, p.content AS post_content
  FROM notifications n
  JOIN users u ON n.actor_id = u.id
  LEFT JOIN posts p ON n.post_id = p.id
  WHERE n.user_id = ?
  ORDER BY n.created_at DESC
  LIMIT 10
";
$notificationStmt = $conn->prepare($notificationQuery);
$notificationStmt->bind_param("i", $user_id);
$notificationStmt->execute();
$notificationsResult = $notificationStmt->get_result();
$notifications = $notificationsResult->fetch_all(MYSQLI_ASSOC);
$notificationStmt->close();

// Count unread notifications
$unreadCountRes = $conn->query("SELECT COUNT(*) AS unread FROM notifications WHERE user_id = $user_id AND is_read = 0");
$unread_count = $unreadCountRes->fetch_assoc()['unread'] ?? 0;

// Count unread messages
$lastSeenQuery = $conn->prepare("SELECT last_seen_message_id FROM users WHERE id = ?");
$lastSeenQuery->bind_param("i", $user_id);
$lastSeenQuery->execute();
$lastSeenQuery->bind_result($lastSeenMessageId);
$lastSeenQuery->fetch();
$lastSeenQuery->close();

$lastSeenMessageId = $lastSeenMessageId ?? 0;
$countNewMessages = $conn->query("SELECT COUNT(*) AS unread_messages FROM messages WHERE id > $lastSeenMessageId");
$unreadMessages = $countNewMessages->fetch_assoc()['unread_messages'] ?? 0;

// Update last seen message
$latest_msg_query = $conn->query("SELECT MAX(id) as max_id FROM messages");
$latest_msg = $latest_msg_query->fetch_assoc();
$latest_msg_id = $latest_msg['max_id'] ?? 0;

$update_last_seen = $conn->prepare("UPDATE users SET last_seen_message_id = ? WHERE id = ?");
$update_last_seen->bind_param("ii", $latest_msg_id, $user_id);
$update_last_seen->execute();
$update_last_seen->close();

// Mark messages as read
$update = $conn->prepare("UPDATE messages SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$update->bind_param("i", $user_id);
$update->execute();
$update->close();
?>



<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Profile • Heybleepi</title>
    <link rel="icon" href="../assets/logo.png" type="image/png" />
    <link href="https://fonts.googleapis.com/css2?family=Quicksand&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../stylesheet/profile_edit.css" />
    <link rel="stylesheet" href="../stylesheet/dashboard.css" />
    <style>
      .sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 25px;
        width: 100%;
        align-items: center;
        margin: 0;
        padding: 0;
        height: 400px;
      }
    </style>
  </head>

  <body>
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
        <a class="sidebar-icon-link" href="search.php" title="Search">
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
          <button onclick="openLogoutModal()" class="sidebar-more-menu-btn logout">
            <i class="ri-logout-box-line"></i> Log out
          </button>
        </li>
      </ul>
    </div>

    <div class="success-toast" id="successToast">
      Changes saved successfully!
    </div>

      <div class="container">
        <!-- FORM -->
        <form id="profile_form" enctype="multipart/form-data">
          <!-- Cover Upload -->
          <div class="cover-preview-div" style="background-image: url('../assets/profile/<?= htmlspecialchars($user['profile_cover'] ?? 'dark_mode.jpg') ?>');" id="cover_preview_div"></div>
          <button class="change-profile-pic" type="button" onclick="changeCover()">Change Cover Photo</button>
          <input type="file" name="cover_input" id="cover_input" accept="image/*" hidden>

          <!-- Profile Section -->
          <div class="profile-picture">
            <img id="profile_image" src="../assets/profile/<?= htmlspecialchars($user['profile_picture'] ?? 'rawr.png') ?>" alt="Profile Picture" />
            <label for="file_input" class="change-profile-image">+</label>
            <input type="file" name="file_input" id="file_input" accept="image/*" hidden />
            <h2>Edit Profile</h2>
          </div>

          <!-- Basic Info -->
          <div class="grid-2">
            <div class="input-group">
              <label for="first_name">First Name</label>
              <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" />
            </div>
            <div class="input-group">
              <label for="last_name">Last Name</label>
              <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" />
            </div>
            <div class="input-group">
              <label for="username">Username</label>
              <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['user_name']) ?>" <?= isset($_SESSION['oauth_provider']) ? 'readonly style="background-color:#8f9585;cursor:not-allowed;"' : '' ?> />
            </div>
          </div>

          <!-- Bio -->
          <div class="input-group">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" readonly><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            <button type="button" class="change-bio" onclick="enableTextArea()">Change Bio</button>
          </div>

          <!-- Details -->
          <div class="grid-2">
            <div class="input-group">
              <label for="work">💼 Works at</label>
              <input type="text" id="work" name="work" value="<?= htmlspecialchars($user['work'] ?? '') ?>" />
            </div>
            <div class="input-group">
              <label for="school">🎓 Studies at</label>
              <input type="text" id="school" name="school" value="<?= htmlspecialchars($user['school'] ?? '') ?>" />
            </div>
            <div class="input-group">
              <label for="home">🏠 Lives in</label>
              <input type="text" id="home" name="home" value="<?= htmlspecialchars($user['home'] ?? '') ?>" />
            </div>
            <div class="input-group">
              <label for="religion">✝️ Religion</label>
              <input type="text" id="religion" name="religion" value="<?= htmlspecialchars($user['religion'] ?? '') ?>" />
            </div>
          </div>

          <!-- Relationship -->
          <div class="input-group relationship-group">
            <label>❤️ Relationship Status</label>
            <div class="radio-options">
              <label><input type="radio" name="relationship" value="single" <?= ($user['relationship_status'] ?? '') == 'single' ? 'checked' : '' ?>> Single</label>
              <label><input type="radio" name="relationship" value="in_a_relationship" <?= ($user['relationship_status'] ?? '') == 'in_a_relationship' ? 'checked' : '' ?>> In a Relationship</label>
              <label><input type="radio" name="relationship" value="married" <?= ($user['relationship_status'] ?? '') == 'married' ? 'checked' : '' ?>> Married</label>
              <label><input type="radio" name="relationship" value="complicated" <?= ($user['relationship_status'] ?? '') == 'complicated' ? 'checked' : '' ?>> It’s Complicated</label>
            </div>
          </div>

          <!-- Buttons -->
          <div style="display:flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="save-changes">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
    <script src="../script/profile_edit.js"></script>
  </body>
</html>