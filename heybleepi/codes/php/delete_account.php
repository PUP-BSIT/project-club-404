<?php
  session_start();
  require_once 'configuration.php';

  if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
  }

  $user_id = $_SESSION['id'];

  $sql = "SELECT * FROM users WHERE id = {$user_id}";
  if (!mysqli_query($conn, $sql)) {
    echo "Error: " .  $sql . "<br>" . mysqli_error($conn);
  }

  echo "Account deletion success, see you again!";
  session_destroy();
  mysqli_close($conn);
?>
