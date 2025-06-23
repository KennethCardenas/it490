<?php
include_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once __DIR__ . '/../includes/mq_client.php';
    $payload = [
        "type" => "register",
        "username" => $_POST['username'],
        "email" => $_POST['email'],
        "password" => $_POST['password']
    ];
    $response = sendMessage($payload);
    
    if ($response['status'] === 'success') {
        $success_message = "Registration successful! You can now login.";
    } else {
        $error_message = "Registration failed: " . $response['message'];
    }
}
?>

<?php $title = "Register"; include_once __DIR__ . "/../header.php"; ?>
<div class="register-container">
    <div class="register-card">
            <div class="register-header">
                <h2>Create Account</h2>
                <p>Join our community today</p>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form class="register-form" method="POST">
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
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                    <div class="password-strength">
                        <div class="strength-meter"></div>
                        <span class="strength-text">Password strength</span>
                    </div>
                </div>
                
                <button type="submit" class="btn-register">Create Account</button>
            </form>
            
            <div class="register-footer">
                <p>Already have an account? </p>
                <a href="login.php">Sign in</a>
            </div>
        </div>
    </div>
    <img src="../images/dog3.png" alt="dog silhouette" id="dog3">
    <img src="../images/dog4.png" alt="dog silhouette" id="dog4">

    <script>
        const form = document.querySelector(".register-form");
        form.addEventListener("submit", function(e) {
            const emailField = document.getElementById("email");
            if (!emailField.value.includes("@")) {
                alert("Please enter a valid email address");
                e.preventDefault();
            }
        });
        // Password toggle functionality
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
        
        // Basic password strength indicator
        password.addEventListener('input', function() {
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
    </script>
<?php include_once __DIR__ . "/../footer.php"; ?>
