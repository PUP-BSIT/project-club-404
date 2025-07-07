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

if ($search !== '') {
    $searchParam = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT users.id, user_name, first_name, last_name, bio, profile_picture 
                            FROM users 
                            LEFT JOIN user_details ON users.id = user_details.id_fk 
                            WHERE (user_name LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
                            AND users.id != ?
                            LIMIT 20");

    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("sssi", $searchParam, $searchParam, $searchParam, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $searchedUserId = $row['id'];

        // Check if current user is following this user
        $checkStmt = $conn->prepare("SELECT 1 FROM follow WHERE follower_id = ? AND following_id = ?");
        $checkStmt->bind_param("ii", $user_id, $searchedUserId);
        $checkStmt->execute();
        $followRes = $checkStmt->get_result();
        $isFollowing = $followRes->num_rows > 0;
        $checkStmt->close();

        $row['is_following'] = $isFollowing;
        $results[] = $row;
    }

    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($results);
    exit();
}
?>