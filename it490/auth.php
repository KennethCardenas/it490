<?php

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
        session_regenerate_id(true);
    }
}

function isAuthenticated(): bool {
    startSecureSession();
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function requireAuth(): void {
    startSecureSession();

    if (!isAuthenticated()) {
        $returnUrl = $_SERVER['REQUEST_URI'] ?? '/pages/dashboard.php';

        if (!str_contains($returnUrl, 'login.php')) {
            $_SESSION['return_url'] = $returnUrl;
        }

        header("Location: /pages/login.php");
        exit();
    }
}

function getReturnUrl(): string {
    startSecureSession();
    $url = $_SESSION['return_url'] ?? '/pages/dashboard.php';
    unset($_SESSION['return_url']);
    return $url;
}
