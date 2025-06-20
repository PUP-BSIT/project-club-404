<?php
session_start();
require_once 'configuration.php'; // Local DB

$provider = $_GET['provider'] ?? '';
if (!$provider) {
    die('No provider specified.');
}

$stmt = $conn->prepare("SELECT client_id, redirect_uri, provider_url FROM oauth_clients WHERE provider_name = ?");
$stmt->bind_param("s", $provider);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $client_id = $row['client_id'];
    $redirect_uri = $row['redirect_uri'];
    $provider_url = rtrim($row['provider_url'], '/');
    
     //Construct auth path
    switch ($provider) {
        case 'heybleepi':
            $auth_path = '/oauth_authorize.php';
            break;
        case 'hershive':
            $auth_path = '/php/oauth_authorize.php'; 
            break;
        case 'devhive': 
            $auth_path = '/api/oauth/oauth_authorize.php';
            break;
        default:
            die('Unsupported provider.');
    }
    
    $auth_url = "{$provider_url}{$auth_path}?client_id={$client_id}&redirect_uri=" . urlencode($redirect_uri);
    header("Location: $auth_url");
    exit;
} else {
    die('Unknown provider.');
}
?>