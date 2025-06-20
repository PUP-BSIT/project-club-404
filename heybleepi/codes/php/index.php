<?php
session_start();
require_once 'users.php';

$message = '';
$activeTab = 'login'; // Default tab
if(isset($_SESSION['isloginok']) && $_SESSION['isloginok'] === true) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $result = loginUser($_POST['email'], $_POST['password']);
        if ($result === true) {
            header('Location: dashboard.php');
            exit();
        } else {
            $message = $result;
        }
        $activeTab = 'login';
    } elseif (isset($_POST['register'])) {
        $result = registerUser(
            $_POST['username'],
            $_POST['email'],
            $_POST['password'],
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['middle_name'],
            $_POST['birthdate']
        );

        if ($result === true) {
            $_SESSION['username'] = $_POST['username'];
            $_SESSION['user_email'] = $_POST['email'];
            header('Location: dashboard.php');
            exit();
        } else {
            $message = $result;
        }
        $activeTab = 'register';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HeyBleepi - Connect in the Galaxy</title>

    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
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
              <p class="subheading">Connect with friends across the galaxy</p>
              <div class="image-container">
                <img src="./assets/heybleepi-mascot.jpg" alt="HeyBleepi Cat Mascot" class="mascot" />
              </div>
              <p class="description">
                Join thousands of space explorers connecting across the universe
              </p>
            </div>

            <!-- Right panel -->
            <div class="right-panel">
              <div class="tab-switcher">
                <button id="login-tab" class="tab-btn tab-active">Login</button>
                <button id="register-tab" class="tab-btn">Register</button>
              </div>

              <!-- Message Display -->
              <?php if ($message): ?>
                <div class="mb-4 text-red-500 text-sm text-center"><?php echo $message; ?></div>
              <?php endif; ?>

              <!-- Login form -->
              <form id="login-form" class="form-section" method="POST" action="index.php">
                <div class="input-group">
                  <i class="ri-mail-line input-icon"></i>
                  <input type="email" name="email" placeholder="Email" class="form-input" required />
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
                  <a href="#" class="link">Forgot password?</a>
                </div>

                <button type="submit" name="login" class="auth-button">Login</button>

                <div class="divider">
                  <div class="line"></div>
                  <span class="divider-text">or continue with</span>
                  <div class="line"></div>
                </div>

                <div class="social-connection">
                  <button type="button" class="account-btn">
                    <img src="./assets/connected_accounts/devhive.jpg" alt="Devhive logo">
                    Devhive
                  </button>
                  <button type="button" class="account-btn">
                    <img src="./assets/connected_accounts/hershell.png" alt="Hershell logo">
                    Hershell
                  </button>
                </div>
              </form>

              <!-- Register form -->
              <form id="register-form" class="form-section hidden" method="POST" action="index.php">
                <div class="input-group">
                  <i class="ri-user-line input-icon"></i>
                  <input type="text" name="username" placeholder="Username (must be unique)" class="form-input" required />
                  <small id="username_status"></small>
                </div>

                <div class="name-grid">
                  <div class="input-group">
                    <i class="ri-user-line input-icon"></i>
                    <input type="text" name="first_name" placeholder="First Name" class="form-input" required />
                  </div>
                  <div class="input-group">
                    <i class="ri-user-line input-icon"></i>
                    <input type="text" name="middle_name" placeholder="Middle Name" class="form-input" />
                  </div>
                  <div class="input-group">
                    <i class="ri-user-line input-icon"></i>
                    <input type="text" name="last_name" placeholder="Last Name" class="form-input" required />
                  </div>
                </div>

                <div class="input-group">
                  <i class="ri-mail-line input-icon"></i>
                  <input type="email" name="email" placeholder="Email Address" class="form-input" required />
                </div>

                <div class="input-group">
                  <i class="ri-calendar-line input-icon"></i>
                  <input type="text" name="birthdate" placeholder="Birthdate (mm/dd/yyyy)" class="form-input" required />
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