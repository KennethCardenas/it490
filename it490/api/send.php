<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('100.87.203.113', 5672, 'guest', 'guest'); // dev-mq IP
$channel = $connection->channel();

list($callbackQueue, ,) = $channel->queue_declare("", false, false, true, false);
$corrId = uniqid();

$response = null;

$channel->basic_consume(
    $callbackQueue,
    '',
    false,
    true,
    false,
    false,
    function ($rep) use (&$response, $corrId) {
        if ($rep->get('correlation_id') === $corrId) {
            $response = $rep->body;
        }
    }
);

$data = ['userId' => 1]; // change to a valid ID from your DB
$msg = new AMQPMessage(
    json_encode($data),
    ['correlation_id' => $corrId, 'reply_to' => $callbackQueue]
);

$channel->basic_publish($msg, '', 'user_request_queue');

while (!$response) {
    $channel->wait();
}

echo "[.] Got response: ", $response, "\n";

$channel->close();
$connection->close();
?>
