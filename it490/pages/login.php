<?php
include_once("auth.php");
include_once("navbar.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once("includes/mq_client.php");
    $payload = [
        "type" => "login",
        "username" => $_POST['username'],
        "password" => $_POST['password']
    ];
    $response = sendMessage($payload);
    if ($response['status'] === 'success') {
        $_SESSION['user'] = $response['user'];
        header("Location: landing.php");
        exit();
    } else {
        $error_message = "Login failed: " . $response['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Your Application</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Please enter your credentials to login</p>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="POST">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" placeholder="Enter your username or email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="login-footer">
            Don't have an account? <a href="register.php">Sign up</a><br>
            <a href="forgot-password.php">Forgot password?</a>
        </div>
    </div>
</body>
</html>
