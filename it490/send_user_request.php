<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Connect to RabbitMQ (same config as consumer)
$connection = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!', '/');
$channel = $connection->channel();

// Declare the same queue (must match in all flags!)
$channel->queue_declare('user_request_queue', false, false, false, false);

// Create and send test message
$data = json_encode([
    'type' => 'test',
    'payload' => 'Hello API consumer!'
]);
$msg = new AMQPMessage($data);

$channel->basic_publish($msg, '', 'user_request_queue');

echo "[x] Sent message to user_request_queue\n";

// Clean up
$channel->close();
$connection->close();
?>
