<?php
require_once __DIR__ . '/../auth.php';
requireAuth(); // Redirects to login if not authenticated

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | BarkBuddy</title>
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <h1>Welcome, <?= htmlspecialchars($user['username']) ?>!</h1>
    <p>Email: <?= htmlspecialchars($user['email']) ?></p>

    <a href="logout.php">Logout</a>
</body>
</html>
