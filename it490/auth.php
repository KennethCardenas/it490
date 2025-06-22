<?php
// Start secure session
function startSecureSession() {
    $sessionName = 'SECURE_SESSION';
    $secure = true; // Only send over HTTPS
    $httponly = true; // Prevent JavaScript access
    
    // Force sessions to only use cookies
    ini_set('session.use_only_cookies', 1);
    
    // Set session cookie parameters
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams["lifetime"],
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => 'Strict'
    ]);
    
    session_name($sessionName);
    session_start();
    session_regenerate_id(true); // Regenerate ID to prevent fixation
}

// Check if user is authenticated
function isAuthenticated(): bool {
    startSecureSession();
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

// Require authentication
function requireAuth(): void {
    if (!isAuthenticated()) {
        $returnUrl = urlencode($_SERVER['REQUEST_URI']);
        header("Location: login.php?return=" . $returnUrl);
        exit();
    }
}
    