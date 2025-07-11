<?php
session_start();
require_once 'configuration.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = intval($_POST['comment_post_id']);
    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['id'];

    if ($post_id && $comment_text) {
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment_text, commented_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $post_id, $user_id, $comment_text);
        if ($stmt->execute()) {
            $commented_at = date("M d, g:i A"); // same format as existing comments
            echo json_encode([
                'success' => true,
                'comment' => [
                    'id' => $conn->insert_id,
                    'text' => htmlspecialchars($comment_text),
                    'user_name' => htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']),
                    'user_id' => $user_id,
                    'commented_at' => $commented_at
                ],
                'current_user_id' => $user_id
            ]);
            exit;
        }
    }
}
echo json_encode(['success' => false]);
?>
