<?php
session_start();
require_once 'configuration.php';
require_once 'users.php';

$message = '';
$showForm = false;

//Validate token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $conn = connectToDatabase();

    $stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $expires = $user['reset_expires'];
        if (strtotime($expires) > time()) {
            $_SESSION['reset_user_id'] = $user['id'];
            $showForm = true;
        } else {
            $message = "Reset link has expired.";
        }
    } else {
        $message = "Invalid reset token.";
    }
}

//Handle new password submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['reset_user_id'])) {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $message = "Passwords do not match.";
        $showForm = true;
    } elseif (strlen($newPassword) < 6) {
        $message = "Password must be at least 12 characters.";
        $showForm = true;
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $conn = connectToDatabase();
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $_SESSION['reset_user_id']);
        $stmt->execute();

        unset($_SESSION['reset_user_id']);
        $message = "Password reset successfully. You can now <a href='index.php'>login</a>.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Reset Password</title>
  <link rel="stylesheet" href="../stylesheet/forgot_password.css" />
</head>
<body>
  <div class="main-container">
    <div class="glass-container">
      <h2 class="heading">Set New Password</h2>
      <?php if ($message): ?>
        <p class="text-center text-red-500"><?php echo $message; ?></p>
      <?php endif; ?>

      <?php if ($showForm): ?>
      <form method="POST">
        <div class="input-group">
          <input 
            type="password"
            name="password"
            placeholder="New Password"
            class="form-input" required />
        </div>
        <div class="input-group">
          <input 
            type="password"
            name="confirm_password"
            placeholder="Confirm Password"
            class="form-input" required />
        </div>
        <button type="submit" class="auth-button">Reset Password</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>