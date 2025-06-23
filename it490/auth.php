<?php

// Start secure session with proper cookie handling
function startSecureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $sessionName = 'SECURE_SESSION';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;

    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_only_cookies', 1);

        // Set cookie params securely
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
        session_regenerate_id(true); // Mitigate session fixation
    }
}

// Check if a user is logged in
function isAuthenticated(): bool {
    startSecureSession();
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

// Require login; redirect to login with return path if not authenticated
function requireAuth(): void {
    startSecureSession();

    if (!isAuthenticated()) {
        $returnUrl = $_SERVER['REQUEST_URI'] ?? '/dashboard.php';

        // Avoid redirect loop
        if (!str_contains($returnUrl, 'login.php')) {
            $_SESSION['return_url'] = $returnUrl;
        }

        header("Location: /pages/login.php");
        exit();
    }
}

// Get and clear return URL or fallback to dashboard
function getReturnUrl(): string {
    startSecureSession();
    $url = $_SESSION['return_url'] ?? '/dashboard.php';
    unset($_SESSION['return_url']);
    return $url;
}
