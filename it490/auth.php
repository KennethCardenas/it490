<?php

/**
 * Starts a secure PHP session with proper cookie parameters.
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $sessionName = 'SECURE_SESSION';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;

    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_only_cookies', 1);

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
        session_regenerate_id(true); // Prevent session fixation
    }
}

/**
 * Checks whether a user is authenticated based on session data.
 */
function isAuthenticated(): bool {
    startSecureSession();
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Requires authentication to view a page.
 * If not authenticated, saves the intended page and redirects to login.
 */
function requireAuth(): void {
    startSecureSession();

    if (!isAuthenticated()) {
        $returnUrl = $_SERVER['REQUEST_URI'] ?? '/pages/profile.php';

        if (!str_contains($returnUrl, 'login.php')) {
            $_SESSION['return_url'] = $returnUrl;
        }

        header("Location: /pages/login.php");
        exit();
    }
}

/**
 * Retrieves the saved return URL or defaults to profile page.
 * Also unsets the session variable to prevent reuse.
 */
function getReturnUrl(): string {
    startSecureSession();
    $url = $_SESSION['return_url'] ?? '/pages/profile.php';
    unset($_SESSION['return_url']);
    return $url;
}
