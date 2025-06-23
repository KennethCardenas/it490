<?php
include_once __DIR__ . '/../auth.php';

// Initialize error message
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once __DIR__ . '/../includes/mq_client.php';
    
    $payload = [
        "type" => "login",
        "username" => $_POST['username'],
        "password" => $_POST['password']
    ];
    
    $response = sendMessage($payload);
    
    if ($response['status'] === 'success') {
        startSecureSession();
        $_SESSION['user'] = $response['user'];
        
        // Redirect to return URL or landing page
        $returnUrl = getReturnUrl();
        header("Location: $returnUrl");
        exit();
    } else {
        $error_message = "Login failed: " . $response['message'];
    }
}
?>

<?php $title = "Login"; include_once __DIR__ . "/../header.php"; ?>
        <div class="login-header">
<div class="login-container">
            <h2>Welcome Back</h2>
            <p>Please enter your credentials to login</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
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
<script>
const loginForm = document.querySelector(".login-form");
loginForm.addEventListener("submit", function(e){
  if(document.getElementById("username").value.trim()==="" || document.getElementById("password").value.trim()===""){
    alert("All fields are required");
    e.preventDefault();
  }
});
</script>
    </div>
<?php include_once __DIR__ . "/../footer.php"; ?>
