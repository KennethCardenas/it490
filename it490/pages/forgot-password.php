<?php
session_start();
$message = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
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
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../navbar.php'; ?>

    <div class="container">
        <h2>Forgot Your Password?</h2>

        <?php if ($message): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form action="send-reset.php" method="POST">
            <label for="email">Email Address:</label>
            <input type="email" name="email" required placeholder="you@example.com">
            <input type="submit" value="Send Reset Link">
        </form>
    </div>
</body>
</html>
