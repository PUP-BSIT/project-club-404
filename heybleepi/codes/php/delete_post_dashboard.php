<?php
session_start();
require_once 'configuration.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_SESSION['id'])) {
    $post_id = intval($_POST['post_id']);
    $user_id = intval($_SESSION['id']);

    // Check if post belongs to user
    $check = $conn->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $post_id, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $check->close();
        http_response_code(403);
        exit("Unauthorized or post does not exist.");
    }
    $check->close();

    // Delete related records first
    $conn->query("DELETE FROM likes WHERE post_id = $post_id");
    $conn->query("DELETE FROM comments WHERE post_id = $post_id");
    $conn->query("DELETE FROM notifications WHERE post_id = $post_id");
    $conn->query("DELETE FROM post_media WHERE post_id = $post_id");
    $conn->query("DELETE FROM shares WHERE post_id = $post_id");

    // Update any posts that reference this post
    $conn->query("UPDATE posts SET shared_post_id = NULL WHERE shared_post_id = $post_id");

    // Finally delete the post
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Redirect
    header("Location: dashboard.php");
    exit();

} else {
    http_response_code(400);
    exit("Invalid request.");
}
?>