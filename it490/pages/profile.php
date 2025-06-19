<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once __DIR__ . '/../includes/mq_client.php';
    $payload = [
        "type" => "update_profile",
        "user_id" => $user['id'],
        "username" => $_POST['username'],
        "email" => $_POST['email'],
        "password" => !empty($_POST['password']) ? $_POST['password'] : null
    ];
    
    $response = sendMessage($payload);
    if ($response['status'] === 'success') {
        $_SESSION['user'] = $response['user'];
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Update failed: " . $response['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | <?= htmlspecialchars($user['username']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include_once __DIR__ . '/../navbar.php'; ?>
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h2>Your Profile</h2>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
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
                <i class="fas fa-eye" id="togglePassword"></i>
            </div>

            <button type="submit" class="btn-update">Update Profile</button>
        </form>
    </div>

    <script>
        // Password toggle functionality
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
    </script>
</body>
</html>
