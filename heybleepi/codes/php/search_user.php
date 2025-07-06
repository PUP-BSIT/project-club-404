<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];

$search = trim($_GET['q'] ?? '');
$results = [];

// Handle Search
if ($search !== '') {
    $searchParam = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT user_name, first_name, last_name, bio, profile_picture 
                            FROM users 
                            LEFT JOIN user_details ON users.id = user_details.id_fk 
                            WHERE user_name LIKE ? 
                               OR first_name LIKE ? 
                               OR last_name LIKE ? 
                            LIMIT 20");

    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $res = $stmt->get_result();
    $results = $res->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);
    $stmt->close();
    exit();
}
?>