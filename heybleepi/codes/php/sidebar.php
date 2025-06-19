<?php
// sidebar.php
session_start();
require_once 'configuration.php';

// Get post count
$userPostCount = $conn->query("SELECT COUNT(*) AS total FROM posts WHERE user_id = " . intval($_SESSION['id']));
$postCount = $userPostCount ? $userPostCount->fetch_assoc()['total'] : 0;

// Get followers/following counts
$followersCountRes = $conn->query("SELECT COUNT(*) AS total FROM users WHERE id != " . intval($_SESSION['id']));
$followersCount = $followersCountRes ? $followersCountRes->fetch_assoc()['total'] : 0;

$followingCountRes = $conn->query("SELECT COUNT(*) AS total FROM users WHERE id != " . intval($_SESSION['id']));
$followingCount = $followingCountRes ? $followingCountRes->fetch_assoc()['total'] : 0;
?>

<aside class="sidebar sidebar--left">
  <!-- Profile Card -->
  <div class="brand">HEYBLEEPI</div>
  
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
      <li><strong><?= $postCount ?></strong><span>Posts</span></li>
      <li><strong><?= number_format($followersCount) ?></strong><span>Followers</span></li>
      <li><strong><?= number_format($followingCount) ?></strong><span>Following</span></li>
    </ul>
  </section>

  <!-- Navigation -->
  <nav class="glass card nav-list">
    <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'nav-item--active' : '' ?>" href="dashboard.php">
      <i class="ri-home-4-line"></i> Home
    </a>
    <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'nav-item--active' : '' ?>" href="messages.php">
      <i class="ri-message-3-line"></i> Messages
    </a>
    <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'nav-item--active' : '' ?>" href="profile.php">
      <i class="ri-user-line"></i> Profile
    </a>
    <a class="nav-item" href="settings.php">
      <i class="ri-settings-4-line"></i> Settings & Privacy
    </a>
    <a class="nav-item" href="bookmarks.php">
      <i class="ri-bookmark-line"></i> Saved
    </a>
    <a class="nav-item" href="logout.php">
      <i class="ri-logout-box-line"></i> Logout
    </a>
  </nav>
</aside>