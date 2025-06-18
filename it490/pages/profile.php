<?php
include_once("auth.php");
requireAuth();
include_once("navbar.php");

$user = $_SESSION['user'];
?>

<link rel="stylesheet" href="css/style.css">
<div class="container">
  <h2>Your Profile</h2>
  <form method="POST">
    <input type="text" name="username" value="<?= $user['username'] ?>" required />
    <input type="email" name="email" value="<?= $user['email'] ?>" required />
    <input type="password" name="password" placeholder="New Password" />
    <button type="submit">Update</button>
  </form>
</div>
