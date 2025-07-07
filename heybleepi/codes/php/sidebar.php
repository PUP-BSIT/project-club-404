<aside class="sidebar sidebar--icononly">
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

    <div style="flex:1"></div>
    <button class="sidebar-more-btn" id="sidebarMoreBtn" title="More" type="button">
      <i class="ri-menu-line"></i>
    </button>
  </nav>
</aside>