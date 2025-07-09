<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'configuration.php';

$provider = $_GET['provider'] ?? null;
$token = $_GET['token'] ?? '';

if (!$provider || !$token) {
    header('Location: index.php?error=missing_data');
    exit;
}

$_SESSION['oauth_token_' . $provider] = $token;
$_SESSION['oauth_provider'] = $provider;

// Revoke expired tokens
$conn->query("UPDATE oauth_tokens SET is_revoked = 1 WHERE expires_at < NOW()");

// Get provider URL and client_id
$stmt = $conn->prepare("SELECT provider_url, client_id FROM oauth_clients WHERE provider_name = ?");
$stmt->bind_param("s", $provider);
$stmt->execute();
$stmt->bind_result($provider_url, $local_client_id);
if (!$stmt->fetch()) {
    header('Location: index.php?error=unknown_provider');
    exit;
}
$stmt->close();
$provider_url = rtrim($provider_url, '/');

// Get user data from provider
$user_data_url = match ($provider) {
    'heybleepi' => $provider_url . '/PROJECT-CLUB-404/heybleepi/codes/php/get-user-data.php',
    'hershive'  => $provider_url . '/project-hershell/Hershive/php/get_user_data.php',
    'devhive'   => $provider_url . '/api/users/get-user-data.php',
    default     => null,
};

$userDataUrl = "$user_data_url?token=$token&provider=$provider";
$userDataJson = file_get_contents($userDataUrl);
$userData = json_decode($userDataJson, true);

if (!$userData || isset($userData['error_message'])) {
    header('Location: index.php?error=oauth_failed');
    exit;
}

$provider_user_id = $userData['user_id'];

//Check if this provider_user_id is already linked
$stmt = $conn->prepare("SELECT user_id FROM user_oauth WHERE provider_user_id = ? AND oauth_provider = ?");
$stmt->bind_param("is", $provider_user_id, $provider);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Existing linked user
    $local_user_id = $row['user_id'];

    // Fetch local user
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $local_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingUser = $result->fetch_assoc();

    // If the username from provider differs
    if ($userData['username'] !== $existingUser['user_name']) {
        // Check if new username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE user_name = ? AND id != ?");
        $stmt->bind_param("si", $userData['username'], $local_user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Username not taken – update username, email, birthdate
            $stmt = $conn->prepare("
                UPDATE users 
                SET user_name = ?, email = ?, birthdate = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "sssi",
                $userData['username'],
                $userData['email'],
                $userData['birthday'],
                $local_user_id
            );
            $stmt->execute();
        } else {
            // Username taken – update only email, birthdate
            $stmt = $conn->prepare("
                UPDATE users 
                SET email = ?, birthdate = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "ssi",
                $userData['email'],
                $userData['birthday'],
                $local_user_id
            );
            $stmt->execute();
        }
    } else {
        // Username same – update only email, birthdate
        $stmt = $conn->prepare("
            UPDATE users 
            SET email = ?, birthdate = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssi",
            $userData['email'],
            $userData['birthday'],
            $local_user_id
        );
        $stmt->execute();
    }
} else {
    // Not linked yet – check if username exists locally
    $stmt = $conn->prepare("SELECT id FROM users WHERE user_name = ?");
    $stmt->bind_param("s", $userData['username']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($existingUser = $result->fetch_assoc()) {
        $local_user_id = $existingUser['id'];

        // Link it if not already linked
        $stmt = $conn->prepare("
            INSERT IGNORE INTO user_oauth (user_id, provider_user_id, oauth_provider)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $local_user_id, $provider_user_id, $provider);
        $stmt->execute();
    } else {
        // New user – only insert allowed fields
        $stmt = $conn->prepare("
            INSERT INTO users (user_name, email, birthdate, password) 
            VALUES (?, ?, ?, '')
        ");
        $stmt->bind_param(
            "sss",
            $userData['username'],
            $userData['email'],
            $userData['birthday']
        );
        $stmt->execute();
        $local_user_id = $stmt->insert_id;

        // Link it
        $stmt = $conn->prepare("
            INSERT INTO user_oauth (user_id, provider_user_id, oauth_provider)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $local_user_id, $provider_user_id, $provider);
        $stmt->execute();
    }
}

// Insert or reuse token
$stmt = $conn->prepare("
    SELECT token 
    FROM oauth_tokens 
    WHERE user_id = ? 
      AND client_id = ? 
      AND is_revoked = 0 
      AND expires_at > NOW()
    LIMIT 1
");
$stmt->bind_param("is", $local_user_id, $local_client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $conn->prepare("
        INSERT INTO oauth_tokens (user_id, client_id, token, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $local_user_id, $local_client_id, $token, $expires_at);
    $stmt->execute();
} else {
    $row = $result->fetch_assoc();
    $token = $row['token'];
}

// Fetch user for session
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $local_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Set session
$_SESSION['id'] = $local_user_id;
$_SESSION['username'] = $user['user_name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['middle_name'] = $user['middle_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['isAllowed'] = 'allowed_to_share';

header('Location: dashboard.php');
exit;
