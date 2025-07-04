<?php 
require_once 'configuration.php';

$input = json_decode(file_get_contents("php://input"), true);

// Token from the sent JSON.
$incoming_token = $input['token'];
$provider = $input['client']; // "devhive" or "hershive"
$shared_post_id = $input['shared_post_id'];
$media_url = $input['media_url'];
$content = $input['shared_content'];

switch (strtolower($provider)) {
    case 'devhive':
        // Token verification for DevHive
        $stmt = $conn->prepare("SELECT user_id FROM oauth_tokens WHERE token = ?");
        $stmt->bind_param("s", $incoming_token);
        $stmt->execute();
        $stmt->bind_result($local_user_id);
        $stmt->fetch();
        $stmt->close();

        if (!$local_user_id) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or unauthorized token for DevHive.']);
            exit;
        }

        // DevHive: Save post
        $stmt = $conn->prepare("INSERT INTO devhive_posts (user_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $local_user_id, $content);
        $stmt->execute();
        $new_post_id = $stmt->insert_id;
        $stmt->close();

        if (!empty($media_url)) {
            $extension = pathinfo($media_url, PATHINFO_EXTENSION);
            $media_type = in_array(strtolower($extension), ['mp4', 'mov', 'avi']) ? 'video' : 'image';

            $media_stmt = $conn->prepare("INSERT INTO devhive_post_media (post_id, file_path, media_type) VALUES (?, ?, ?)");
            $media_stmt->bind_param("iss", $new_post_id, $media_url, $media_type);
            $media_stmt->execute();
            $media_stmt->close();
        }

        break;

    case 'hershive':
    default:
        // Token verification for Hershive
        $stmt = $conn->prepare("SELECT user_id FROM oauth_tokens WHERE token = ?");
        $stmt->bind_param("s", $incoming_token);
        $stmt->execute();
        $stmt->bind_result($local_user_id);
        $stmt->fetch();
        $stmt->close();

        if (!$local_user_id) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or unauthorized token for Hershive.']);
            exit;
        }

        // Hershive: Save post
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $local_user_id, $content);
        $stmt->execute();
        $new_post_id = $stmt->insert_id;
        $stmt->close();

        if (!empty($media_url)) {
            $extension = pathinfo($media_url, PATHINFO_EXTENSION);
            $media_type = in_array(strtolower($extension), ['mp4', 'mov', 'avi']) ? 'video' : 'image';

            $media_stmt = $conn->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, ?)");
            $media_stmt->bind_param("iss", $new_post_id, $media_url, $media_type);
            $media_stmt->execute();
            $media_stmt->close();
        }

        break;
}

http_response_code(200);
echo json_encode(['message' => 'Post received and saved successfully.']);
?>