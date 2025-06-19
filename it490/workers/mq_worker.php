<?php
require_once __DIR__ . '/../vendor/autoload.php';


use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('user_actions_queue', false, false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) {
    $payload = json_decode($msg->body, true);

    echo " [x] Received message of type: " . ($payload['type'] ?? 'unknown') . "\n";

    switch ($payload['type'] ?? '') {
        case 'login':
            // Simulate login validation
            if ($payload['username'] === 'admin' && $payload['password'] === 'secure123') {
                echo " [+] Login success for user: {$payload['username']}\n";
            } else {
                echo " [-] Login failed for user: {$payload['username']}\n";
            }
            break;

        case 'register':
            // Simulate registration success
            echo " [+] Registering user: {$payload['username']} with email: {$payload['email']}\n";
            break;

        default:
            echo " [?] Unknown message type\n";
            break;
    }

    // Acknowledge message processed
    $msg->ack();
};

$channel->basic_consume('user_actions_queue', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
