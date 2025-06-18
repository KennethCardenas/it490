<?php
include("auth.php");
if (isAuthenticated()) {
    header("Location: landing.php");
} else {
    header("Location: login.php");
}
exit();
?>
