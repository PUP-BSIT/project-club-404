<?php

session_start();
require_once 'configuration.php'; // DB

$client_id = $_GET['client_id'] ?? $_POST['client_id'] ?? '';
$redirect_uri = $_GET['redirect_uri'] ?? $_POST['redirect_uri'] ?? '';
$error = '';

// Approve/Deny 
if (isset($_POST['approve'])) {
    $user_id = $_SESSION['user_id'];
    
    // Check for existing valid token for this user and client
    $stmt = $conn->prepare("
        SELECT token 
        FROM oauth_tokens 
        WHERE user_id = ? AND client_id = ? AND expires_at > NOW()
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("is", $user_id, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Reuse existing token
        $token = $row['token'];
    } else {
        // No valid token found — generate a new one
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $stmt = $conn->prepare("INSERT INTO oauth_tokens (user_id, client_id, token, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $client_id, $token, $expires_at);
        $stmt->execute();
    }
    
    // Redirect back to client with token
    header("Location: $redirect_uri&token=$token");
    exit;
}

// Login 
if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($user_id, $hashed_password);
        if ($stmt->fetch() && password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            header("Location: oauth_authorize.php?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri));
            exit;
        } else {
            $error = "Invalid credentials.";
        }
        $stmt->close();
    }
    // login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Login to HeyBleepi</title>
        <link rel="stylesheet" href="../stylesheet/oauth_authorize.css" />
    </head>
    <body>
        <div class="main-container">
            <div class="header">
                <img src="../assets/heybleepi-mascot.jpg" alt="HeyBleepi Mascot" class="mascot">
                <h3>Sign in using HEYBLEEPI</h3>
            </div>
            <div class="main-text">
                <h1>Login</h1>
                <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
                <form method="POST">
                    <input type="hidden" name="client_id" value="<?= htmlspecialchars($client_id) ?>">
                    <input type="hidden" name="redirect_uri" value="<?= htmlspecialchars($redirect_uri) ?>">
                    <input type="email" name="email" placeholder="Email" required><br><br>
                    <input type="password" name="password" placeholder="Password" required><br><br>
                    <button type="submit" class="auth-button">Login</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Consent form
?>
<!DOCTYPE html>
<html lang="en" style="background: url('../assets/dark_mode.jpg') no-repeat center center fixed; background-size: cover;">
<head>
    <meta charset="UTF-8">
    <title>Authorize Application</title>
    <link rel="stylesheet" href="../stylesheet/oauth_authorize.css" />
</head>
<body style="background: url('../assets/dark_mode.jpg') no-repeat center center fixed; background-size: cover;">
    <div class="main-container">
        <div class="header">
            <img src="../assets/heybleepi-mascot.jpg" alt="HeyBleepi Mascot" class="mascot">
            <h3>Sign in using HEYBLEEPI</h3>
        </div>
        <div class="main-text">
            <h1>Authorize Application</h1>
            <p>Application "<b><?= htmlspecialchars($client_id) ?></b>" is requesting access to your HeyBleepi profile.</p>
            <form method="POST">
                <input type="hidden" name="client_id" value="<?= htmlspecialchars($client_id) ?>">
                <input type="hidden" name="redirect_uri" value="<?= htmlspecialchars($redirect_uri) ?>">
                <button type="submit" name="approve" value="1" class="auth-button">Approve</button>
                <button type="submit" name="deny" value="1" class="auth-button" style="background:#c00;">Deny</button>
            </form>
        </div>
    </div>
</body>
</html>