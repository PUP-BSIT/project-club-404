<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$user_name = $_SESSION['username'];
$success_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first = $_POST['first_name'];
    $last = $_POST['last_name'];
    $email = $_POST['email'];
    $bio = $_POST['bio'];

    $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, bio=? WHERE user_name=?");
    $stmt->bind_param("sssss", $first, $last, $email, $bio, $user_name);
    $stmt->execute();
    $stmt->close();

    $success_message = "Profile updated successfully!";
}

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_name=?");
$stmt->bind_param("s", $user_name);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Your Profile!</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./stylesheet/profile_edit.css" />
  </head>

  <body>
    <div class="container">
      <!-- Cover Preview -->
      <div class="cover-preview-div" id="cover_preview_div"></div>
      <button class="change-profile-pic" onclick="changeCover()">Change Cover Photo</button>
      <input type="file" id="cover_input" accept="image/*" hidden>

      <!-- Profile Picture -->
      <div class="profile-picture">
        <img id="profile_image" src="./assets/profile/rawr.png" alt="Shark" />
        <label for="file_input" class="change-profile-image">+</label>
        <input type="file" id="file_input" accept="image/*" hidden />
        <h2>Edit Profile</h2>
      </div>

      <!-- Form Fields -->
      <form id="profile_form">
        <div id="edit_profile_form">
          <div class="input-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" />
          </div>
          <div class="input-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" />
          </div>
          <div class="input-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['user_name']) ?>" />
          </div>
          <div class="input-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" />
          </div>
        </div>
      </form>

      <!-- Bio -->
      <div class="bio-section">
        <label for="bio">Bio</label>
        <textarea id="bio" name="bio" disabled>
          <?= htmlspecialchars($user['bio']) ?>
        </textarea>

        <!-- Buttons -->
        <button class="change-bio" onclick="enableTextArea()">Change Bio</button>
        <button class="save-changes" onclick="alertUserOnSave()">Save Changes</button>
      </div>
    </div>

    <script src="../codes/script/profile_edit.js"></script>
  </body>
</html>