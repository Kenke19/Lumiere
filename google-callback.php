<?php
require 'Admin/includes/db.php';
require 'Admin/includes/auth.php'; 

// Get the ID token sent by Google
$id_token = $_POST['credential'] ?? null;

if (!$id_token) {
    die('No ID token provided');
}

// Verify token with Google API
$client_id = GOOGLE_CLIENT_ID;

// Use Google API Client Library or verify manually:
$payload = json_decode(file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=$id_token"), true);

if (!$payload || $payload['aud'] !== $client_id) {
    die('Invalid ID token');
}

// Extract user info
$email = $payload['email'];
$name = $payload['name'] ?? '';
$googleId = $payload['sub'];

// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Create new user
    $stmt = $pdo->prepare("INSERT INTO users (email, name, google_id) VALUES (?, ?, ?)");
    $stmt->execute([$email, $name, $googleId]);
    $userId = $pdo->lastInsertId();
} else {
    $userId = $user['id'];
}

// Log user in 
$_SESSION['user_id'] = $userId;
$_SESSION['user_email'] = $email;
$_SESSION['user_name'] = $name;

// Redirect to index page
header('Location: index.php');
exit();
