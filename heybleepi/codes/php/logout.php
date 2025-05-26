<?php
session_start();
session_destroy(); // Clear session
header("Location: index.php");
exit();
?>