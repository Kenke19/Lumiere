<?php
// security_headers.php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}
header("Content-Security-Policy: default-src 'self'; 
        script-src 'self' https://cdn.tailwindcss.com https://accounts.google.com; 
        style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; 
        img-src 'self' data:; 
        font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; 
        frame-src https://accounts.google.com; 
        upgrade-insecure-requests;");

header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Secure session cookie params - these are global for all pages using sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'], // Adjust for your production domain without port
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// CSRF token generation (if used globally) - only generate if not already set, or on new session
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
