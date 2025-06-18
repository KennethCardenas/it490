<?php include_once("auth.php"); ?>
<div class="navbar">
  <a href="landing.php">Home</a>
  <?php if (isAuthenticated()): ?>
    <a href="profile.php">Profile</a>
    <a href="logout.php">Logout</a>
  <?php else: ?>
    <a href="register.php">Register</a>
    <a href="login.php">Login</a>
  <?php endif; ?>
</div>
