<?php
session_start();
require_once 'configuration.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = intval($_POST['comment_id']);
    $user_id = $_SESSION['id'];

    if ($comment_id) {
        $stmt = $conn->prepare("DELETE FROM comments WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $comment_id, $user_id);
        if ($stmt->execute()) {
            echo "deleted";
            exit;
        }
    }
}
echo "error";
?>