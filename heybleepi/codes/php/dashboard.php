<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit();
}

// POST CREATION
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['post_content'])) {
  $user_id = $_SESSION['id'];
  $post_content = trim($_POST['post_content']);

  if (!empty($post_content)) {
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $post_content);
    $stmt->execute();
    $stmt->close();
  }

  header("Location: dashboard.php");
  exit();
}

// LIKE A POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['like_post_id'])) {
  $user_id = $_SESSION['id'];
  $post_id = intval($_POST['like_post_id']);

  // Check if already liked
  $check = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
  $check->bind_param("ii", $user_id, $post_id);
  $check->execute();
  $check->store_result();

  if ($check->num_rows === 0) {
    $like = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $like->bind_param("ii", $user_id, $post_id);
    $like->execute();
    $like->close();
  }

  $check->close();
  header("Location: dashboard.php");
  exit();
}

// COMMENT ON A POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['comment_post_id'], $_POST['comment_text'])) {
  $user_id = $_SESSION['id'];
  $post_id = intval($_POST['comment_post_id']);
  $comment = trim($_POST['comment_text']);

  if (!empty($comment)) {
    $stmt = $conn->prepare("INSERT INTO comments (user_id, post_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $post_id, $comment);
    $stmt->execute();
    $stmt->close();
  }

  header("Location: dashboard.php");
  exit();
}

