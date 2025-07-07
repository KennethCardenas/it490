<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
if(!hasRole('user')) { echo 'Access denied'; exit(); }
include __DIR__ . '/profile.php';
