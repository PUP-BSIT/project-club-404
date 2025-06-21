<?php
session_start();
require_once 'configuration.php';

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit();
}

$user_name = $_SESSION['username'];
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
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet" />
    <link href="../stylesheet/settings.css" rel="stylesheet">
    <title>Settings</title>
  </head>

  <body>
    <!-- Logout & Delete Account Prompt -->
    <div id="logout_prompt" class="hide-overlay">
      <div id="logout_question" class="show-logout-question">
        <h2>Are you sure you want to logout?</h2>
        <div class="logout-buttons">
          <form>
            <button 
              type="button"
              class="buttons logout-btn"
              onClick="window.location.href='logout.php'">
                Logout
            </button>
          </form>
          
          <button 
            type="button"
            class="buttons cancel-btn"
            onClick="hideLogoutPrompt()">
              Cancel
          </button>
        </div>
      </div>
    </div>

    <div id="delete_acc" class="hide-delete-acc-prompt">
      <div class="delete-acc-prompt">
        <h2>Are you sure you want to permanently delete your account?</h2>
        <div class="delete-acc-buttons">
          <form> <!--to test delete account php-->
            <button 
              type="button"
              class="buttons delete-btn"
              onClick="deleteAccount(<?php htmlspecialchars($user['id']);?>)">
                Delete
            </button>
          </form>

          <button 
            type="button"
            class="buttons cancel-btn"
            onClick="hideDeleteAccountPrompt()">
              Cancel
          </button>
        </div>
      </div>
    </div>
    <!-- End of Logout & Delete Account Prompt -->

    <!-- Navigation: Acc Info, Privacy Settings, Logout-->
    <div id="navigation">
      <div>
        <h3>HeyBleepi | Settings & Privacy</h3>
      </div>

      <div id="buttons_container">
        <button
          type="button"
          id="acct_info_btn"
          onClick="switchToAccountInformation()"
          title="Account Information">
            <span class="material-symbols-outlined">manage_accounts</span>
        </button>

        <button
          type="button"
          id="privacy_settings_btn"
          onClick="switchToPrivacySettings()"
          title="Privacy and Security">
           <span class="material-symbols-outlined">security</span>
        </button>

        <a href="dashboard.php" class="back-to-dashboard" title="Back to dashboard">
          <span class="material-symbols-outlined">
            home
          </span>
        </a>

        <button 
          type="button" 
          id="logout_btn"
          onClick="showLogoutPrompt()"
          title="LOGOUT">
            <span class="material-symbols-outlined">logout</span></button>
      </div>
    </div>
    <!-- End of Navigation-->

    <!-- Start of Section -->
    <div id="section">
      <div id="account_information_container" 
        class="show-account-info">
        <h2>Account Information</h2>
        <p>Here you can view and edit your account information.</p>
        <form required>
          <div>
            <label for="first_name">First Name</label>
            <input
              type="text"
              placeholder="First Name"
              id="first_name"
              name="first_name"
              class="acct-info-input"
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
              class="acct-info-input"
              value="<?php echo htmlspecialchars($user['middle_name']); ?>">
          </div>

          <div>
            <label>Last Name</label>
            <input
              type="text"
              placeholder="Last Name"
              id="last_name"
              name="last_name"
              class="acct-info-input"
              value="<?php echo htmlspecialchars($user['last_name']); ?>"
              required>
          </div>

          <div>
            <label>Username</label>
            <input
              type="text"
              placeholder="Username"
              id="username_input"
              name="username_input"
              class="acct-info-input"
              value="<?php echo htmlspecialchars($user['user_name']); ?>"
              required
              <?php if (isset($_SESSION['oauth_provider'])) echo 'readonly style="background-color: #8f9585; cursor: not-allowed;"'; ?>>
          </div>

          <div>
            <label>Email</label>
            <input
              type="email"
              placeholder="Email"
              id="email"
              class="acct-info-input"
              value="<?php echo htmlspecialchars($user['email']); ?>"
              required
              <?php if (isset($_SESSION['oauth_provider'])) echo 'readonly style="background-color: #8f9585; cursor: not-allowed;"'; ?>>
          </div>

          <div>
            <label>Birthdate</label>
            <input
              type="text"
              placeholder="Birthdate"
              id="birthdate"
              name="birthdate"
              class="acct-info-input"
              value="<?php echo htmlspecialchars($user['birthdate']); ?>"
              required>
          </div>

          <button
            type="button"
            id="save_btn"
            onClick="updateAccountInformation()">
              Save Changes
          </button>
        </form>
      </div>

      <div id="privacy_settings_container" 
        class="hide-privacy-settings">
        <h2>Privacy & Security</h2>
        <p>Here you can manage your password.</p>
        <form required>
          <div class="old-pass-container">
            <label>Old Password</label>
            <input 
              type="password" 
              placeholder="Old Password"
              class="old-pass"
              id="old_password"
              required
              <?php if (isset($_SESSION['oauth_provider'])) echo 'readonly style="background-color: #8f9585; cursor: not-allowed;"'; ?>>
          </div>

          <div class="new-pass-container">
            <label>New Password</label>
            <input 
              type="password" 
              placeholder="New Password"
              class="new-pass"
              id="new_password"
              required
              <?php if (isset($_SESSION['oauth_provider'])) echo 'readonly style="background-color: #8f9585; cursor: not-allowed;"'; ?>>
          </div>

          <div class="confirm-pass-container">
            <label>Confirm New Password</label>
            <input 
              type="password" 
              placeholder="Confirm New Password"
              class="confirm-pass"
              id="confirm_new_password"
              required
              <?php if (isset($_SESSION['oauth_provider'])) echo 'readonly style="background-color: #8f9585; cursor: not-allowed;"'; ?>>
          </div>

          <div class="checkbox-container"> 
           <input
              type="checkbox" 
              id="show_password"
              class="checkbox"
              onClick="showPassword()"
              value="Show Password">
            <label for="checkbox">Show Password</label>
            </div>

          <button 
            type="button"
            id="change_password_btn"
            onClick="changePassword()">
              Change Password
          </button>
        </form>
        <button 
          type="button"
          id="delete_account_btn"
          onClick="showDeleteAccountPrompt()">
            Delete Account
        </button>
      </div>
    </div>
    <!-- End of Section -->

    <script src="../script/settings.js"></script>
  </body>
</html>