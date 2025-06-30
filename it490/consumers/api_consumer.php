<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!', '/');
$channel = $connection->channel();
$channel->queue_declare('user_request_queue', false, false, false, false);

echo " [*] API Consumer waiting for messages...\n";

$callback = function ($msg) {
    echo " [x] [API] Received: ", $msg->body, "\n";
    // You can add routing or auth logic here later
};

$channel->basic_consume('user_request_queue', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
