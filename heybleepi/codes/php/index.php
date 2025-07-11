<?php
session_start();
require_once 'users.php';

$message = '';
$messageType = '';
$activeTab = 'login'; // Default tab
if(isset($_SESSION['isloginok']) && $_SESSION['isloginok'] === true) {
    header('Location: loading.php');
    exit();
}

// Check for success parameter in URL
if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    $message = 'Registration successful! Please Login.';
    $messageType = 'success';
    $activeTab = 'login';
}

// Check for message parameters from redirects
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = isset($_GET['type']) ? $_GET['type'] : 'error';
    
    // Set active tab based on the context
    if (strpos($message, 'password') !== false 
        || strpos($message, 'User not found') !== false) {
        $activeTab = 'login';
    } elseif (strpos($message, 'Username already taken') !== false) {
        $activeTab = 'register';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Heybleepi</title>
    <link rel="icon" href="../assets/logo.png" type="image/png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../stylesheet/form_style.css" />
  </head>

  <body>
    <div id="stars-container">
      <div class="main-container">
        <div class="glass-container">
          <div class="content-wrapper">

            <!-- Left panel -->
            <div class="left-panel">
              <h1 class="heading">HeyBleepi</h1>
              <p class="subheading">Say hey. Drop a bleep.</p>
              <div class="image-container">
                <img src="../assets/logo-heybleepi-rb.png" alt="HeyBleepi Logo" class="mascot" />
              </div>
              <p class="description">
                Join the conversation and bleep your thoughts into the feed.
              </p>
            </div>

            <!-- Right panel -->
            <div class="right-panel">
              <div class="tab-switcher">
                <button id="login-tab" class="tab-btn <?php echo $activeTab === 'login' ? 'tab-active' : ''; ?>">Login</button>
                <button id="register-tab" class="tab-btn <?php echo $activeTab === 'register' ? 'tab-active' : ''; ?>">Register</button>
              </div>

              <!-- Message Display -->
              <?php if ($message): ?>
                <div class="message-display <?php echo $messageType === 'success' ? 'message-success' : 'message-error'; ?>">
                  <?php if ($messageType === 'success'): ?>
                    <i class="ri-check-circle-line"></i>
                  <?php else: ?>
                    <i class="ri-error-warning-line"></i>
                  <?php endif; ?>
                  <span><?php echo htmlspecialchars($message); ?></span>
                </div>
              <?php endif; ?>

              <!-- Login form -->
              <form id="login-form" class="form-section <?php echo $activeTab === 'register' ? 'hidden' : ''; ?>" method="POST" action="index.php">
                <div class="input-group">
                  <i class="ri-mail-line input-icon"></i>
                  <input type="email" name="email" placeholder="Email" class="form-input" 
                         value="<?php echo isset($_POST['email']) && $activeTab === 'login' ? htmlspecialchars($_POST['email']) : ''; ?>" required />
                </div>

                <div class="input-group">
                  <i class="ri-lock-line input-icon"></i>
                  <input type="password" name="password" placeholder="Password" class="form-input" required />
                  <button type="button" class="input-toggle">
                    <i class="ri-eye-line"></i>
                  </button>
                </div>

                <div class="form-options">
                  <label class="checkbox-label">
                    <input type="checkbox" name="remember_me" />
                    <span class="checkmark"></span>
                    <span>Remember me</span>
                  </label>
                  <a href="forgot_password.php" class="link">Forgot password?</a>
                </div>

                <button type="submit" name="login" class="auth-button">Login</button>

                <div class="divider">
                  <div class="line"></div>
                  <span class="divider-text">or continue with</span>
                  <div class="line"></div>
                </div>

                <div class="social-connection">
                    <a href="oauth_login.php?provider=devhive" class="account-btn">
                      <img src="../assets/connected_accounts/devhive.png" alt="Devhive logo">
                      Devhive
                    </a>
                    <a href="oauth_login.php?provider=hershive" class="account-btn">
                      <img src="../assets/connected_accounts/hershell.png" alt="Hershive logo">
                      Hershell
                    </a>
                </div>
              </form>

              <!-- Register form -->
              <form id="register-form" class="form-section <?php echo $activeTab === 'login' ? 'hidden' : ''; ?>" method="POST" action="index.php">
                <div class="input-group">
                  <i class="ri-user-line input-icon"></i>
                  <input type="text" name="username" placeholder="Username (must be unique)" class="form-input" 
                         value="<?php echo isset($_POST['username']) && $activeTab === 'register' ? htmlspecialchars($_POST['username']) : ''; ?>" required />
                  <small id="username_status"></small>
                </div>

                <div class="name-grid">
                  <div class="input-group">
                    <i class="ri-user-line input-icon"></i>
                    <input type="text" name="first_name" placeholder="First Name" class="name-group" 
                           value="<?php echo isset($_POST['first_name']) && $activeTab === 'register' ? htmlspecialchars($_POST['first_name']) : ''; ?>" required />
                  </div>
                  <div class="input-group">
                    <i class="ri-user-line input-icon"></i>
                    <input type="text" name="middle_name" placeholder="Middle Name" class="name-group" 
                           value="<?php echo isset($_POST['middle_name']) && $activeTab === 'register' ? htmlspecialchars($_POST['middle_name']) : ''; ?>" />
                  </div>
                  <div class="input-group">
                    <i class="ri-user-line input-icon"></i>
                    <input type="text" name="last_name" placeholder="Last Name" class="name-group" 
                           value="<?php echo isset($_POST['last_name']) && $activeTab === 'register' ? htmlspecialchars($_POST['last_name']) : ''; ?>" required />
                  </div>
                </div>

                <div class="input-group">
                  <i class="ri-mail-line input-icon"></i>
                  <input type="email" name="email" placeholder="Email Address" class="form-input" 
                         value="<?php echo isset($_POST['email']) && $activeTab === 'register' ? htmlspecialchars($_POST['email']) : ''; ?>" required />
                </div>

                <div class="input-group">
                  <i class="ri-calendar-line input-icon"></i>
                  <input type="text" name="birthdate" placeholder="Birthdate (mm/dd/yyyy)" class="form-input" 
                         value="<?php echo isset($_POST['birthdate']) && $activeTab === 'register' ? htmlspecialchars($_POST['birthdate']) : ''; ?>" required />
                </div>

                <div class="input-group">
                  <i class="ri-lock-line input-icon"></i>
                  <input type="password" name="password" placeholder="Password" class="form-input" required />
                  <button type="button" class="input-toggle">
                    <i class="ri-eye-line"></i>
                  </button>
                </div>

                <label class="checkbox-agreement">
                  <input type="checkbox" name="agree_terms" required />
                  <span class="checkmark"></span>
                  <span class="agreement-text">
                    I agree to the
                    <a href="#" class="link">Terms of Service</a>
                    and
                    <a href="#" class="link">Privacy Policy</a>
                  </span>
                </label>

                <button type="submit" name="register" class="auth-button">Create Account</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="../script/form_script.js"></script>
  </body>
</html>