<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api/connect.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!', '/');
$channel = $connection->channel();
$channel->queue_declare('user_request_queue', false, true, false, false);

echo " [*] Waiting for messages...\n";

$callback = function ($msg) {
    echo " [x] Received ", $msg->body, "\n";
    // Add logic to handle the message
};

$channel->basic_consume('user_request_queue', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
