<?php
session_start();

function isAuthenticated() {
    return isset($_SESSION['user']);
}

function requireAuth() {
    if (!isAuthenticated()) {
        header("Location: login.php");
        exit();
    }
}
?>
