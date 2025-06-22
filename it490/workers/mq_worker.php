<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api/connect.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Database connection (reuse from connect.php)
global $conn;

// Password hashing configuration
define('PASSWORD_BCRYPT_COST', 12);

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('user_actions_queue', false, true, false, false);
$channel->queue_declare('response_queue', false, true, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

function validateEmailOrUsername($input) {
    if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
        return ['field' => 'email', 'value' => $input];
    }
    return ['field' => 'username', 'value' => $input];
}

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST]);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

function checkDuplicateCredentials($conn, $username, $email, $excludeUserId = null) {
    $errors = [];
    
    $query = "SELECT id FROM USERS WHERE username = ?";
    if ($excludeUserId) {
        $query .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($query);
    if ($excludeUserId) {
        $stmt->bind_param("si", $username, $excludeUserId);
    } else {
        $stmt->bind_param("s", $username);
    }
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username already exists";
    }
    
    $query = "SELECT id FROM USERS WHERE email = ?";
    if ($excludeUserId) {
        $query .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($query);
    if ($excludeUserId) {
        $stmt->bind_param("si", $email, $excludeUserId);
    } else {
        $stmt->bind_param("s", $email);
    }
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email already exists";
    }
    
    return $errors;
}

$callback = function ($msg) use ($channel, $conn) {
    try {
        $payload = json_decode($msg->body, true);
        $response = ['status' => 'error', 'message' => 'Unknown action'];
        
        echo " [x] Processing: " . ($payload['type'] ?? 'unknown') . "\n";

        switch ($payload['type'] ?? '') {
            case 'login':
                // Validate login with email or username
                $credential = validateEmailOrUsername($payload['username']);
                $query = "SELECT id, username, email, password FROM USERS WHERE {$credential['field']} = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $credential['value']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (verifyPassword($payload['password'], $user['password'])) {
                        $response = [
                            'status' => 'success',
                            'user' => [
                                'id' => $user['id'],
                                'username' => $user['username'],
                                'email' => $user['email']
                            ]
                        ];
                        echo " [+] Login success for user: {$user['username']}\n";
                    } else {
                        $response['message'] = "Invalid credentials";
                        echo " [-] Login failed (bad password)\n";
                    }
                } else {
                    $response['message'] = "User not found";
                    echo " [-] Login failed (user not found)\n";
                }
                break;

            case 'register':
                // Check for duplicate credentials
                $duplicateErrors = checkDuplicateCredentials($conn, $payload['username'], $payload['email']);
                
                if (!empty($duplicateErrors)) {
                    $response['message'] = implode(", ", $duplicateErrors);
                    echo " [-] Registration failed: " . $response['message'] . "\n";
                    break;
                }
                
                // Hash password and create user
                $hashedPassword = hashPassword($payload['password']);
                $query = "INSERT INTO USERS (username, email, password, created_at) VALUES (?, ?, ?, NOW())";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sss", $payload['username'], $payload['email'], $hashedPassword);
                
                if ($stmt->execute()) {
                    $userId = $stmt->insert_id;
                    $response = [
                        'status' => 'success',
                        'message' => 'Registration successful',
                        'user' => [
                            'id' => $userId,
                            'username' => $payload['username'],
                            'email' => $payload['email']
                        ]
                    ];
                    echo " [+] Registered user: {$payload['username']}\n";
                } else {
                    $response['message'] = "Database error: " . $conn->error;
                    echo " [-] Registration failed: " . $response['message'] . "\n";
                }
                break;

            case 'update_profile':
                if (empty($payload['user_id'])) {
                    $response['message'] = "User ID required";
                    break;
                }
                
                // Check for duplicate credentials (excluding current user)
                $duplicateErrors = checkDuplicateCredentials(
                    $conn, 
                    $payload['username'], 
                    $payload['email'], 
                    $payload['user_id']
                );
                
                if (!empty($duplicateErrors)) {
                    $response['message'] = implode(", ", $duplicateErrors);
                    echo " [-] Profile update failed: " . $response['message'] . "\n";
                    break;
                }
                
                // Prepare update query
                $query = "UPDATE USERS SET username = ?, email = ?";
                $params = [$payload['username'], $payload['email']];
                $types = "ss";
                
                // Add password update if provided
                if (!empty($payload['password'])) {
                    $query .= ", password = ?";
                    $hashedPassword = hashPassword($payload['password']);
                    $params[] = $hashedPassword;
                    $types .= "s";
                }
                
                $query .= " WHERE id = ?";
                $params[] = $payload['user_id'];
                $types .= "i";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                
                if ($stmt->execute()) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Profile updated',
                        'user' => [
                            'id' => $payload['user_id'],
                            'username' => $payload['username'],
                            'email' => $payload['email']
                        ]
                    ];
                    echo " [+] Updated profile for user ID: {$payload['user_id']}\n";
                } else {
                    $response['message'] = "Update failed: " . $conn->error;
                    echo " [-] Profile update failed: " . $response['message'] . "\n";
                }
                break;

            case 'password_reset':
                // Implementation for password reset
                // This would typically involve generating a token, storing it with expiration,
                // and sending an email with a reset link
                $response['message'] = "Password reset functionality not yet implemented";
                echo " [?] Password reset requested\n";
                break;

            default:
                $response['message'] = "Unsupported action type";
                echo " [?] Unknown message type\n";
                break;
        }
        
        // Send response back if correlation_id is set
        if (isset($payload['correlation_id'])) {
            $responseMsg = new AMQPMessage(
                json_encode($response),
                ['correlation_id' => $payload['correlation_id']]
            );
            $channel->basic_publish($responseMsg, '', 'response_queue');
        }
        
        $msg->ack();
    } catch (Exception $e) {
        error_log("Error processing message: " . $e->getMessage());
        echo " [!] Error: " . $e->getMessage() . "\n";
    }
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('user_actions_queue', '', false, false, false, false, $callback);

try {
    while ($channel->is_consuming()) {
        $channel->wait();
    }
} catch (Exception $e) {
    echo "Channel error: " . $e->getMessage() . "\n";
    $channel->close();
    $connection->close();
    exit(1);
}

$channel->close();
$connection->close();