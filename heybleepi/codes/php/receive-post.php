<?php 
require_once 'configuration.php';
ini_set('error_log', __DIR__ . 'my_error_log.txt');

$input = json_decode(file_get_contents("php://input"), true);

// Token from the sent JSON.
$incoming_token = $input['token'];
$provider = $input['provider'] ?? ''; // "devhive" or "hershive"
$shared_post_id = $input['shared_post_id'];
$media_url = $input['media_url'];
$content = $input['shared_content'];

switch (strtolower($provider)) {
    case 'devhive': // In progress
        $image_url = $input['posts'][0]['image_url'];
        $video_url = $input['posts'][0]['video_url'];
        $content = $input['posts'][0]['content'];
        
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
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content, post_provider) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $local_user_id, $content, $provider);
        $stmt->execute();
        $new_post_id = $stmt->insert_id;
        $stmt->close();

        $video_exts = ['mp4', 'mov', 'avi', 'webm', 'mkv'];
        $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $uploads_dir = 'uploads/';
        
        $media_sources = [
            ['url' => $image_url ?? '', 'type' => 'image'],
            ['url' => $video_url ?? '', 'type' => 'video'],
        ];
        
        foreach ($media_sources as $media) {
            $media_url = $media['url'];
            $expected_type = $media['type'];
        
            if (!empty($media_url)) {
                $extension = strtolower(pathinfo($media_url, PATHINFO_EXTENSION));
        
                // Validate extension
                if (
                    ($expected_type === 'video' && !in_array($extension, $video_exts)) ||
                    ($expected_type === 'image' && !in_array($extension, $image_exts))
                ) {
                    echo json_encode(['error' => "Unsupported $expected_type format: $extension"]);
                    exit;
                }
        
                // Generate unique filename
                $filename = uniqid('media_', true) . '.' . $extension;
                $local_path = $uploads_dir . $filename;
        
                // Download media from DevHive
                $file_contents = @file_get_contents($media_url);
                if ($file_contents === false) {
                    echo json_encode(['error' => "Failed to download $expected_type from $media_url"]);
                    exit;
                }
        
                // Save to local uploads/
                file_put_contents($local_path, $file_contents);
        
                // Insert into DB
                $media_stmt = $conn->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, ?)");
                $media_stmt->bind_param("iss", $new_post_id, $local_path, $expected_type);
                $media_stmt->execute();
                $media_stmt->close();
            }
        }

        break;

    case 'hershive':
    default:
    $media_url = $input['media_url'] ?? '';
    $content = $input['shared_content'];

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

    // Save post
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, post_provider) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $local_user_id, $content, $provider);
    $stmt->execute();
    $new_post_id = $stmt->insert_id;
    $stmt->close();

    if (!empty($media_url)) {
        $video_exts = ['mp4', 'mov', 'avi', 'webm', 'mkv'];
        $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

        $extension = strtolower(pathinfo($media_url, PATHINFO_EXTENSION));

        // Determine type
        if (in_array($extension, $video_exts)) {
            $media_type = 'video';
        } elseif (in_array($extension, $image_exts)) {
            $media_type = 'image';
        } else {
            echo json_encode(['error' => 'Unsupported media type.']);
            exit;
        }

        // Save to uploads/
        $uploads_dir = 'uploads/';
        $filename = uniqid('media_', true) . '.' . $extension;
        $local_path = $uploads_dir . $filename; // full new file name with directory

        $file_contents = @file_get_contents($media_url); //https://cuteee.png
        if ($file_contents === false) {
            echo json_encode(['error' => 'Failed to download media.']);
            exit;
        }

        file_put_contents($local_path, $file_contents);

        // Save local file path
        $media_stmt = $conn->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, ?)");
        $media_stmt->bind_param("iss", $new_post_id, $local_path, $media_type);
        $media_stmt->execute();
        $media_stmt->close();
    }

    break;
}

http_response_code(200);
echo json_encode(['message' => 'Post received and saved successfully.']);

error_log(print_r($input, true));
?>