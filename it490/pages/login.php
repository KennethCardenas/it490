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
        echo "<p>Login failed: " . $response['message'] . "</p>";
    }
}
?>

<link rel="stylesheet" href="css/style.css">
<div class="container">
  <h2>Login</h2>
  <form method="POST">
    <input type="text" name="username" placeholder="Username or Email" required />
    <input type="password" name="password" placeholder="Password" required />
    <button type="submit">Login</button>
  </form>
</div>
