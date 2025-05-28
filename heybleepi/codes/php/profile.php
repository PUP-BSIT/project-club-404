<?php
session_start();
require_once 'configuration.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit();
}

$username = $_SESSION['username'];

// Fetch user data by username
$sql = "SELECT * FROM users WHERE user_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo "User not found.";
  exit();
}

$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>HEYBLEEPI | <?php echo htmlspecialchars($user['username']); ?>'s Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="./stylesheet/dashboard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" />
  </head>
  <body class="page">

    <!-- Top Navbar -->
    <header class="top-nav glass">
      <h1 class="brand">HEYBLEEPI</h1>
      <nav class="nav-actions">
        <a class="icon-btn" href="dashboard.php" title="Home"><i class="ri-home-4-line"></i></a>
        <a class="icon-btn" href="messages.php" title="Messages"><i class="ri-message-3-line"></i></a>

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
      </nav>
    </header>

    <!-- Main Layout -->
    <main class="profile-container">
      <!-- Banner + Profile info -->
      <div class="profile-top glass">
        <img class="banner-img" src="<?php echo $user['banner.jpg']; ?>" alt="Banner" />
        <div class="profile-info-bar">
          <img class="avatar profile-avatar" src="<?php echo $user['rawr.jpg']; ?>" alt="Profile" />
          <div class="user-basic-info">
            <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
            <p>@<?php echo htmlspecialchars($user['username']); ?> · <?php echo $user['friends']; ?> friends</p>
          </div>

          <div class="profile-buttons">
            <button class="btn btn--primary">Add to Story</button>
              <button class="btn btn--secondary"
                onclick="window.location.href='profile_edit.php'">
                Edit Profile
            </button>
          </div>
        </div>
      </div>

      <nav class="profile-tabs glass">
        <a class="tab active" href="#">Posts</a>
        <a class="tab" href="#">About</a>
        <a class="tab" href="#">Friends</a>
        <a class="tab" href="#">Photos</a>
        <a class="tab" href="#">More</a>
      </nav>

      <!-- Main 2-column grid -->
      <div class="profile-main-grid">
        <!-- LEFT COLUMN -->
        <aside class="left-column">
          <section class="glass card">
            <h4 class="card-title">Intro</h4>
            <p> Welcome to HeyBleeepi!</p>
            <p><i class="ri-briefcase-line"></i> Works at Krusty Krab</p>
            <p><i class="ri-graduation-cap-line"></i> Studies at PUP Taguig</p>
            <p><i class="ri-map-pin-line"></i> Lives in Manila</p>
          </section>

          <section class="glass card">
            <h4 class="card-title">Photos</h4>
            <div class="photo-grid">
              <img src="./assets/profile/cat.jpg" alt="">
              <img src="./assets/profile/penguin.jpg" alt="">
              <img src="./assets/profile/frog.jpg" alt="">
              <img src="./assets/profile/cat.jpg" alt="">
              <img src="./assets/profile/penguin.jpg" alt="">
              <img src="./assets/profile/frog.jpg" alt="">
              <img src="./assets/profile/cat.jpg" alt="">
              <img src="./assets/profile/penguin.jpg" alt="">
              <img src="./assets/profile/frog.jpg" alt="">
            </div>
          </section>
        </aside>

        <!-- Create Post -->
        <section class="right-column">
          <div class="glass create-post">
            <div class="create-post-header">
              <img class="avatar avatar--sm" src="./assets/profile/rawr.png" alt="">
              <div class="poster-info">
                <a href="profile.php" class="poster-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></a>
                <p>@<?php echo htmlspecialchars($user['user_name']); ?></p>
              </div>
            </div>

            <textarea class="create-post-input" id="postInput" placeholder="What's happening in your galaxy?"></textarea>

            <div class="create-post-actions">
              <div class="action-group">
                <button class="icon-btn"><i class="ri-image-line"></i></button>
                <button class="icon-btn"><i class="ri-vidicon-line"></i></button>
                <button class="icon-btn"><i class="ri-emotion-line"></i></button>
                <button class="icon-btn"><i class="ri-map-pin-line"></i></button>
              </div>
              <button class="btn btn--primary" onclick="createPost()">Post</button>
            </div>
          </div>

          <!-- Posts will appear here -->
          <div id="postsContainer">
            <article class="glass post">
              <!--  Post Header -->
              <header class="post-header">
                <img class="avatar avatar--sm" src="./assets/profile/rawr.png" alt="">
                <div>
                  <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);?></h4>
                  <time>3h ago</time>
                </div>
                <button class="icon-btn"><i class="ri-more-fill"></i></button>
              </header>

              <!--- Post Content -->
              <p>MEOW MEOW MEOW MEOW✨ <span class="tag">#SpaceLife</span></p>
              <img class="post-image" src="./assets/post/cc_cat.jpg" alt="Moon">

              <!-- Post Footer -->
              <footer class="post-footer">
                <div class="post-actions">
                  <button class="icon-btn like">
                    <i class="ri-heart-line"></i>
                    <span>201</span>
                  </button>

                  <button class="icon-btn">
                    <i class="ri-chat-1-line"></i>
                    <span>342</span>
                  </button>

                  <button class="icon-btn">
                    <i class="ri-share-forward-line"></i>
                    <span>128</span>
                  </button>
                </div>

                <button class="icon-btn">
                  <i class="ri-bookmark-line"></i>
                </button>
              </footer>
            </article>
          </div>
        </section>
      </div>
    </main>

    <script src="./script/dashboard.js"></script>
  </body>
</html>