<?php
include_once __DIR__ . '/../auth.php';

$error_message = '';
$success_message = '';
$step = 1; // Step 1: Verify user, Step 2: Reset password

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once __DIR__ . '/../includes/mq_client.php';
    
    try {
        if (isset($_POST['step']) && $_POST['step'] === '1') {
            // Step 1: Verify username and email
            $payload = [
                "type" => "verify_user",
                "username" => $_POST['username'],
                "email" => $_POST['email']
            ];
            
            $response = sendMessage($payload);
            
            if ($response['status'] === 'success') {
                // User verified, proceed to step 2
                $step = 2;
                $verified_username = $_POST['username'];
                $verified_email = $_POST['email'];
                $success_message = "User verified! Please enter your new password.";
            } else {
                $error_message = "User verification failed: " . $response['message'];
            }
        } elseif (isset($_POST['step']) && $_POST['step'] === '2') {
            // Step 2: Reset password
            $payload = [
                "type" => "reset_password",
                "username" => $_POST['username'],
                "email" => $_POST['email'],
                "new_password" => $_POST['new_password']
            ];
            
            $response = sendMessage($payload);
            
            if ($response['status'] === 'success') {
                $success_message = "Password reset successful! You can now login with your new password.";
                $step = 1; // Reset to step 1
            } else {
                $error_message = "Password reset failed: " . $response['message'];
                $step = 2; // Stay on step 2
                $verified_username = $_POST['username'];
                $verified_email = $_POST['email'];
            }
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | BarkBuddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)),
                url("../images/forgotpassbackground.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .container {
            max-width: 450px;
            margin: 50px auto;
            background: #fff;
            border: 2px solid #0077cc;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }

        h2 {
            color: #0077cc;
            text-align: center;
        }

        label {
            font-weight: bold;
            color: #333;
        }

        input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="submit"] {
            width: 100%;
            background-color: #fb8b1a;
            color: white;
            border: none;
            padding: 10px;
            margin-top: 15px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #e77a10;
        }

        .message {
            text-align: center;
            color: green;
            margin-top: 10px;
        }

        .navbar {
            background: #333;
            color: white;
            padding: 10px;
            margin: 
        }

        .navbar a {
            color: white;
            margin-right: 10px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../navbar.php'; ?>
    <?php $title = "Forgot Password"; include_once __DIR__ . "/../header.php"; ?>
    
    <div class="forgot-password-container">
        <div class="forgot-password-card">
            <div class="forgot-password-header">
                <h2>
                    <?php if ($step === 1): ?>
                        Forgot Your Password?
                    <?php else: ?>
                        Reset Your Password
                    <?php endif; ?>
                </h2>
                <p>
                    <?php if ($step === 1): ?>
                        Enter your username and email to verify your account
                    <?php else: ?>
                        Enter your new password below
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
                <!-- Step 1: Verify User -->
                <form class="forgot-password-form" method="POST">
                    <input type="hidden" name="step" value="1">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" placeholder="Enter your username" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">Verify Account</button>
                </form>
            <?php else: ?>
                <!-- Step 2: Reset Password -->
                <form class="forgot-password-form" method="POST">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($verified_username); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($verified_email); ?>">
                    
                    <div class="form-group">
                        <label>Account</label>
                        <div class="account-info">
                            <p><strong>Username:</strong> <?php echo htmlspecialchars($verified_username); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($verified_email); ?></p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter your new password" required>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                        <div class="password-strength">
                            <div class="strength-meter"></div>
                            <span class="strength-text">Password strength</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">Reset Password</button>
                    <a href="forgot-password.php" class="btn-secondary">Start Over</a>
                </form>
            <?php endif; ?>
            
            <div class="forgot-password-footer">
                <p>Remember your password? <a href="login.php">Sign in</a></p>
            </div>
        </div>
    </div>
    
    <img src="../images/dog3.png" alt="dog silhouette" id="dog3">
    <img src="../images/dog4.png" alt="dog silhouette" id="dog4">

    <script>
        // Password confirmation validation
        const form = document.querySelector(".forgot-password-form");
        if (form) {
            form.addEventListener("submit", function(e) {
                const newPassword = document.getElementById("new_password");
                const confirmPassword = document.getElementById("confirm_password");
                
                if (newPassword && confirmPassword) {
                    if (newPassword.value !== confirmPassword.value) {
                        alert("Passwords do not match!");
                        e.preventDefault();
                        return false;
                    }
                    
                    if (newPassword.value.length < 6) {
                        alert("Password must be at least 6 characters long!");
                        e.preventDefault();
                        return false;
                    }
                }
            });
        }
        
        // Password toggle functionality
        const togglePassword = document.querySelector('#togglePassword');
        const newPassword = document.querySelector('#new_password');
        
        if (togglePassword && newPassword) {
            togglePassword.addEventListener('click', function() {
                const type = newPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                newPassword.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
                this.classList.toggle('fa-eye');
            });
        }
        
        // Password strength indicator
        if (newPassword) {
            newPassword.addEventListener('input', function() {
                const strengthMeter = document.querySelector('.strength-meter');
                const strengthText = document.querySelector('.strength-text');
                const passwordValue = this.value;
                let strength = 0;
                
                if (passwordValue.length > 0) strength++;
                if (passwordValue.length >= 8) strength++;
                if (/[A-Z]/.test(passwordValue)) strength++;
                if (/[0-9]/.test(passwordValue)) strength++;
                if (/[^A-Za-z0-9]/.test(passwordValue)) strength++;
                
                const strengthClasses = ['weak', 'fair', 'good', 'strong', 'very-strong'];
                strengthMeter.className = 'strength-meter ' + (strengthClasses[strength-1] || '');
                
                const strengthMessages = [
                    'Very weak',
                    'Weak',
                    'Fair',
                    'Strong',
                    'Very strong'
                ];
                strengthText.textContent = strengthMessages[strength] || 'Password strength';
            });
        }
    </script>
    
    <?php include_once __DIR__ . "/../footer.php"; ?>
</body>
</html>
