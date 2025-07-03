<?php
// Start a secure PHP session
function startSecureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $sessionName = 'SECURE_SESSION';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'; // Detect HTTPS
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

// Check if user is authenticated
function isAuthenticated(): bool {
    startSecureSession();
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

// Require authentication and redirect to login if not authenticated
function requireAuth(): void {
    startSecureSession();

    if (!isAuthenticated()) {
        $returnUrl = $_SERVER['REQUEST_URI'] ?? '/pages/landing.php';

        if (!str_contains($returnUrl, 'login.php')) {
            $_SESSION['return_url'] = $returnUrl;
        }

        header("Location: /pages/login.php");
        exit();
    }
}

// Get and clear the return URL from session or fallback to landing page
function getReturnUrl(): string {
    startSecureSession();
    $url = $_SESSION['return_url'] ?? '/pages/landing.php';
    unset($_SESSION['return_url']);
    return $url;
}

// Check if the current user has the given role
function hasRole(string $role): bool {
    startSecureSession();
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role;
}

// Convenience check for admin role
function isAdmin(): bool {
    return hasRole('admin');
}

// Require a specific role to access a page
function requireRole(string $role): void {
    if (!hasRole($role)) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied';
        exit();
    }
}
