<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//session_start();
require_once 'configuration.php';

function redirectWithMessage($message, $type = "error") {
    header("Location: index.php?message=" . urlencode($message) . "&type=" . $type);
    exit();
}

// Handle registration
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register'])) {
    $username  = trim($_POST['username']);
    $fname     = trim($_POST['first_name']);
    $mname     = trim($_POST['middle_name']);
    $lname     = trim($_POST['last_name']);
    $email     = trim($_POST['email']);
    $birthdate = $_POST['birthdate'];
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check for existing username
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE user_name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        redirectWithMessage("Username already taken!");
    }

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (user_name, first_name, middle_name, last_name, email, birthdate, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $username, $fname, $mname, $lname, $email, $birthdate, $password);

    if ($stmt->execute()) {
        // Optional: redirect to login form instead of auto-login
        header("Location: index.php?registration=success");
        exit();
    } else {
        redirectWithMessage("Error: " . $stmt->error);
    }
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
      SELECT users.*, user_details.profile_picture
      FROM users
      LEFT JOIN user_details ON users.id = user_details.id_fk
      WHERE users.email = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user["password"])) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['username'] = $user['user_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['middle_name'] = $user['middle_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['avatar'] = $user['profile_picture'] ?? 'default.png';
            $_SESSION['isloginok'] = true;

            header("Location: dashboard.php");
            exit();
        } else {
            redirectWithMessage("Invalid password.");
        }
    } else {
        redirectWithMessage("User not found.");
    }
}

$conn->close();
?>