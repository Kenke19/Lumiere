<?php
session_start();
require 'Admin/includes/db.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    die("Invalid verification token.");
}

$stmt = $pdo->prepare("SELECT id, email_verified FROM users WHERE email_verification_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("Invalid or expired verification token.");
}

if ($user['email_verified']) {
    $message = "Email already verified. You can login now.";
} else {
    // Update user as verified and remove token
    $stmtUpdate = $pdo->prepare("UPDATE users SET email_verified = 1, email_verification_token = NULL WHERE id = ?");
    if ($stmtUpdate->execute([$user['id']])) {
        $message = "Email verified successfully! You can now login.";
    } else {
        $message = "Failed to verify email. Please try again later.";
    }
}

// Simple feedback page - customize as needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Email Verification - Lumière</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-50 flex items-center justify-center p-4 min-h-screen font-sans">
    <div class="bg-white p-8 rounded-lg shadow max-w-md w-full text-center">
        <h1 class="text-2xl font-bold mb-4">Email Verification</h1>
        <p class="mb-6"><?= htmlspecialchars($message) ?></p>
        <a href="auth.php" class="text-indigo-600 hover:underline font-semibold">Go to Login</a>
    </div>
</body>
</html>
