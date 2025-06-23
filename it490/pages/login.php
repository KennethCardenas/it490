<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/mq_client.php';

startSecureSession();

if (!isset($_SESSION['return_url'])) {
    $_SESSION['return_url'] = $_GET['return'] ?? '/pages/dashboard.php';
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $payload = [
        "type" => "login",
        "username" => $username,
        "password" => $password
    ];

    $response = sendMessage($payload);

    if (isset($response['status']) && $response['status'] === 'success') {
        $_SESSION['user'] = $response['user'];
        header("Location: " . getReturnUrl());
        exit();
    } else {
        $error_message = "Login failed: " . htmlspecialchars($response['message'] ?? 'Unknown error');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | BarkBuddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <?php include_once __DIR__ . '/../navbar.php'; ?>
    <div class="login-container">
        <div class="login-header">
            <h2>Welcome</h2>
            <p>Please enter your credentials to login</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="POST">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" placeholder="Enter username or email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
            </div>

            <button type="submit">Login</button>
        </form>

        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Sign up</a></p>
            <p>Forgot Password? <a href="forgot-password.php">Reset</a></p>
        </div>
    </div>
    <img src="../images/dogsilhouette.png" alt="dog silhouette" id="dog1">
    <img src="../images/dogsilhouette2.png" alt="dog silhouette" id="dog2">
</body>
</html>
