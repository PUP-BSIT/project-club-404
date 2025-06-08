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
      $mediaType = explode('/', $_FILES['media_files']['type'][$index])[0]; // image/video
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
  <style>
    .album-form-container {
      max-width: 600px;
      margin: 30px auto;
      background: #fff;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    input, textarea, select {
      width: 100%;
      margin-bottom: 1rem;
      padding: 0.8rem;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
    input[type="file"] {
      border: none;
    }
    button {
      padding: 0.8rem 1.5rem;
      border: none;
      border-radius: 5px;
      background-color: var(--primary, #4CAF50);
      color: white;
      cursor: pointer;
    }
    #albumPreview {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 15px;
    }

    #albumPreview img,
    #albumPreview video {
      border-radius: 8px;
      max-width: 150px;
      max-height: 150px;
      object-fit: cover;
    }

    /* Layout classes */
    .layout-grid img,
    .layout-grid video {
      flex: 1 0 calc(33% - 10px);
      max-width: calc(33.33% - 10px);
      height: 120px;
    }

    .layout-collage img:nth-child(1),
    .layout-collage video:nth-child(1) {
      flex: 2 0 calc(66% - 10px);
      max-width: calc(66.66% - 10px);
      height: 200px;
    }

    .layout-collage img,
    .layout-collage video {
      flex: 1 0 calc(33% - 10px);
      max-width: calc(33.33% - 10px);
      height: 100px;
    }

    .layout-row img,
    .layout-row video {
      flex: 1 0 100%;
      max-width: 100%;
      height: auto;
    }

  </style>
</head>
<body class="page">

  <div class="album-form-container">
    <form action="create_album.php" method="POST" enctype="multipart/form-data">
      <label>Album Title:</label>
      <input type="text" name="title" required><br>

      <label>Description:</label>
      <textarea name="description"></textarea><br>

      <label>Choose Layout:</label>
      <select name="layout" id="layoutSelect" onchange="updateLayoutPreview()">
        <option value="grid">Grid</option>
        <option value="collage">Collage</option>
        <option value="row">Row</option>
      </select><br><br>

       <div id="media-container">
        <input type="file" name="media_files[]" accept="image/*,video/*" required>
      </div>

      <button type="button" onclick="addFileInput()">+ Add More</button>

      <button type="submit">Create Album</button>
    </form>

    <!-- Layout Preview -->
    <h4>Album Preview:</h4>
    <div id="layoutPreview" style="margin-top:10px;">
      <img id="layoutImage" src="layouts/grid.png" style="max-width:200px; border: 1px solid #ccc;">
    </div>
  </div>

  <script>
    function addFileInput() {
      const container = document.getElementById('media-container');
      const input = document.createElement('input');
      input.type = 'file';
      input.name = 'media_files[]';
      input.accept = 'image/*,video/*';
      container.appendChild(input);
    }

    const container = document.getElementById('media-container');
    const preview = document.getElementById('albumPreview');
    const layoutSelect = document.getElementById('layoutSelect');

    function addFileInput() {
      const input = document.createElement('input');
      input.type = 'file';
      input.name = 'media_files[]';
      input.accept = 'image/*,video/*';
      input.addEventListener('change', previewMedia);
      container.appendChild(input);
    }

    function previewMedia() {
      preview.innerHTML = ''; // clear old previews

      const files = [...document.querySelectorAll('input[type="file"][name="media_files[]"]')]
        .map(input => input.files[0])
        .filter(f => f);

      files.forEach(file => {
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

      updateLayoutPreview();
    }

    function updateLayoutPreview() {
      const layout = layoutSelect.value;
      preview.className = ''; // clear all classes
      preview.classList.add(`layout-${layout}`);
    }

    // Initial setup
    document.querySelector('input[type="file"][name="media_files[]"]').addEventListener('change', previewMedia);
    layoutSelect.addEventListener('change', updateLayoutPreview);
  </script>

</body>
</html>