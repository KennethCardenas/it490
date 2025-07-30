<?php
require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/api/connect.php'; // defines $conn (MySQLi)

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!');
$channel = $connection->channel();

$channel->queue_declare('user_request_queue', false, false, false, false);

echo "[*] Waiting for messages on 'user_request_queue'. To exit press CTRL+C\n";

$callback = function ($msg) use ($conn) {
    echo "[x] Received ", $msg->body, "\n";
    $request = json_decode($msg->body, true);
    $response = [];

    if (!isset($request['type'])) {
        $response = ['error' => 'Missing request type'];
    } else {
        switch ($request['type']) {
            case 'login':
                $username = $request['username'] ?? '';
                $password = $request['password'] ?? '';

                $stmt = $conn->prepare("SELECT * FROM USERS WHERE USERNAME = ?");
                if ($stmt === false) {
                    $response = ['error' => 'Prepare failed: ' . $conn->error];
                } else {
                    $stmt->bind_param('s', $username);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if ($result && password_verify($password, $result['PASSWORD'])) {
                        unset($result['PASSWORD']); // Hide password hash
                        $response = $result;
                    } else {
                        $response = ['error' => 'Invalid credentials'];
                    }
                }
                break;

            case 'get_user':
                if (!isset($request['userId'])) {
                    $response = ['error' => 'Missing userId'];
                } else {
                    $userId = intval($request['userId']);
                    $stmt = $conn->prepare("SELECT * FROM USERS WHERE ID = ?");
                    if ($stmt === false) {
                        $response = ['error' => 'Prepare failed: ' . $conn->error];
                    } else {
                        $stmt->bind_param('i', $userId);
                        $stmt->execute();
                        $result = $stmt->get_result()->fetch_assoc();
                        $stmt->close();

                        $response = $result ?: ['error' => 'User not found'];
                    }
                }
                break;

            default:
                $response = ['error' => 'Unknown request type'];
        }
    }

    $reply = new AMQPMessage(
        json_encode($response),
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
