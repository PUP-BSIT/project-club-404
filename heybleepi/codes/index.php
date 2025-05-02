<?php
session_start();
require_once 'users.php';

// Initialize variables
$message = '';
$formData = [
  'first_name' => '',
  'middle_name' => '',
  'last_name' => '',
  'birthdate' => '',
  'email' => '',
  'password' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['login'])) {
    loginUser($_POST['email'], $_POST['password']);
  } else if (isset($_POST['register'])) {
    error_log("Register form submitted: " . print_r($_POST, true));

    if (!empty($_POST['email']) && !empty($_POST['password']) && 
        !empty($_POST['first_name']) && !empty($_POST['last_name']) && 
        filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            
        if (registerUser(
          $_POST['email'],
          $_POST['password'],
          $_POST['first_name'],
          $_POST['last_name'],
          $_POST['middle_name'],
          $_POST['birthdate']
        )) {
          header("Location: index.php?registration=success#login");
          exit();
        } else {
          $message = "Registration failed. Please try again.";
        }
    } else {
      $message = "Please fill all required fields correctly.";
    }
  }
}

// Get any redirect messages
if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
  $message = "Registration successful! Please log in.";
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Your existing head content -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HeyBleepi - Connect in the Galaxy</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="form_style.css" />
  </head>

  <body>
    <!-- Modified form with PHP handling -->
      <div id="stars-container">
        <div class="main-container">
          <div class="glass-container">
            <div class="content-wrapper">
              <!-- Left panel (unchanged) -->
              <div class="left-panel">
                <h1 class="heading">HeyBleepi</h1>
                <p class="subheading">Connect with friends across the galaxy</p>
                <div class="image-container">
                  <img 
                    src="./assets/heybleepi-mascot.jpg" 
                    alt="HeyBleepi Cat Mascot" 
                    class="mascot" />
                </div>
                <p class="description">
                  Join thousands of space explorers connecting across the universe
                </p>
              </div>

              <!-- Right panel -->
              <div class="right-panel">
                <!-- Error message display -->
                <?php if (!empty($message)): ?>
                <div class="mb-4 p-4 bg-red-100 
                    border border-red-400 text-red-700 rounded">
                  <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <div class="tab-switcher">
                  <button
                    id="login-tab"
                    class="tab-btn tab-active"
                    type="button"
                    onclick="switchToLogin(event)">
                      Login
                  </button>
                  <button
                    id="register-tab"
                    class="tab-btn"
                    type="button"
                    onclick="switchToRegister(event)">
                      Register
                  </button>
                </div>

                <!-- Login form -->
                <form method="POST" action="<?php 
                  echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                  <div id="login-form" class="form-section">
                    <div class="input-group">
                      <i class="ri-mail-line input-icon"></i>
                      <input
                        type="email"
                        name="email"
                        placeholder="Email"
                        class="form-input" 
                        value="<?php 
                        echo htmlspecialchars($formData['email']); ?>"
                        required />
                    </div>

                    <div class="input-group">
                      <i class="ri-lock-line input-icon"></i>
                      <input
                        type="password"
                        name="password"
                        placeholder="Password"
                        class="form-input" required />
                      <button class="input-toggle" type="button">
                        <i class="ri-eye-line"></i>
                      </button>
                    </div>

                    <div class="form-options">
                      <label class="checkbox-label">
                        <input type="checkbox" name="remember" />
                        <span class="checkmark"></span>
                        <span>Remember me</span>
                      </label>
                      <a href="#" class="link">Forgot password?</a>
                    </div>

                    <button type="submit" name="login" class="auth-button">
                      Login
                    </button>

                    <div class="divider">
                      <div class="line"></div>
                      <span class="divider-text">or continue with</span>
                      <div class="line"></div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                      <button type="button" class="auth-social">
                        <i class="ri-google-fill text-white"></i>
                      </button>
                      <button type="button" class="auth-social">
                        <i class="ri-facebook-fill text-white"></i>
                      </button>
                      <button type="button" class="auth-social">
                        <i class="ri-twitter-x-fill text-white"></i>
                      </button>
                    </div>
                  </div>
                </form>

                <!-- Register form -->
                <form method="POST" action="<?php 
                  echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                  <div id="register-form" class="form-section hidden">
                    <div class="name-grid">
                      <div class="input-group">
                        <i class="ri-user-line input-icon"></i>
                        <input
                          type="text"
                          name="first_name"
                          placeholder="First Name"
                          class="form-input" 
                          value="<?php 
                              echo htmlspecialchars($formData['first_name']); ?>" 
                              required />
                      </div>

                      <div class="input-group">
                        <i class="ri-user-line input-icon"></i>
                        <input
                          type="text"
                          name="middle_name"
                          placeholder="Middle Name"
                          class="form-input" 
                          value="<?php 
                          echo htmlspecialchars($formData['middle_name']); ?>" />
                      </div>

                      <div class="input-group">
                        <i class="ri-user-line input-icon"></i>
                        <input
                          type="text"
                          name="last_name"
                          placeholder="Last Name"
                          class="form-input" 
                          value="<?php 
                            echo htmlspecialchars($formData['last_name']); ?>"
                          required />
                      </div>
                    </div>

                    <div class="input-group">
                      <i class="ri-calendar-line input-icon"></i>
                      <input
                        type="text"
                        name="birthdate"
                        placeholder="Birthdate (mm/dd/yyyy)"
                        class="form-input" 
                        value="<?php 
                            echo htmlspecialchars($formData['birthdate']); ?>" />
                    </div>

                    <div class="input-group">
                      <i class="ri-mail-line input-icon"></i>
                      <input
                        type="email"
                        name="email"
                        placeholder="Email Address"
                        class="form-input" 
                        value="<?php 
                        echo htmlspecialchars($formData['email']); ?>" required />
                    </div>

                    <div class="input-group">
                      <i class="ri-lock-line input-icon"></i>
                      <input 
                        type="password"
                        name="password"
                        placeholder="Password"
                        class="form-input" required />
                      <button class="input-toggle" type="button">
                        <i class="ri-eye-line"></i>
                      </button>
                    </div>

                    <label class="checkbox-agreement">
                      <input type="checkbox" name="terms" required />
                      <span class="checkmark"></span>
                      <span class="agreement-text">
                        I agree to the
                        <a href="#" class="link">Terms of Service</a>
                        and
                        <a href="#" class="link">Privacy Policy</a>
                      </span>
                    </label>

                    <button type="submit" name="register" class="auth-button">
                      Create Account
                    </button>
                  </div><!-- Register form -->
                </form>
              </div><!-- Right panel -->
            </div><!-- Overall panel -->
          </div><!-- Glass -->
        </div><!-- Background -->
      </div><!-- Stars -->
    </form>
    <script src="form_script.js"></script>
  </body>
</html>