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
            foreach (['username','email','password'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email format');
            }
            break;
        case 'update_profile':
            foreach (['user_id','username','email'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email format');
            }
            break;
    }

    try {
        // Use the same RabbitMQ connection details as send.php
        $connection = new AMQPStreamConnection('100.87.203.113', 5672, 'JS2624', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare('user_actions_queue', false, true, false, false);
        list($callbackQueue,) = $channel->queue_declare('', false, false, true, true);
        $corrId = uniqid();
        $msg = new AMQPMessage(json_encode($payload), [
            'correlation_id' => $corrId,
            'reply_to' => $callbackQueue
        ]);
        $channel->basic_publish($msg, '', 'user_actions_queue');
        $response = null;
        $channel->basic_consume($callbackQueue, '', false, true, true, false,
            function ($rep) use (&$response, $corrId) {
                if ($rep->get('correlation_id') === $corrId) {
                    $response = json_decode($rep->body, true);
                }
            });
        while (!$response) {
            $channel->wait();
        }
        $channel->close();
        $connection->close();
        return $response;
    } catch (Exception $e) {
        error_log('MQ Connection failed: ' . $e->getMessage());
        return ['status'=>'error','message'=>'Failed to send message: '.$e->getMessage()];
    }
}
