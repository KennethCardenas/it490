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

// Get current user's role
function getUserRole(): ?string {
    startSecureSession();
    return $_SESSION['user']['role'] ?? null;
}

// Check if user has specific role
function hasRole(string $role): bool {
    startSecureSession();
    return getUserRole() === $role;
}

// Check if user has any of the specified roles
function hasAnyRole(array $roles): bool {
    startSecureSession();
    $userRole = getUserRole();
    return $userRole && in_array($userRole, $roles);
}

// Require specific role and redirect if not authorized
function requireRole(string $role): void {
    startSecureSession();
    
    if (!isAuthenticated()) {
        $returnUrl = $_SERVER['REQUEST_URI'] ?? '/pages/landing.php';
        if (!str_contains($returnUrl, 'login.php')) {
            $_SESSION['return_url'] = $returnUrl;
        }
        header("Location: /it490/pages/login.php");
        exit();
    }
    
    if (!hasRole($role)) {
        header("Location: /it490/error.php?code=403&message=Access denied - insufficient privileges");
        exit();
    }
}

// Require any of the specified roles
function requireAnyRole(array $roles): void {
    startSecureSession();
    
    if (!isAuthenticated()) {
        $returnUrl = $_SERVER['REQUEST_URI'] ?? '/pages/landing.php';
        if (!str_contains($returnUrl, 'login.php')) {
            $_SESSION['return_url'] = $returnUrl;
        }
        header("Location: /it490/pages/login.php");
        exit();
    }
    
    if (!hasAnyRole($roles)) {
        header("Location: /it490/error.php?code=403&message=Access denied - insufficient privileges");
        exit();
    }
}

// Check if user is admin
function isAdmin(): bool {
    return hasRole('admin');
}

// Check if user is owner
function isOwner(): bool {
    return hasRole('owner');
}

// Check if user is sitter
function isSitter(): bool {
    return hasRole('sitter');
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
