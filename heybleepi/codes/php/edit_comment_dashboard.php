<?php
session_start();
require_once 'configuration.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['comment_id'], $_POST['comment_text'])) {
    $commentId = intval($_POST['comment_id']);
    $newText = trim($_POST['comment_text']);

    if (!empty($newText)) {
        $stmt = $conn->prepare("UPDATE comments SET comment_text = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $newText, $commentId, $_SESSION['id']);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dashboard.php");
    exit();
}
?>