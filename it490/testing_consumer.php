<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api/connect.php'; // $conn (MySQLi)

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!');
$channel = $connection->channel();
$channel->queue_declare('user_request_queue', false, false, false, false);

echo "[*] Waiting for messages on 'user_request_queue'. To exit press CTRL+C\n";

$callback = function ($msg) use ($conn) {
    echo "[x] Received ", $msg->body, "\n";
    $request = json_decode($msg->body, true);
    $type = $request['type'] ?? '';
    $response = ['error' => 'Invalid request'];

    if ($type === 'get_user_by_id') {
        $userId = intval($request['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM USERS WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $response = $result ?: ['error' => 'User not found'];
        } else {
            $response = ['error' => 'Prepare failed: ' . $conn->error];
        }
    } elseif ($type === 'register') {
        $username = $request['username'] ?? '';
        $email = $request['email'] ?? '';
        $password = $request['password'] ?? '';

        if ($username && $email && $password) {
            $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check->bind_param("ss", $username, $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $response = ['error' => 'Username or email already exists'];
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $email, $hashedPassword);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'User registered'];
                } else {
                    $response = ['error' => 'Insert failed: ' . $stmt->error];
                }
                $stmt->close();
            }

            $check->close();
        } else {
            $response = ['error' => 'Missing username, email, or password'];
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
?>
