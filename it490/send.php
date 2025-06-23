<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Connect to RabbitMQ on dev-mq VM with username kac63
$connection = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!');
$channel = $connection->channel();

// Create a unique callback queue
list($callbackQueue, ,) = $channel->queue_declare("", false, false, true, false);
$corrId = uniqid();
$response = null;

// Consume the callback response
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

// Send request with userId payload
$data = ['userId' => 1]; // Replace with a valid ID in your database
$msg = new AMQPMessage(
    json_encode($data),
    ['correlation_id' => $corrId, 'reply_to' => $callbackQueue]
);

// Publish the message to the user_request_queue
$channel->basic_publish($msg, '', 'user_request_queue');

// Wait for response
while (!$response) {
    $channel->wait();
}

echo "[.] Got response: ", $response, "\n";

// Clean up
$channel->close();
$connection->close();
?>
