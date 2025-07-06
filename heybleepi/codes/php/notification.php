<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];

// Fetch notifications for the user
$stmt = $conn->prepare("
    SELECT n.*, u.first_name, u.last_name, ud.profile_picture
    FROM notifications n
    JOIN users u ON n.actor_id = u.id
    LEFT JOIN user_details ud ON u.id = ud.id_fk
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);

$unread_count = 0;
$unreadResult = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadResult->bind_param("i", $user_id);
$unreadResult->execute();
$unreadResult->bind_result($unread_count);
$unreadResult->fetch();
$unreadResult->close();

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Notifications - HEYBLEEPI</title>
  <link rel="stylesheet" href="../stylesheet/dashboard.css" />
  <link rel="stylesheet" href="../stylesheet/messages.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet" />
  <style>
    .notif-feed-container {
      max-width: 600px;
      margin: 28px;
      margin-left: 500px;
      background: #18191c;
      border-radius: 18px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.18);
      padding: 0;
    }
    .notif-feed-header {
      padding: 22px 24px 22px 24px;
      font-size: 1.3rem;
      font-weight: bold;
      color: #fff;
      text-align: center;
    }
    .notif-feed-list {
      list-style: none;
      margin: 0;
        padding: 0;
    }
    .notif-feed-item {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      padding: 18px 24px;
      border-bottom: 1px solid #23242a;
      color: #fff;
    }
    .notif-feed-item:last-child {
      border-bottom: none;
    }
    .notif-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
    }
    .notif-content {
      flex: 1;
    }
    .notif-actor {
      font-weight: 600;
      color: #fff;
    }
    .notif-meta {
      font-size: 0.9rem;
      color: #aaa;
      margin-top: 2px;
    }
    .notif-time {
      font-size: 0.8rem;
      color: #888;
      margin-left: 8px;
    }
    .notif-feed-item.unread {
      background: rgba(79, 138, 255, 0.08);
    }

    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 25px;
      width: 100%;
      align-items: center;
      margin: 0;
      padding: 0;
      height: 522px;
    }
  </style>
</head>
<body class="page">
  <?php
  // Make sure these are set before including sidebar.php
  if (!isset($unread_count)) $unread_count = 0;
  if (!isset($unreadMessages)) $unreadMessages = 0;
  include 'sidebar.php';
  ?>

  <div id="sidebarMoreMenu" class="sidebar-more-menu hidden">
    <ul>
      <li><a href="settings.php"><i class="ri-settings-4-line"></i> Settings</a></li>
      <li><a href="logout.php" style="color:#ff4d4f;"><i class="ri-logout-box-line"></i> Log out</a></li>
    </ul>
  </div>

  <div class="notif-feed-container">
      <div class="notif-feed-header">Activity</div>
      <ul class="notif-feed-list">
          <?php if (empty($notifications)): ?>
              <li class="notif-feed-item">
                  <div class="notif-content">No notifications yet.</div>
              </li>
          <?php else: ?>
              <?php foreach ($notifications as $notif): ?>
                  <li class="notif-feed-item<?= $notif['is_read'] ? '' : ' unread' ?>">
                      <img class="notif-avatar" src="../assets/profile/<?= htmlspecialchars($notif['profile_picture'] ?? 'rawr.png') ?>" alt="Avatar">
                      <div class="notif-content">
                          <span class="notif-actor"><?= htmlspecialchars($notif['first_name'] . ' ' . $notif['last_name']) ?></span>
                          <?php
                              // Customize this for your notification types
                              if ($notif['type'] === 'like') {
                                  echo " liked your post.";
                              } elseif ($notif['type'] === 'comment') {
                                  echo " commented on your post.";
                              } elseif ($notif['type'] === 'follow') {
                                  echo " started following you.";
                              } else {
                                  echo " " . htmlspecialchars($notif['type']);
                              }
                          ?>
                          <div class="notif-meta">
                              <span class="notif-time"><?= date("M d, g:i A", strtotime($notif['created_at'])) ?></span>
                              <span style="margin-left:12px; color:#4f8aff;">
                                  <?= $notif['is_read'] ? '' : '• Unread' ?>
                              </span>
                          </div>
                      </div>
                  </li>
              <?php endforeach; ?>
          <?php endif; ?>
      </ul>
  </div>
  <script src="../script/dashboard.js"></script>
  <script>
    const moreBtn = document.getElementById('sidebarMoreBtn');
      const moreMenu = document.getElementById('sidebarMoreMenu');
      if (moreBtn && moreMenu) {
        moreBtn.addEventListener('click', function(e) {
          e.preventDefault();
          moreMenu.classList.toggle('hidden');
        });
        document.addEventListener('click', function(e) {
          if (!moreMenu.contains(e.target) && !moreBtn.contains(e.target)) {
            moreMenu.classList.add('hidden');
          }
        });
      }
  </script>
</body>
</html>