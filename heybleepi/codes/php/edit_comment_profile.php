<?php
session_start();
require_once 'configuration.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['comment_id'], $_POST['comment_text'])) {
    $comment_id = intval($_POST['comment_id']);
    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['id'];

    // Only allow editing own comments
    $stmt = $conn->prepare("UPDATE comments SET comment_text = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $comment_text, $comment_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: profile.php");
    exit();
}
?>