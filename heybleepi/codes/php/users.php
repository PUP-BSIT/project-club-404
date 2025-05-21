<?php
require_once './configuration.php';

function isUserLoggedIn() {
    return isset($_SESSION['id']);
}

function loginUser($email, $password) {
    global $conn;

    // Sanitize inputs
    $safeEmail = mysqli_real_escape_string($conn, $email);
    $safePassword = mysqli_real_escape_string($conn, $password);

    // Fetch user from the database
    $query = "SELECT * FROM users WHERE email = '$safeEmail'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if ($user['password'] === $safePassword) {
          $_SESSION['id'] = $user['id'];
          $_SESSION['email'] = $user['email'];
          header("Location: main.php");
          exit();
        } else {
          return "Invalid password. Please try again.";
        }
    } else {
      return "Email not found. Please register first.";
    }

    $message = 'Invalid email or password';
    header("Location: index.php?message=" . urlencode($message));
    exit();
}

function getUser($email) {
    global $conn;

    $safeEmail = mysqli_real_escape_string($conn, $email);

    // Fetch user from the database
    $query = "SELECT * FROM users WHERE email = '$safeEmail'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}

function registerUser($email, $password, $firstName, $lastName, 
                        $middleName = null, $birthdate = null) {
    global $conn;

    $safeEmail = mysqli_real_escape_string($conn, $email);
    $safePassword = mysqli_real_escape_string($conn, $password);
    $safeFirstName = mysqli_real_escape_string($conn, $firstName);
    $safeLastName = mysqli_real_escape_string($conn, $lastName);
    $safeMiddleName = $middleName ? "'" . 
      mysqli_real_escape_string($conn, $middleName) . "'" : "NULL";
    $safeBirthdate = $birthdate ? "'" . 
      mysqli_real_escape_string($conn, $birthdate) . "'" : "NULL";

    // Check if email exists
    $check = mysqli_query($conn, "SELECT id FROM users 
                                  WHERE email = '$safeEmail'");
    if (mysqli_num_rows($check) > 0) {
        error_log("Email already exists: $safeEmail");
        return false; // Email already exists
    }

    // Insert new user
    $query = "INSERT INTO users 
              (email, password, first_name, last_name, middle_name, birthdate)
              VALUES ('$safeEmail', '$safePassword', '$safeFirstName', 
                      '$safeLastName', $safeMiddleName, $safeBirthdate)";

    $result = mysqli_query($conn, $query);
    if ($result) {
        return true;
    } else {
        error_log("Registration failed: " . mysqli_error($conn));
        return false;
    }
}

// Logout function
// This function will destroy the session and redirect to the login page
function logoutUser() {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
?>