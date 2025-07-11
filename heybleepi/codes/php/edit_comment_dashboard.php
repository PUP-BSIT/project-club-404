<?php
session_start();
require_once 'configuration.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = intval($_POST['comment_id']);
    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['id'];

    if ($comment_id && $comment_text) {
        $stmt = $conn->prepare("UPDATE comments SET comment_text=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sii", $comment_text, $comment_id, $user_id);
        if ($stmt->execute()) {
            echo "updated";
            exit;
        }
    }
}
echo "error";
?>