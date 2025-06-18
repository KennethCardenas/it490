<?php
include_once("auth.php");
include_once("navbar.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Send register data to MQ
    include_once("includes/mq_client.php");
    $payload = [
        "type" => "register",
        "username" => $_POST['username'],
        "email" => $_POST['email'],
        "password" => $_POST['password']
    ];
    $response = sendMessage($payload);
    if ($response['status'] === 'success') {
        echo "<p>Registration successful!</p>";
    } else {
        echo "<p>Registration failed: " . $response['message'] . "</p>";
    }
}
?>

<link rel="stylesheet" href="css/style.css">
<div class="container">
  <h2>Register</h2>
  <form method="POST">
    <input type="text" name="username" placeholder="Username" required />
    <input type="email" name="email" placeholder="Email" required />
    <input type="password" name="password" placeholder="Password" required />
    <button type="submit">Register</button>
  </form>
</div>
