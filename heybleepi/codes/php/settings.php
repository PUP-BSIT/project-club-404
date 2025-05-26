<?php
session_start();
require_once 'configuration.php';

// Ensure user is logged in
if (!isset($_SESSION['user_name'])) {
  header("Location: index.php");
  exit();
}

$user_name = $_SESSION['user_name'];
$user = [];

// Fetch user data
$sql = "SELECT * FROM users WHERE user_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_name);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  $user = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="./stylesheet/settings.css" rel="stylesheet">
    <title>Settings</title>
  </head>

  <body>
    <div id="navigation">
      <div>
        <h3>HeyBleepi | Settings & Privacy</h3>
      </div>

      <div id="buttons_container">
        <button
          type="button"
          onClick="switchToAccountInformation()">
            Account Information
        </button>
        <button
          type="button"
          onClick="switchToPrivacySettings()">
            Privacy & Security
        </button>
        <button type="button" id="logout_btn" onclick="window.location.href='logout.php'">Logout</button>
      </div>
    </div>

    <div id="section">
      <div id="account_information_container">
        <h2>Account Information</h2>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
          <div class="update-success">
            Profile updated successfully!
          </div>
        <?php endif; ?>

        <p>Here you can view and edit your account information.</p>
        <form method="POST" action="update_settings.php">
          <div>
            <label for="first_name">First Name</label>
            <input
              type="text"
              placeholder="First Name"
              id="first_name"
              name="first_name"
              value="<?php echo htmlspecialchars($user['first_name']); ?>"
              required>
          </div>

          <div>
            <label>Middle Name</label>
            <input
              type="text"
              placeholder="Middle Name"
              id="middle_name"
              name="middle_name"
              value="<?php echo htmlspecialchars($user['middle_name']); ?>"
            >
          </div>

          <div>
            <label>Last Name</label>
            <input
              type="text"
              placeholder="Last Name"
              id="last_name"
              name="last_name"
              value="<?php echo htmlspecialchars($user['last_name']); ?>"
              required
            >
          </div>

          <div>
            <label>Username</label>
            <input
              type="text"
              placeholder="Username"
              id="username"
              name="username"
              value="<?php echo htmlspecialchars($user['user_name']); ?>"
              required
            >
          </div>

          <div>
            <label>Email</label>
            <input
              type="email"
              placeholder="Email"
              id="email"
              name="email"
              value="<?php echo htmlspecialchars($user['email']); ?>"
              required
            >
          </div>

          <div>
            <label>Birthdate</label>
            <input
              type="text"
              placeholder="Birthdate"
              id="birthdate"
              name="birthdate"
              value="<?php echo htmlspecialchars($user['birthdate']); ?>"
              required
            >
          </div>

          <button
            type="button"
            id="save_btn">
              Save Changes
          </button>
        </form>

        <div id="connected_accts_container">
          <h4>Connected Accounts</h4>
          <div class="account-buttons">

            <button class="account-btn">
              <img src="./assets/connected_accounts/devhive.jpg" alt="devhive">
              Connect to Devhive
            </button>

            <button class="account-btn">
              <img src="./assets/connected_accounts/hershell.png" alt="hershell">
              Connect to Hershell
            </button>
          </div>
        </div>
      </div>
    </div>

    <script src="./script/settings.js"></script>
  </body>
</html>