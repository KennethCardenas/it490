<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$pdo = new PDO("mysql:host=localhost;dbname=your_db;charset=utf8mb4", "your_user", "your_password");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('user_actions_queue', false, false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) use ($pdo) {
    $payload = json_decode($msg->body, true);
    echo " [x] Received message of type: " . ($payload['type'] ?? 'unknown') . "\n";

    $responsePayload = [
        'type' => $payload['type'] ?? 'unknown',
        'status' => 'error', 
        'message' => 'Unhandled request',
    ];

    switch ($payload['type'] ?? '') {
        case 'login':
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$payload['username']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($payload['password'], $user['password'])) {
                    echo " [+] Login success for user: {$payload['username']}\n";
                    $responsePayload['status'] = 'success';
                    $responsePayload['message'] = 'Login successful';
                    $responsePayload['user'] = [
                        'username' => $user['username'],
                    ];
                } else {
                    echo " [-] Login failed for user: {$payload['username']}\n";
                    $responsePayload['message'] = 'Invalid username or password';
                }
            } catch (PDOException $e) {
                echo " [-] Login error: " . $e->getMessage() . "\n";
                $responsePayload['message'] = 'Login error: ' . $e->getMessage();
            }
            break;

        case 'register':
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$payload['username'], $payload['email']]);
                $exists = $stmt->fetchColumn();

                if ($exists > 0) {
                    $responsePayload['message'] = 'Username or email already exists';
                    echo " [-] Registration failed: " . $responsePayload['message'] . "\n";
                    break;
                }

                $hashedPassword = password_hash($payload['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([
                    $payload['username'],
                    $payload['email'],
                    $hashedPassword
                ]);

                $responsePayload['status'] = 'success';
                $responsePayload['message'] = 'Registration successful';
                echo " [+] Registered user: {$payload['username']}\n";

            } catch (PDOException $e) {
                echo " [-] Registration failed: " . $e->getMessage() . "\n";
                $responsePayload['message'] = 'Registration error: ' . $e->getMessage();
            }
            break;

        default:
            echo " [?] Unknown message type\n";
            $responsePayload['message'] = 'Unsupported action type';
            break;
    }

    if (!empty($msg->get('reply_to'))) {
        $responseMsg = new AMQPMessage(
            json_encode($responsePayload),
            ['correlation_id' => $msg->get('correlation_id')]
        );
        $msg->getChannel()->basic_publish($responseMsg, '', $msg->get('reply_to'));
    }

    //Acknowledge message processed
    $msg->ack();
};

$channel->basic_consume('user_actions_queue', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
