<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php'; // Adjust path if needed

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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

    // Validate required fields depending on type (basic example)
    switch ($payload['type']) {
        case 'login':
            if (empty($payload['username']) || empty($payload['password'])) {
                throw new InvalidArgumentException('Username and password are required');
            }
            break;
        case 'register':
            $required = ['username', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($payload[$field])) {
                    throw new InvalidArgumentException("$field is required");
                }
            }
            if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Invalid email format");
            }
            break;
        case 'password_reset':
            // Add validations as needed
            break;
        case 'update_profile':
            $required = ['user_id', 'username', 'email'];
            foreach ($required as $field) {
                if (empty($payload[$field])) {
                    throw new InvalidArgumentException("$field is required");
                }
            }
            if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email format');
            }
            break;
        default:
            return [
                'status' => 'error',
                'message' => 'Unsupported message type',
                'timestamp' => time()
            ];
    }

    // Try sending message to RabbitMQ
    try {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        // Declare the queue (idempotent)
        $channel->queue_declare('user_actions_queue', false, false, false, false);

        // Create message with JSON payload
        $msg = new AMQPMessage(json_encode($payload));

        // Publish message to queue
        $channel->basic_publish($msg, '', 'user_actions_queue');

        // Close connections
        $channel->close();
        $connection->close();

        return [
            'status' => 'sent',
            'message' => 'Message sent to queue',
            'timestamp' => time()
        ];
    } catch (Exception $e) {
        error_log('MQ Connection failed: ' . $e->getMessage());

        return [
            'status' => 'error',
            'message' => 'Failed to send message: ' . $e->getMessage(),
            'timestamp' => time()
        ];
    }
}

