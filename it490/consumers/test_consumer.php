<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!', '/');
$channel = $connection->channel();

// Replace 'test_queue' with your actual queue name
$channel->queue_declare('user_request_queue', false, false, false, false);

echo " [*] TEST NODE now listening to 'user_request_queue'...\n";

$callback = function ($msg) {
    echo "[TEST NODE] Received: ", $msg->body, "\n";
};

$channel->basic_consume('user_request_queue', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
