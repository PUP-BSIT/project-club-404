<?php
header('Content-Type: application/json');
require_once 'configuration.php'; // DB

if (!isset($_GET['token'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unsuccessful Authorization!"]);
    exit;
}

$token = $_GET['token'];
$stmt = $conn->prepare(
    "SELECT users.user_name AS username, users.first_name, users.middle_name, users.last_name, users.email, users.birthdate AS birthday
     FROM oauth_tokens
     JOIN users ON oauth_tokens.user_id = users.id
     WHERE oauth_tokens.token = ? AND oauth_tokens.expires_at > NOW()"
);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    http_response_code(200);
    echo json_encode($user);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Unsuccessful Authorization!"]);
}
?>