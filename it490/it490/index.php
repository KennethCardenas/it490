<?php
// Enable strict type checking
declare(strict_types=1);

// Include the authentication library
require_once __DIR__ . '/auth.php';

// Set security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Check authentication status
try {
    if (isAuthenticated()) {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        
        // Redirect to landing page
        header("Location: pages/landing.php");
        exit();
    } else {
        // Redirect to login with optional return URL
        $returnUrl = isset($_SERVER['REQUEST_URI']) ? 
            '?return=' . urlencode($_SERVER['REQUEST_URI']) : '';
        header("Location: pages/login.php" . $returnUrl);
        exit();
    }
} catch (Exception $e) {
    // Log error securely (in production, log to file/system)
    error_log('Authentication check failed: ' . $e->getMessage());
    
    // Show generic error page
    header("Location: error.php?code=500");
    exit();
}
