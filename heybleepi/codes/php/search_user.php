<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];

// Handle search
$search = trim($_GET['q'] ?? '');
$results = [];
if ($search !== '') {
    $stmt = $conn->prepare("SELECT user_name, first_name, last_name, bio, profile_picture 
                    FROM users LEFT JOIN user_details ON users.id = user_details.id_fk 
                    WHERE user_name LIKE CONCAT('%', ?, '%') 
                    OR first_name 
                    LIKE CONCAT('%', ?, '%') 
                    OR last_name 
                    LIKE CONCAT('%', ?, '%') 
                    LIMIT 20");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $res = $stmt->get_result();
    $results = $res->fetch_all(MYSQLI_ASSOC);

    echo json_encode($results);
    $stmt->close();
}
?>