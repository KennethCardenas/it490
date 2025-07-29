<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once __DIR__ . '/../includes/mq_client.php';
    include_once __DIR__ . '/../api/connect.php'; // ✅ For logging

    $payload = [
        "type" => "update_profile",
        "user_id" => $user['id'],
        "username" => trim($_POST['username']),
        "email" => trim($_POST['email']),
        "password" => !empty($_POST['password']) ? $_POST['password'] : null
    ];

    $response = sendMessage($payload);

    if (isset($response['status']) && $response['status'] === 'success') {
        $_SESSION['user'] = $response['user'];
        $user = $response['user']; // update local variable
        $success_message = "Profile updated successfully!";

         // ✅ Log successful profile update
        $conn = include __DIR__ . '/../api/connect.php';
        $userId = intval($user['id']);
        $usernameEscaped = $conn->real_escape_string($user['username'] ?? 'unknown');
        $conn->query("
            INSERT INTO logs (user_id, type, message) 
            VALUES ($userId, 'profile_update', 'User $usernameEscaped updated their profile')
        ");


    } else {
        $error_message = "Update failed: " . htmlspecialchars($response['message'] ?? "Unknown error.");

        
        // ✅ Log failed update
        $conn = include __DIR__ . '/../api/connect.php';
        $userId = intval($user['id']);
        $usernameEscaped = $conn->real_escape_string($user['username'] ?? 'unknown');
        $conn->query("
            INSERT INTO logs (user_id, type, message) 
            VALUES ($userId, 'profile_update_failed', 'User $usernameEscaped failed to update their profile')
        ");

    }
}
?>

<?php $title = "Profile"; include_once __DIR__ . "/../header.php"; ?>
<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        <h2>Your Profile</h2>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?= $success_message ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-error">
            <?= $error_message ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="profile-form">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control"
                   value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group password-toggle">
            <label for="password">New Password (leave blank to keep current)</label>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="Enter new password">
            <i class="fas fa-eye" id="togglePassword" style="cursor:pointer;"></i>
        </div>

        <button type="submit" class="btn-update">Update Profile</button>
    </form>
</div>

<script>
// Form validation
document.querySelector(".profile-form").addEventListener("submit", function(e) {
    const username = document.getElementById("username").value.trim();
    const email = document.getElementById("email").value.trim();
    const passwordValue = document.getElementById("password").value;

    if (username.length < 4 || /\s/.test(username)) {
        alert("Username must be at least 4 characters and contain no spaces.");
        e.preventDefault();
        return;
    }

    if (!email.includes("@")) {
        alert("Enter a valid email address.");
        e.preventDefault();
        return;
    }

    if (passwordValue !== "") {
        if (
            passwordValue.length < 8 ||
            !/[A-Z]/.test(passwordValue) ||
            !/[0-9]/.test(passwordValue) ||
            !/[^A-Za-z0-9]/.test(passwordValue)
        ) {
            alert("If changing password, it must be at least 8 characters and include a number, uppercase letter, and special character.");
            e.preventDefault();
            return;
        }

        const commonPasswords = ["password", "123456", "qwerty"];
        if (commonPasswords.includes(passwordValue.toLowerCase())) {
            alert("Password is too common. Please choose a stronger one.");
            e.preventDefault();
            return;
        }
    }
});

// Toggle password visibility
const togglePassword = document.getElementById('togglePassword');
const passwordField = document.getElementById('password');

togglePassword.addEventListener('click', function () {
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    this.classList.toggle('fa-eye-slash');
    this.classList.toggle('fa-eye');
});
</script>

<?php include_once __DIR__ . "/../footer.php"; ?>
