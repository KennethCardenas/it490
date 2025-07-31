<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function sendMessage(array $payload): array {
    if (!isset($payload['type'])) {
        throw new InvalidArgumentException('Payload must contain a type');
    }

    switch ($payload['type']) {
        case 'login':
            if (empty($payload['username']) || empty($payload['password'])) {
                throw new InvalidArgumentException('Username and password are required');
            }
            break;

        case 'register':
            foreach (['username', 'email', 'password'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email format');
            }
            break;

        case 'update_profile':
            foreach (['user_id', 'username', 'email'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email format');
            }
            break;

        case 'logout':
            if (empty($payload['user_id'])) {
                throw new InvalidArgumentException('user_id is required for logout');
            }
            break;

        case 'add_dog':
            foreach (['owner_id', 'name', 'breed'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'list_dogs':
            if (empty($payload['owner_id'])) {
                throw new InvalidArgumentException('owner_id is required');
            }
            break;

        case 'get_dog':
            if (empty($payload['dog_id'])) {
                throw new InvalidArgumentException('dog_id is required');
            }
            break;

        case 'update_dog':
            foreach (['dog_id', 'owner_id', 'name', 'breed'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'get_sitter_profile':
        case 'update_sitter_profile':
            if (empty($payload['user_id'])) {
                throw new InvalidArgumentException('user_id is required');
            }
            break;

        case 'list_sitters':
            // no validation needed
            break;

        case 'grant_dog_access':
            foreach (['dog_id', 'sitter_id', 'owner_id'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'list_active_dogs':
            if (empty($payload['sitter_id'])) {
                throw new InvalidArgumentException('sitter_id is required');
            }
            break;

        case 'record_activity':
            foreach (['dog_id', 'sitter_id', 'description'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'list_activities':
            if (empty($payload['dog_id'])) {
                throw new InvalidArgumentException('dog_id is required');
            }
            break;

        default:
            throw new InvalidArgumentException("Unsupported message type: {$payload['type']}");
    }

    try {
        $connection = new AMQPStreamConnection(
            '100.87.203.113',
            5672,
            'kac63',
            'Linklinkm1!',
            '/',
            false,
            'AMQPLAIN',
            null,
            'en_US',
            30.0,
            30.0,
            null,
            false,
            30
        );
        $channel = $connection->channel();
        $channel->queue_declare('user_request_queue', false, false, false, false);

        list($callbackQueue,) = $channel->queue_declare('', false, false, true, true);
        $corrId = uniqid();

        $msg = new AMQPMessage(json_encode($payload), [
            'correlation_id' => $corrId,
            'reply_to' => $callbackQueue
        ]);

        $channel->basic_publish($msg, '', 'user_request_queue');

        $response = null;
        $timeout = 0;
        $maxTimeout = 30;

        $channel->basic_consume($callbackQueue, '', false, true, true, false,
            function ($rep) use (&$response, $corrId) {
                if ($rep->get('correlation_id') === $corrId) {
                    $response = json_decode($rep->body, true);
                }
            });

        while (!$response && $timeout < $maxTimeout) {
            try {
                $channel->wait(null, false, 1);
                $timeout++;
            } catch (Exception $e) {
                $timeout++;
                continue;
            }
        }

        $channel->close();
        $connection->close();

        if (!$response) {
            return ['status' => 'error', 'message' => 'Request timeout - worker may not be running'];
        }

        return $response;

    } catch (Exception $e) {
        error_log('MQ Connection failed: ' . $e->getMessage());
        return ['status' => 'error', 'message' => 'Failed to send message: ' . $e->getMessage()];
    }
}
