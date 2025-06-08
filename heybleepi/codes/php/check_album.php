<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['id'])) {
  header("Location: index.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $user_id = $_SESSION['id'];
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $layout = $_POST['layout'] ?? 'grid';

  // Insert album
  $stmt = $conn->prepare("INSERT INTO albums (user_id, title, description, layout) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("isss", $user_id, $title, $description, $layout);
  $stmt->execute();
  $album_id = $stmt->insert_id;
  $stmt->close();

  // Handle multiple file uploads
  foreach ($_FILES['media']['tmp_name'] as $index => $tmpPath) {
    if ($_FILES['media']['error'][$index] === UPLOAD_ERR_OK) {
      $name = basename($_FILES['media']['name'][$index]);
      $targetDir = 'uploads/albums/';
      if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
      $filePath = $targetDir . time() . '_' . $name;
      move_uploaded_file($tmpPath, $filePath);

      $ext = pathinfo($filePath, PATHINFO_EXTENSION);
      $mediaType = in_array(strtolower($ext), ['mp4', 'webm', 'mov']) ? 'video' : 'image';

      $stmt = $conn->prepare("INSERT INTO album_photos (album_id, file_path, media_type) VALUES (?, ?, ?)");
      $stmt->bind_param("iss", $album_id, $filePath, $mediaType);
      $stmt->execute();
      $stmt->close();
    }
  }

  header("Location: view_album.php?album_id=" . $album_id);
  exit();
}
?>

<!-- ALBUM CREATION FORM -->
<!DOCTYPE html>
<html>
<head>
  <title>Create Album</title>
  <style>
    body { font-family: Arial; padding: 20px; }
    .container { max-width: 600px; margin: auto; }
    input, textarea, select { width: 100%; padding: 8px; margin-bottom: 10px; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Create New Album</h2>
    <form method="POST" enctype="multipart/form-data">
      <label>Album Title</label>
      <input type="text" name="title" required>

      <label>Description</label>
      <textarea name="description" rows="3"></textarea>

      <label>Layout</label>
      <select name="layout">
        <option value="grid">Grid</option>
        <option value="collage">Collage</option>
        <option value="row">Row</option>
      </select>

      <label>Select Photos or Videos</label>
      <input type="file" name="media[]" multiple accept="image/*,video/*" required>

      <button type="submit">Create Album</button>
    </form>
  </div>
</body>
</html>