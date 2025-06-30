<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Update with the IP of your MQ server
$connection = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!');
$channel = $connection->channel();

// Make sure this matches the queue your consumer listens to
$queueName = 'test_queue';

$channel->queue_declare($queueName, false, true, false, false);

$data = json_encode([
    'type' => 'test',
    'message' => 'Hello from send.php'
]);

$msg = new AMQPMessage($data, ['delivery_mode' => 2]);
$channel->basic_publish($msg, '', $queueName);

echo "[x] Sent: $data\n";

$channel->close();
$connection->close();
