<?php
include_once("auth.php");
requireAuth();
include_once("navbar.php");
?>

<link rel="stylesheet" href="css/style.css">
<div class="container">
  <h2>Welcome, <?= $_SESSION['user']['username'] ?>!</h2>
  <p>This is your landing page after login.</p>
</div>
