<?php
session_start();
include_once __DIR__ . '/../includes/mq_client.php'; // Make sure this path is correct

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    // Build MQ payload
    $payload = [
        "type" => "password_reset",
        "email" => $email
    ];

    // Send to message queue
    $response = sendMessage($payload);

    // Set session message based on response
    $_SESSION['message'] = $response['status'] === 'success'
        ? "If this email is registered, a reset link was sent to $email."
        : "Error: " . $response['message'];

    header("Location: forgot-password.php");
    exit;
}

header("Location: forgot-password.php");
exit;