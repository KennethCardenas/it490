<?php
require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/api/connect.php'; // defines $conn (MySQLi)

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('user_request_queue', false, false, false, false);

echo "[*] Waiting for messages on 'user_request_queue'. To exit press CTRL+C\n";

$callback = function ($msg) use ($conn) {
    echo "[x] Received ", $msg->body, "\n";
    $request = json_decode($msg->body, true);
    $userId = intval($request['userId']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if ($stmt === false) {
        $response = json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    } else {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $response = json_encode($result ?: ['error' => 'User not found']);
    }

    $reply = new AMQPMessage(
        $response,
        ['correlation_id' => $msg->get('correlation_id')]
    );

    $msg->getChannel()->basic_publish($reply, '', $msg->get('reply_to'));
    $msg->getChannel()->basic_ack($msg->delivery_info['delivery_tag']);
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('user_request_queue', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
?>