// SHARE A POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['share_post_id'])) {
  $user_id = $_SESSION['id'];
  $post_id = intval($_POST['share_post_id']);

  $check = $conn->prepare("SELECT id FROM shares WHERE user_id = ? AND post_id = ?");
  $check->bind_param("ii", $user_id, $post_id);
  $check->execute();
  $check->store_result();

  if ($check->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO shares (user_id, post_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
    $stmt->close();
  }

  $check->close();
  header("Location: dashboard.php");
  exit();
}
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
            <span class="badge" id="notification_count">3</span>
          </button>

          <div class="notification-dropdown" id="notification_dropdown">
            <h4>Notifications</h4>
            <ul>
              <li><strong>John</strong> liked your post.</li>
              <li><strong>Alice</strong> followed you.</li>
              <li><strong>Jane</strong> liked your post.</li>
            </ul>
            <button class="mark-read" id="markAllReadBtn">Mark all as read</button>
          </div>
        </div>

        <!-- Profile -->
        <img
          class="avatar avatar--sm"
          src="./assets/profile/shark.jpg"
          alt="Profile" />
      </div>
    </header>

    <!-- ☰ MAIN LAYOUT -->
    <main class="layout">

      <!-- LEFT SIDEBAR -->
      <aside class="sidebar sidebar--left">

        <!-- Profile Card -->
        <section class="glass card card--profile">
          <img class="avatar avatar--lg" src="./assets/profile/shark.jpg" alt="" />
          <h3 class="card-title"><?php echo htmlspecialchars($_SESSION['first_name'] . " " . $_SESSION['last_name']);?></h3>
          <p class="card-subtitle"><?php echo htmlspecialchars($_SESSION['username'])?></p>
          <ul class="stats">
            <li><strong>248</strong><span>Posts</span></li>
            <li><strong>15.2 K</strong><span>Followers</span></li>
            <li><strong>1.8 K</strong><span>Following</span></li>
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
            Messages
          </a>

          <a class="nav-item" href="profile.php">
            <i class="ri-user-line"></i>
            Profile
          </a>

          <a class="nav-item" href="settings.php"> <!-- Link to settings & privacy -->
            <i class="ri-settings-4-line"></i>
            Settings & Privacy
          </a>

          <a class="nav-item" href="bookmarks.php"> <!-- Link to saved items -->
            <i class="ri-bookmark-line"></i>
            Saved
          </a>
        </nav>
      </aside>

      <!-- FEED -->
      <section class="feed" id="mainFeed">

        <!-- Create Post -->
        <form method="POST" action="dashboard.php">
          <div class="glass create-post">
            <div class="create-post-header">
              <img class="avatar avatar--sm" src="<?= $_SESSION['avatar'] ?? './assets/profile/default.png' ?>" alt="">
              <div class="poster-info">
                <a href="profile.php" class="poster-name"><?= $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] ?></a>
                <p>@<?= $_SESSION['username'] ?></p>
              </div>
            </div>

            <textarea class="create-post-input" name="post_content"
              placeholder="What's happening in your galaxy?"
              required
            ></textarea>

            <div class="create-post-actions">
              <div class="action-group">
                <button class="icon-btn" type="button"><i class="ri-image-line"></i></button>
                <button class="icon-btn" type="button"><i class="ri-vidicon-line"></i></button>
                <button class="icon-btn" type="button"><i class="ri-emotion-line"></i></button>
                <button class="icon-btn" type="button"><i class="ri-map-pin-line"></i></button>
              </div>
              <button class="btn btn--primary" onClick="submit">Post</button>
            </div>
          </div>
        </form>

        <!-- POSTS (example post card) -->
        <?php
        // FETCH POSTS FROM DATABASE
        $fetchPosts = $conn->query("
          SELECT posts.*, users.first_name, users.last_name, users.user_name
          FROM posts
          JOIN users ON posts.user_id = users.id
          ORDER BY posts.created_at DESC
        ");
        ?>

        <?php while ($post = $fetchPosts->fetch_assoc()): ?>
          <article class="glass post">
            <header class="post-header">
              <img class="avatar avatar--sm" src="./assets/profile/default.png" alt="">
              <div>
                <h4><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></h4>
                <time><?= date("g:i A", strtotime($post['created_at'])) ?></time>
              </div>
              <button class="icon-btn"><i class="ri-more-fill"></i></button>
            </header>

            <p><?= htmlspecialchars($post['content']) ?></p>

            <footer class="post-footer">
              <div class="post-actions">
                <!-- LIKE FORM -->
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="like_post_id" value="<?= $post['id'] ?>">
                  <button type="submit" class="icon-btn">
                    <i class="ri-heart-line"></i>
                    <?php
                      $countLikes = $conn->query("SELECT COUNT(*) AS total FROM likes WHERE post_id = {$post['id']}")->fetch_assoc();
                      echo "<span>{$countLikes['total']}</span>";
                    ?>
                  </button>
                </form>

                <!-- COMMENT TOGGLE + COUNT -->
                <button class="icon-btn" onclick="document.getElementById('comment-form-<?= $post['id'] ?>').classList.toggle('hidden')">
                  <i class="ri-chat-1-line"></i>
                  <?php
                    $countComments = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE post_id = {$post['id']}")->fetch_assoc();
                    echo "<span>{$countComments['total']}</span>";
                  ?>
                </button>

                <!-- SHARE FORM -->
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="share_post_id" value="<?= $post['id'] ?>">
                  <button type="submit" class="icon-btn">
                    <i class="ri-share-forward-line"></i>
                    <?php
                      $countShares = $conn->query("SELECT COUNT(*) AS total FROM shares WHERE post_id = {$post['id']}")->fetch_assoc();
                      echo "<span>{$countShares['total']}</span>";
                    ?>
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

              <!-- EXISTING COMMENTS -->
              <div style="margin-top:10px;">
                <?php
                  $comments = $conn->query("SELECT comments.*, users.first_name, users.last_name FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = {$post['id']} ORDER BY commented_at ASC");
                  while ($comment = $comments->fetch_assoc()):
                ?>
                  <div style="margin-bottom: 8px;">
                    <strong><?= htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) ?>:</strong>
                    <?= htmlspecialchars($comment['comment_text']) ?>
                    <small style="color:gray;"> – <?= date("M d, g:i A", strtotime($comment['commented_at'])) ?></small>
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
          <h3 class="card-title">Suggested Connections</h3>
          <ul class="suggestions" id="suggestion_list">


            <!-- Initial Visible Users -->
            <li class="suggestion">
              <img class="avatar avatar--sm" src="./assets/profile/chick.jpg" alt="">
              <div class="user-meta">
                <h4>Mingyu</h4>
                <p>Rapper</p>
              </div>
              <button class="btn btn--primary btn--sm">Connect</button>
            </li>

            <li class="suggestion">
              <img class="avatar avatar--sm" src="assets/profile/cat.jpg" alt="">
              <div class="user-meta">
                <h4>Hoshi</h4>
                <p>RAWRRRRRR</p>
              </div>
              <button class="btn btn--primary btn--sm">Connect</button>
            </li>

            <!-- Hidden Initially -->
            <li class="suggestion hidden">
              <img class="avatar avatar--sm" src="./assets/profile/penguin.jpg" alt="">
              <div class="user-meta">
                <h4>Yasmin</h4>
                <p>Developer</p>
              </div>
              <button class="btn btn--primary btn--sm">Connect</button>
            </li>

            <li class="suggestion hidden">
              <img class="avatar avatar--sm" src="./assets/profile/frog.jpg" alt="">
              <div class="user-meta">
                <h4>Ken</h4>
                <p>Artist</p>
              </div>
              <button class="btn btn--primary btn--sm">Connect</button>
            </li>
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

    <script src="./script/dashboard.js"></script>
  </body>
</html>