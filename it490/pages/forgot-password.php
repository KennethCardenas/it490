<?php
session_start();
$message = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<?php $title = "Forgot Password"; include_once __DIR__ . "/../header.php"; ?>

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
<?php include_once __DIR__ . "/../footer.php"; ?>
