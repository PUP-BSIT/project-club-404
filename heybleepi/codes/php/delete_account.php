<?php
  session_start();
  require_once 'configuration.php';

  if (!isset($_SESSION['username']) || !isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
  }

  $user_id = $_SESSION['id'];

  $sql = "DELETE FROM users WHERE id = ${user_id}";
  if (!mysqli_query($conn, $sql)) {
    die("Error: " .  $sql . "<br>" . mysqli_error($conn));
  }

  echo "Account deletion success, see you again!";
  session_destroy();
  exit();
//   header("Location: index.php?deleted=success");
  mysqli_close($conn);
?>
