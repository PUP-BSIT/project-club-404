<?php
session_start();
require_once 'configuration.php';
require_once 'users.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(16));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    if ($conn) {
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, 
                                reset_expires = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expires, $email);
        $stmt->execute();

        $resetLink = "https://heybleepi.site/reset_password.php?token=$token";
        $subject = "Password Reset Request";

        $body = "
        <html>
        <head><title>Password Reset</title></head>
        <body style='font-family: Arial, sans-serif; 
              background-color: #f9f9f9; color: #333; padding: 20px;'>
            <h2>Password Reset Request</h2>
            <p>Hello,</p>
            <p>We received a request to reset your password for your 
                <strong>HeyBleepi</strong> account.</p>
            <p>Please click the link below to reset your password:</p>
            <p><a href='$resetLink' style='color: #4f46e5;'>
              Reset Your Password</a></p>
            <p>This link will expire in <strong>1 hour</strong>.</p>
            <p>If you didn’t request this, you can ignore this message and your 
              password will remain unchanged.</p>
        </body>
        </html>";

        // Important: Add headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@heybleepi.site";

        mail($email, $subject, $body, $headers);

        $message = "If the email exists, a reset link has been sent.";
    } else {
        $message = "Database connection failed.";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
  <title>Forgot Password</title>
  <link rel="stylesheet" href="./stylesheet/forgot_password.css" />
</head>
<body>
  <div id="stars-container">
    <div class="main-container">
      <div class="glass-container">
        <h2 class="heading">Reset Your Password</h2>
        <form method="POST">
          <div class="input-group">
            <input 
              type="email" 
              name="email" 
              placeholder="Enter your registered email" 
              class="form-input" required />
          </div>
          <button type="submit" class="auth-button">Send Reset Link</button>
        </form>
        <?php if ($message): ?>
            <p class="reset-message"><?php echo $message; ?></p>
            <div style="text-align: center; margin-top: 10px;">
              <a href="index.php" 
              class="go-back-button">Go to Login</a>
            </div>
         <?php endif; ?>
      </div>
    </div>
  </div>
  <script src="./script/form_script.js"></script>
</body>
</html>