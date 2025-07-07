<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

// Resolved: use isOwner() to restrict access appropriately
if (!isOwner()) {
    echo 'Access denied';
    exit();
}

include __DIR__ . '/profile.php';
