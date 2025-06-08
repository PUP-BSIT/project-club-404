<?php
session_start();
require_once 'configuration.php';

if (!isset($_GET['album_id']) || !isset($_SESSION['id'])) {
  header("Location: profile.php");
  exit();
}

$album_id = intval($_GET['album_id']);
$user_id = $_SESSION['id'];

// Fetch album info
$albumStmt = $conn->prepare("SELECT * FROM albums WHERE id = ? AND user_id = ?");
$albumStmt->bind_param("ii", $album_id, $user_id);
$albumStmt->execute();
$albumResult = $albumStmt->get_result();

if ($albumResult->num_rows === 0) {
  echo "Album not found or access denied.";
  exit();
}
$album = $albumResult->fetch_assoc();

// Fetch media
$mediaStmt = $conn->prepare("SELECT * FROM album_photos WHERE album_id = ? ORDER BY created_at DESC");
$mediaStmt->bind_param("i", $album_id);
$mediaStmt->execute();
$mediaResult = $mediaStmt->get_result();
$mediaItems = $mediaResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($album['title']) ?> | Album</title>
  <link rel="stylesheet" href="stylesheet/dashboard.css">
  <style>
    .album-container {
      padding: 20px;
    }
    .album-title {
      font-size: 1.5rem;
      margin-bottom: 15px;
    }
    .album-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .album-grid img,
    .album-grid video {
      max-width: 220px;
      max-height: 220px;
      border-radius: 10px;
      cursor: pointer;
      object-fit: cover;
    }
  </style>
</head>
<body class="page">
  <div class="album-container">
    <h2 class="album-title"><?= htmlspecialchars($album['title']) ?></h2>
    <p><?= htmlspecialchars($album['description']) ?></p>

    <div class="album-grid">
      <?php foreach ($mediaItems as $item): ?>
        <?php if ($item['media_type'] === 'image'): ?>
          <img src="<?= htmlspecialchars($item['file_path']) ?>" onclick="openLightbox('<?= $item['file_path'] ?>')" />
        <?php else: ?>
          <video src="<?= htmlspecialchars($item['file_path']) ?>" controls onclick="openLightboxVideo('<?= $item['file_path'] ?>')"></video>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Lightbox -->
  <div id="lightbox" class="lightbox" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); justify-content:center; align-items:center; z-index:9999;">
    <span style="position:absolute; top:20px; right:30px; font-size:30px; color:#fff; cursor:pointer;" onclick="closeLightbox()">×</span>
    <div id="lightboxContent"></div>
  </div>

  <script>
    function openLightbox(src) {
      document.getElementById('lightboxContent').innerHTML = `<img src="${src}" style="max-width:90vw; max-height:90vh; border-radius:10px;" />`;
      document.getElementById('lightbox').style.display = 'flex';
    }

    function openLightboxVideo(src) {
      document.getElementById('lightboxContent').innerHTML = `
        <video controls autoplay style="max-width:90vw; max-height:90vh; border-radius:10px;">
          <source src="${src}" type="video/mp4">
        </video>`;
      document.getElementById('lightbox').style.display = 'flex';
    }

    function closeLightbox() {
      document.getElementById('lightbox').style.display = 'none';
      document.getElementById('lightboxContent').innerHTML = '';
    }
  </script>
</body>
</html>
