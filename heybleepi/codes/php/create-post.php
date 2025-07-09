<?php
session_start();
require_once 'configuration.php';

if (!isset($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['share_post_id'])) {
  header("Location: dashboard.php");
  exit();
}

$content = null;
$user_id = $_SESSION['user_id'];
// "devhive" or "hershive"
$client = $_POST['share_to_other']; 
$shared_post_id = intval($_POST['share_post_id']);
// for devhive receiver client
$devhive_endpoint = "https://devhivespace.com/api/posts/share-receive.php"; 
// for hershive receiver client
$hershive_endpoint ="https://hershive.com/project-hershell/Hershive/php/receive-post.php"; 

$stmt = $conn->prepare("SELECT 
                          p.id,
                          p.user_id,
                          p.content,
                          p.created_at,
                          p.shared_post_id,
                          p.location,
                          m.file_path,
                          m.media_type
                        FROM posts p
                        LEFT JOIN post_media m ON p.id = m.post_id
                        WHERE p.id = ?;
                    ");
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->bind_param("i", $shared_post_id);
$stmt->execute();
$stmt->store_result(); 
// Define variables for each column
$stmt->bind_result($post_id, $user_id, $content, $created_at, $shared_post_id, $location, $file_path, $media_type);

// Collect results
$posts = [];

while ($stmt->fetch()) {
    $posts[] = [
        'id' => $post_id,
        'user_id' => $user_id,
        'content' => $content,
        'created_at' => $created_at,
        'shared_post_id' => $shared_post_id,
        'location' => $location,
        'file_path' => $file_path ? "https://heybleepi.site/PROJECT-CLUB-404/heybleepi/codes/php/uploads/" . rawurlencode(basename($file_path)) : null,
        'media_type' => $media_type,
        'client' => $client,
    ];
}

$stmt->close();

switch ($client) {
  case 'devhive': // In-Progress 
    $isAllowed = $_SESSION['isAllowed'];

    // Checks if the session has a token.
    if(!isset($_SESSION['oauth_token_' . $client])) {
        echo "Account not from devhive.";
        return;
    }
    
    $user_token = $_SESSION['oauth_token_' . $client];

    // Handles JSON post data temporarily.
    $data = [
        'token'=>$user_token,
        'posts'=>$posts,
        'provider'=>'heybleepi'
    ];
    
    // Checks if the user has a session that is allowed to share post to other soc med.
    if($isAllowed === 'allowed_to_share') {      
        $ch = curl_init($devhive_endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true); // sets the method to POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // sends the data to client
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $user_token
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Debug output
        if ($httpCode === 200) {
            // echo "<h1>Post shared to DevHive successfully.</h1><p>Response: {$response}</p>";
            // Get owner of original post
            $ownerStmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
            $ownerStmt->bind_param("i", $shared_post_id);
            $ownerStmt->execute();
            $ownerStmt->bind_result($postOwnerId);
            $ownerStmt->fetch();
            $ownerStmt->close();

            // Notify if not sharing own post
            if ($postOwnerId && $postOwnerId != $user_id) {
                $type = 'share';
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, actor_id, post_id, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
                $notifStmt->bind_param("iiis", $postOwnerId, $user_id, $original_post_id, $type);
                $notifStmt->execute();
                $notifStmt->close();
            }
        } else {
            echo "<h1>Failed to share post to DevHive.</h1><p>HTTP Code: {$httpCode}</p><p>Response: {$response}</p>";
        }
        return;
    } else {
        echo '<h1>This account is not authorized to share.</h1>
            <p>Not Authorized or No account from DevHive</p>';
        return;
    }
    break;

  case 'hershive':
    $isAllowed = $_SESSION['isAllowed'] ?? '';
    $user_token = $_SESSION['oauth_token_' . $client];
    
    // Checks if the session has a token.
    if(!isset($_SESSION['oauth_token_' . $client])) {
        echo "Account not from hershive.";
        return;
    }

    // Handles JSON post data
    $data = [
    'token'=>$user_token,
    'posts'=>$posts,
    'provider'=>'heybleepi'
    ];
    
    // Checks if the user has a session that is allowed to share post to other soc med.
    if($isAllowed === 'allowed_to_share') {
        $ch = curl_init($hershive_endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true); // sets the method to POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // sends the data to client
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $user_token // if needed
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Debug output
        if ($httpCode === 200) {
            echo "<h1>Post shared to Hershive successfully.</h1><p>Response: {$response}</p>";
        } else {
            echo "<h1>Failed to share post to Hershive.</h1><p>HTTP Code: {$httpCode}</p><p>Response: {$response}</p>";
        }
        return;
    } else {
        echo "<h1>This account is not authorized to share.</h1>
            <p>Not Authorized or No account from Hershive</p>";
        return;
    }
    break;

  default:
    // invalid provider
    echo '<h1>Invalid provider.</h1>';
    break;
}

$stmt->close();

?>