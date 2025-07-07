<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

// Restrict access to owners only
if (!isOwner()) {
    echo 'Access denied';
    exit();
}

include __DIR__ . '/profile.php';
