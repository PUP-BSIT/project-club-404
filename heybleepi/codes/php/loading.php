<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['id'])) {
    header("Location: /");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Heybleepi</title>
  <link rel="icon" href="/heybleepi-production/heybleepi/codes/assets/logo.png" type="image/png" />
  <link rel="stylesheet" href="/heybleepi-production/heybleepi/codes/stylesheet/dashboard.css">
  <style>
       body {
      margin: 0;
      padding: 0;
      font-family: 'Quicksand', sans-serif;
      height: 100vh;
      background: #101114;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .loading-wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      animation: fadeIn 1s ease-in-out;
    }

    .loading-logo {
      width: 200px;
      height: auto;
      animation: fadeIn 1s ease-in-out;
    }

    .loading-text {
      position: absolute;
      bottom: 30px;
      font-size: 0.9rem;
      color: #cbd9ff;
      text-align: center;
      width: 100%;
      animation: fadeIn 1s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="loading-wrapper">
    <img src="/heybleepi-production/heybleepi/codes/assets/logo-heybleepi-rb.png" alt="HEYBLEEPI Logo" class="loading-logo">
  </div>
  <div class="loading-text"><strong>Heybleepi</strong> by Club-404</div>

  <script>
    setTimeout(() => {
      window.location.href = '/heybleepi-production/heybleepi/codes/php/dashboard.php';
    }, 2000);
  </script>
</body>
</html>
