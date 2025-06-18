<?php
function sendMessage($payload) {
    // In production: connect to RabbitMQ and send $payload
    // Simulated response for development:
    if ($payload['type'] === 'login') {
        return ($payload['username'] === 'admin') ? [
            'status' => 'success',
            'user' => ['username' => 'admin', 'email' => 'admin@example.com']
        ] : ['status' => 'error', 'message' => 'Invalid credentials'];
    } elseif ($payload['type'] === 'register') {
        return ['status' => 'success'];
    }
    return ['status' => 'error', 'message' => 'Unhandled type'];
}
?>
