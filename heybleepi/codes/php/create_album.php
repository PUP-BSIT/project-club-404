
<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['id'])) {
  header("Location: index.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = $_SESSION['id'];
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $layout = $_POST['layout'] ?? 'grid';

  // Insert album
  $albumStmt = $conn->prepare("INSERT INTO albums (user_id, title, description, layout) VALUES (?, ?, ?, ?)");
  $albumStmt->bind_param("isss", $user_id, $title, $description, $layout);
  $albumStmt->execute();
  $album_id = $albumStmt->insert_id;
  $albumStmt->close();

  // Create upload folder if not exists
  $uploadDir = "uploads/albums/";
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

  // Save uploaded files
  foreach ($_FILES['media_files']['tmp_name'] as $index => $tmpPath) {
    if ($_FILES['media_files']['error'][$index] === UPLOAD_ERR_OK) {
      $originalName = basename($_FILES['media_files']['name'][$index]);

      $mimeType = mime_content_type($tmpPath);
      $mediaType = strpos($mimeType, 'video') !== false ? 'video' :
                  (strpos($mimeType, 'image') !== false ? 'image' : 'other');

      if (!in_array($mediaType, ['image', 'video'])) {
        continue; // Skip unsupported files
      }

      $uniqueName = time() . '_' . $index . '_' . $originalName;
      $targetPath = $uploadDir . $uniqueName;

      if (move_uploaded_file($tmpPath, $targetPath)) {
        $insertStmt = $conn->prepare("INSERT INTO album_photos (album_id, file_path, media_type) VALUES (?, ?, ?)");
        $insertStmt->bind_param("iss", $album_id, $targetPath, $mediaType);
        $insertStmt->execute();
        $insertStmt->close();
      }
    }
  }

  header("Location: profile.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Album</title>
  <link rel="stylesheet" href="stylesheet/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    .back-to-profile {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.95rem;
      padding: 0.5rem 1rem;
      border-radius: 0px;
      color: white;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(6px);
      text-decoration: none;
      transition: all 0.3s ease;
      margin-bottom: 1.5rem;
      max-width: 100%;
      overflow: hidden;
      white-space: nowrap;
    }

    .back-to-profile .arrow {
      font-weight: bold;
      font-size: 1.1rem;
    }

    .album-form-container {
      width: 100%;
      max-width: 1100px;
      margin: 60px auto;
      background: rgba(255, 255, 255, 0.05);
      padding: 2.5rem;
      border-radius: 16px;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: white;
      overflow: visible;
      box-sizing: border-box;
    }

    .album-form-container h2 {
      margin-bottom: 1.5rem;
      text-align: center;
    }

    .form-field {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
      margin-bottom: 1rem;
    }

    input, textarea {
      padding: 0.75rem;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.15);
      background: rgba(255, 255, 255, 0.05);
      color: white;
    }

    input::file-selector-button {
      background: var(--primary);
      color: white;
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 8px;
      margin-right: 10px;
      cursor: pointer;
    }

    .media-buttons {
      display: flex;
      flex-direction: row;
      justify-content: flex-start;
      flex-wrap: wrap;
      align-items: center;
      gap: 1rem;
      margin-top: 1rem;
    }

    .media-upload-btn {
      background: linear-gradient(90deg, #6f8eff, #ffb6e6);
      color: white;
      padding: 0.5rem 1.25rem;
      border-radius: 9999px;
      border: none;
      font-size: 0.95rem;
      cursor: pointer;
      white-space: nowrap;
      transition: background 0.3s ease;
    }

    .submit-container {
      display: flex;
      justify-content: flex-end;
      width: 100%;
      margin-top: 1.5rem;
    }

    .btn-submit {
      background: linear-gradient(to right, #4f8aff, #2f6ee5);
      color: white;
      padding: 0.65rem 1.5rem;
      border-radius: 9999px;
      border: none;
      cursor: pointer;
      font-weight: 500;
      width: auto;
      display: inline-block;
      text-align: center;
      box-shadow: 0 0 0 transparent;
      transition: all 0.3 ease-in-out;
    }

    .btn-submit:hover {
      box-shadow: 0 0 12px 3px rgba(79, 138, 255, 0.6);
    }

    .btn-submit:active {
      box-shadow: 0 0 16px 4px rgba(47, 110, 229, 0.7);
      transform: scale(0.98);
    }

    #albumPreview {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 15px;
      max-height: none;
    }

    #albumPreview img, #albumPreview video {
      border-radius: 12px;
      max-width: 120px;
      max-height: 120px;
      object-fit: cover;
    }

    @media (max-width: 480px) {
      .back-to-profile {
        font-size: 0.85rem;
        padding: 0.4rem 0.75rem;
        gap: 0.35rem;
      }

      .media-buttons {
        flex-direction: column;
        align-items: stretch;
      }

      .media-upload-btn {
        width: 100%;
        text-align: center;
      }

      .submit-container {
        justify-content: center;
      }

      .btn-submit {
        width: 100%;
      }
    }

    @media (max-width: 768px) {
      .album-form-container {
        padding: 1rem;
        max-width: 95vw;
      }

      #albumPreview img,
      #albumPreview video {
        max-width: 100px;
        max-height: 100px;
      }
    }
  </style>
</head>

<body class="page">

  <a href="profile.php" class="back-to-profile">
    <span class="arrow">←</span> Back to Profile
  </a>

  <div class="album-form-container">
    <h2>Create Album</h2>
    <form action="create_album.php" method="POST" enctype="multipart/form-data">

    <div class="form-field">
        <label>Album Title:</label>
        <input type="text" name="title" required>
      </div>

      <div class="form-field">
        <label>Description:</label>
        <textarea name="description" rows="3"></textarea>
      </div>

      <!-- Media Buttons Row -->
      <div class="form-field media-buttons">
        <input type="file" id="photoInput" name="media_files[]" accept="image/*" style="display: none;" multiple>
        <input type="file" id="videoInput" name="media_files[]" accept="video/*" style="display: none;" multiple>

        <button type="button" class="media-upload-btn" onclick="document.getElementById('photoInput').click()">+ Photo</button>
        <button type="button" class="media-upload-btn" onclick="document.getElementById('videoInput').click()">+ Video</button>
      </div>

      <!-- Media Preview -->
      <div id="albumPreview"></div>

      <!-- Submit Button Bottom Right -->
      <div class="submit-container">
        <button class="btn-submit" type="submit">Create Album</button>
      </div>

    </form>
  </div>

  <script>
    function previewMedia(files) {
      const preview = document.getElementById('albumPreview');
      Array.from(files).forEach(file => {
        const type = file.type.split('/')[0];
        const url = URL.createObjectURL(file);

        if (type === 'image') {
          const img = document.createElement('img');
          img.src = url;
          preview.appendChild(img);
        } else if (type === 'video') {
          const vid = document.createElement('video');
          vid.src = url;
          vid.controls = true;
          preview.appendChild(vid);
        }
      });
    }

    document.getElementById('photoInput').addEventListener('change', function(e) {
      previewMedia(e.target.files);
    });

    document.getElementById('videoInput').addEventListener('change', function(e) {
      previewMedia(e.target.files);
    });
  </script>

</body>
</html>