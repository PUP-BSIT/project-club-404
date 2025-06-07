<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit;
}

$username = $_SESSION['username'];

$stmt = $conn->prepare(
  "SELECT users.*, user_details.*
   FROM users
   LEFT JOIN user_details
   ON users.id = user_details.id_fk
   WHERE users.user_name = ?"
);

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$defaultProfilePic = 'rawr.png';
$oldProfilePath = __DIR__ . "/assets/profile/" . $user['profile_picture'];
if (empty($user['profile_picture']) || $user['profile_picture'] !== $defaultProfilePic && !file_exists($oldProfilePath)) {
  $stmt = $conn->prepare(
    "UPDATE user_details
      SET profile_picture = ?
      WHERE id_fk = ?"
  );


  $stmt-> bind_param("si", $defaultProfilePic, $user['id']);
  $stmt-> execute();
  $user['profile_picture'] = $defaultProfilePic;
  
}

$defaultCoverPic = 'dark_mode.jpg';
$oldCoverPath = __DIR__ . "/assets/profile/" . $user['profile_cover'];
if (empty($user['profile_cover']) || $user['profile_picture'] !== $defaultCoverPic && !file_exists($oldCoverPath)) {
    $stmt = $conn->prepare(
      "UPDATE user_details
       SET profile_cover = ?
       WHERE id_fk = ?"
    );
    
    $stmt-> bind_param("si", $defaultCoverPic, $user['id']);
    $stmt-> execute();
    $user['profile_cover'] = $defaultCoverPic;
  
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $newUsername = $_POST['username'];
  $firstName = $_POST['first_name'];
  $lastName = $_POST['last_name'];
  $email = $_POST['email'];
  $bio = $_POST['bio'];
  $work = $_POST['work'];
  $school = $_POST['school'];
  $home = $_POST['home'];
  $religion = $_POST['religion'];
  $relationshipStatus = $_POST['relationship'];
  $oldUsername = $_SESSION['username'];

  // Update users table
  $stmt = $conn->prepare(
    "UPDATE users SET user_name = ?, first_name = ?, last_name = ?, email = ? WHERE user_name = ?"
  );
  $stmt->bind_param("sssss", $newUsername, $firstName, $lastName, $email, $oldUsername);
  $stmt->execute();

  // Get updated user ID
  $stmt = $conn->prepare("SELECT id FROM users WHERE user_name = ?");
  $stmt->bind_param("s", $newUsername);
  $stmt->execute();
  $result = $stmt->get_result();
  $userIdRow = $result->fetch_assoc();
  $userId = $userIdRow['id'];

  // Check if user_details exists
  $stmt = $conn->prepare("SELECT id_fk FROM user_details WHERE id_fk = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $stmt = $conn->prepare(
      "UPDATE user_details SET bio = ?, work = ?, school = ?, home = ?, religion = ?, relationship_status = ? WHERE id_fk = ?"
    );
    $stmt->bind_param("ssssssi", $bio, $work, $school, $home, $religion, $relationshipStatus, $userId);
  } else {
    $stmt = $conn->prepare(
      "INSERT INTO user_details (id_fk, bio, work, school, home, religion, relationship_status) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issssss", $userId, $bio, $work, $school, $home, $religion, $relationshipStatus);
  }
  $stmt->execute();

  // Handle avatar upload
  if (isset($_FILES['file_input']) && $_FILES['file_input']['error'] === UPLOAD_ERR_OK) {
    $avatarName = basename($_FILES['file_input']['name']);
    $avatarTmp = $_FILES['file_input']['tmp_name'];
    $avatarPath = __DIR__ . "/assets/profile/" . $avatarName;

    if (!empty($user['profile_picture']) && $user['profile_picture'] !== 'rawr.png') {
        $oldProfilePath = __DIR__ . "/assets/profile/" . $user['profile_picture'];
        if (file_exists($oldProfilePath)) {
            unlink($oldProfilePath);
        }
    }

    move_uploaded_file($avatarTmp, $avatarPath);

    $stmt = $conn->prepare(
      "UPDATE user_details 
      SET profile_picture = ? 
      WHERE id_fk = ?"
    );

    $stmt->bind_param("si", $avatarName, $userId);
    $stmt->execute();
  }

  // Handle cover photo upload
  if (isset($_FILES['cover_input']) && $_FILES['cover_input']['error'] === UPLOAD_ERR_OK) {
    $coverName = basename($_FILES['cover_input']['name']);
    $coverTmp = $_FILES['cover_input']['tmp_name'];
    $coverPath = __DIR__ . "/assets/profile/" . $coverName;
    
    if (!empty($user['profile_cover']) && $user['profile_cover'] !== 'dark_mode.jpg') {
      $oldCoverPath = __DIR__ . "/assets/profile/" . $user['profile_cover'];
      if (file_exists($oldCoverPath)) {
        unlink($oldCoverPath);
      }
    }

    move_uploaded_file($coverTmp, $coverPath);

    $stmt = $conn->prepare(
      "UPDATE user_details 
      SET profile_cover = ? 
      WHERE id_fk = ?"
    );

    $stmt->bind_param("si", $coverName, $userId);
    $stmt->execute();
  }

  $_SESSION['username'] = $newUsername;

  // Redirect to profile.php to view changes live
  header("Location: profile.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Your Profile!</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="stylesheet/profile_edit.css" />
  </head>

  <body>
    <div class="container">
      <!-- FORM -->
      <form id="profile_form" method="POST" enctype="multipart/form-data">

        <!-- Cover Upload -->
        <div class="cover-preview-div"
          style="background-image: url('../assets/profile/<?php echo htmlspecialchars($user['profile_cover'] ?? 'dark_mode.jpg') ?>');"
          id="cover_preview_div">
        </div>
        <button class="change-profile-pic" type="button" onclick="changeCover()">Change Cover Photo</button>
        <input type="file" name="cover_input" id="cover_input" accept="image/*" hidden>

        <!-- Edit Profile Title -->
        <div class="profile-picture">
          <img id="profile_image"
            src="../assets/profile/<?php echo htmlspecialchars($user['profile_picture'] ?? 'rawr.png') ?>"
            alt="Profile Picture" />
          <label for="file_input" class="change-profile-image">+</label>
          <input type="file" name="file_input" id="file_input" accept="image/*" hidden />
          <h2>Edit Profile</h2>
        </div>

        <!-- Basic Information -->
        <div class="grid-2">
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

        <!-- Bio -->
        <div class="input-group">
          <label for="bio">Bio</label>
          <textarea id="bio" name="bio" readonly><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
          <button type="button" class="change-bio" onclick="enableTextArea()">Change Bio</button>
        </div>

        <!-- Profile Details -->
        <div class="grid-2">
          <div class="input-group">
            <label for="work">💼 Works at</label>
            <input type="text" id="work" name="work" value="<?= htmlspecialchars($user['work'] ?? '') ?>" />
          </div>
          <div class="input-group">
            <label for="school">🎓 Studies at</label>
            <input type="text" id="school" name="school" value="<?= htmlspecialchars($user['school'] ?? '') ?>" />
          </div>
          <div class="input-group">
            <label for="home">🏠 Lives in</label>
            <input type="text" id="home" name="home" value="<?= htmlspecialchars($user['home'] ?? '') ?>" />
          </div>
          <div class="input-group">
            <label for="religion">✝️ Religion</label>
            <input type="text" id="religion" name="religion" value="<?= htmlspecialchars($user['religion'] ?? '') ?>" />
          </div>
        </div>

        <!-- Relationship Status -->
        <div class="input-group relationship-group">
          <label>❤️ Relationship Status</label>
          <div class="radio-options">
            <label><input type="radio" name="relationship" value="single" <?= ($user['relationship_status'] ?? '') == 'single' ? 'checked' : '' ?>> Single</label>
            <label><input type="radio" name="relationship" value="in_a_relationship" <?= ($user['relationship_status'] ?? '') == 'in_a_relationship' ? 'checked' : '' ?>> In a Relationship</label>
            <label><input type="radio" name="relationship" value="married" <?= ($user['relationship_status'] ?? '') == 'married' ? 'checked' : '' ?>> Married</label>
            <label><input type="radio" name="relationship" value="complicated" <?= ($user['relationship_status'] ?? '') == 'complicated' ? 'checked' : '' ?>> It’s Complicated</label>
          </div>
        </div>

        <!-- Buttons -->
        <div style="display:flex; gap: 1rem; margin-top: 2rem;">
          <button type="submit" class="save-changes">Save Changes</button>
          <button type="button" class="return-to-profile" onclick="window.location.href='profile.php'">Back to Profile</button>
        </div>
      </form>

    </div>
    <script src="script/profile_edit.js"></script>
  </body>
</html>