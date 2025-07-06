<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];

// Fetch unread counts for sidebar badges
$unread_count = 0;
$unreadResult = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadResult->bind_param("i", $user_id);
$unreadResult->execute();
$unreadResult->bind_result($unread_count);
$unreadResult->fetch();
$unreadResult->close();

$unreadMessages = 0;
$lastSeenRow = $conn->query("SELECT last_seen_message_id FROM users WHERE id = $user_id");
$lastSeenMessageId = $lastSeenRow ? ($lastSeenRow->fetch_assoc()['last_seen_message_id'] ?? 0) : 0;
$unreadMsgResult = $conn->query("SELECT COUNT(*) AS unread FROM messages WHERE id > $lastSeenMessageId");
$unreadMessages = $unreadMsgResult ? $unreadMsgResult->fetch_assoc()['unread'] : 0;

// Handle search
$search = trim($_GET['q'] ?? '');
$results = [];
if ($search !== '') {
    $stmt = $conn->prepare("SELECT user_name, first_name, last_name, bio, profile_picture FROM users LEFT JOIN user_details ON users.id = user_details.id_fk WHERE user_name LIKE CONCAT('%', ?, '%') OR first_name LIKE CONCAT('%', ?, '%') OR last_name LIKE CONCAT('%', ?, '%') LIMIT 20");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $res = $stmt->get_result();
    $results = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Search - HEYBLEEPI</title>
  <link rel="stylesheet" href="../stylesheet/dashboard.css" />
  <link rel="stylesheet" href="../stylesheet/messages.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet" />
  <style>
    .search-feed-container {
      max-width: 600px;
      margin: 100px auto 40px auto;
      background: #18191c;
      border-radius: 18px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.18);
      padding: 0;
    }
    .search-feed-header {
      padding: 18px 24px 0 24px;
      font-size: 1.3rem;
      font-weight: bold;
      color: #fff;
      text-align: center;
    }
    .search-bar {
      display: flex;
      align-items: center;
      background: #23242a;
      border-radius: 12px;
      margin: 18px 24px 0 24px;
      padding: 8px 16px;
    }
    .search-bar input {
      background: transparent;
      border: none;
      color: #fff;
      font-size: 1rem;
      flex: 1;
      outline: none;
    }
    .search-bar i {
      color: #aaa;
      font-size: 1.3rem;
      margin-right: 8px;
    }
    .search-feed-list {
      list-style: none;
      margin: 0;
      padding: 0;
    }
    .search-feed-item {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 18px 24px;
      border-bottom: 1px solid #23242a;
      color: #fff;
    }
    .search-feed-item:last-child {
      border-bottom: none;
    }
    .search-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
    }
    .search-content {
      flex: 1;
    }
    .search-username {
      font-weight: 600;
      color: #fff;
    }
    .search-bio {
      font-size: 0.95rem;
      color: #aaa;
    }
    .follow-btn {
      background: #fff;
      color: #18191c;
      border: none;
      border-radius: 8px;
      padding: 7px 22px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    .follow-btn:hover {
      background: #4f8aff;
      color: #fff;
    }

    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 25px;
      width: 100%;
      align-items: center;
      margin: 0;
      padding: 0;
      height: 505px;
    }
  </style>
</head>
<body class="page">
  <?php include 'sidebar.php'; ?>

  <div id="sidebarMoreMenu" class="sidebar-more-menu hidden">
    <ul>
      <li><a href="settings.php"><i class="ri-settings-4-line"></i> Settings</a></li>
      <li><a href="logout.php" style="color:#ff4d4f;"><i class="ri-logout-box-line"></i> Log out</a></li>
    </ul>
  </div>

  <div class="search-feed-container">
    <div class="search-feed-header">Search</div>
    <form class="search-bar" method="get" action="search.php">
        <i class="ri-search-line"></i>
        <input type="text" name="q" placeholder="Search" value="<?= htmlspecialchars($search) ?>" autocomplete="off" />
    </form>
    <ul class="search-feed-list">
        <?php if ($search === ''): ?>
            <li class="search-feed-item" style="justify-content:center; color:#aaa;">Follow suggestions</li>
        <?php else: ?>
            <?php if (empty($results)): ?>
                <li class="search-feed-item" style="justify-content:center; color:#aaa;">No results found.</li>
            <?php else: ?>
                <?php foreach ($results as $user): ?>
                    <li class="search-feed-item">
                        <img class="search-avatar" src="./assets/profile/<?= htmlspecialchars($user['profile_picture'] ?? 'rawr.png') ?>" alt="Avatar">
                        <div class="search-content">
                            <div class="search-username"><?= htmlspecialchars($user['user_name']) ?></div>
                            <div class="search-bio"><?= htmlspecialchars($user['bio'] ?? '') ?></div>
                        </div>
                        <button class="follow-btn">Follow</button>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
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