<?php
include_once("auth.php");

// Check if logout is confirmed
if (isset($_GET['confirmed']) && $_GET['confirmed'] === 'true') {
    // Clear all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to login page
    header("Location: login.php");
    exit();
}

// If not confirmed, show logout confirmation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout | Your Application</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/logout.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="logout-container">
        <div class="logout-card">
            <div class="logout-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </div>
            <h2>Ready to leave?</h2>
            <p>Are you sure you want to log out of your account?</p>
            
            <div class="logout-actions">
                <a href="?confirmed=true" class="btn-logout">Yes, Logout</a>
                <a href="landing.php" class="btn-cancel">Cancel</a>
            </div>
        </div>
    </div>
</body>
</html>
