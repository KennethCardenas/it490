<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    // 👉 In a real project, here you'd check if the email exists in your database
    // and generate a reset token to send a real email.

    // For now, just simulate success
    $_SESSION['message'] = "If this email is registered, a reset link was sent to $email.";
    header("Location: forgot-password.php");
    exit;
}

header("Location: forgot-password.php");
exit;