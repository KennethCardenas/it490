<?php
declare(strict_types=1);

/**
 * Sends a message to message queue with proper validation and error handling
 * 
 * @param array $payload The message payload to send
 * @return array Response array with status and message/user data
 * @throws InvalidArgumentException If payload is invalid
 */
function sendMessage(array $payload): array {
    // Validate payload structure
    if (!isset($payload['type'])) {
        throw new InvalidArgumentException('Payload must contain a type');
    }

    // Initialize response template
    $response = [
        'status' => 'error',
        'message' => 'Action failed',
        'timestamp' => time()
    ];

    try {
        switch ($payload['type']) {
            case 'login':
                return handleLogin($payload);
                
            case 'register':
                return handleRegistration($payload);
                
            case 'password_reset':
                return handlePasswordReset($payload);
                
            default:
                $response['message'] = 'Unsupported message type';
                return $response;
        }
    } catch (Exception $e) {
        error_log('Message processing failed: ' . $e->getMessage());
        $response['message'] = 'Internal server error';
        return $response;
    }
}

/**
 * Handles login payload
 */
private function handleLogin(array $payload): array {
    // Validate required fields
    if (empty($payload['username']) || empty($payload['password'])) {
        throw new InvalidArgumentException('Username and password are required');
    }

    // In production: Connect to RabbitMQ and send $payload
    // This is simulated response for development:
    if ($payload['username'] === 'admin' && $payload['password'] === 'secure123') {
        return [
            'status' => 'success',
            'user' => [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
                'roles' => ['admin']
            ],
            'timestamp' => time()
        ];
    }

    return [
        'status' => 'error',
        'message' => 'Invalid credentials',
        'timestamp' => time()
    ];
}

/**
 * Handles registration payload
 */
private function handleRegistration(array $payload): array {
    // Validate required fields
    $required = ['username', 'email', 'password'];
    foreach ($required as $field) {
        if (empty($payload[$field])) {
            throw new InvalidArgumentException("$field is required");
        }
    }

    // Validate email format
    if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("Invalid email format");
    }

    // In production: Connect to RabbitMQ and send $payload
    // This is simulated success response for development:
    return [
        'status' => 'success',
        'message' => 'Registration successful',
        'user' => [
            'id' => rand(1000, 9999),
            'username' => $payload['username'],
            'email' => $payload['email']
        ],
        'timestamp' => time()
    ];
}

/**
 * Handles password reset payload
 */
private function handlePasswordReset(array $payload): array {
    // Implementation would go here
    return [
        'status' => 'error',
        'message' => 'Password reset not implemented',
        'timestamp' => time()
    ];
}
