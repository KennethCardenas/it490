<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!', '/');
$channel = $connection->channel();
$channel->queue_declare('test_queue', false, false, false, false);

echo " [*] TEST NODE waiting for messages...\n";

$callback = function ($msg) {
    echo "[TEST NODE] Received: ", $msg->body, "\n";
};

$channel->basic_consume('test_queue', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
