<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!');
$channel = $connection->channel();

$data = json_encode(['type' => 'test', 'payload' => 'Hello from sender']);
$msg = new AMQPMessage($data);

$channel->basic_publish($msg, '', 'user_request_queue');

echo " [x] Sent message\n";

$channel->close();
$connection->close();
?>
