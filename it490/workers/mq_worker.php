<?php 
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api/connect.php';

use PhpAmqpLib\\Connection\\AMQPStreamConnection;
use PhpAmqpLib\\Message\\AMQPMessage;

define('PASSWORD_BCRYPT_COST', 12);

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST]);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

function validateEmailOrUsername($input) {
    return filter_var($input, FILTER_VALIDATE_EMAIL)
        ? ['field' => 'email', 'value' => $input]
        : ['field' => 'username', 'value' => $input];
}

function checkDuplicateCredentials($conn, $username, $email, $excludeUserId = null) {
    $errors = [];

    $query = "SELECT id FROM USERS WHERE username = ?";
    if ($excludeUserId) $query .= " AND id != ?";
    $stmt = $conn->prepare($query);
    $excludeUserId ? $stmt->bind_param("si", $username, $excludeUserId) : $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) $errors[] = "Username already exists";

    $query = "SELECT id FROM USERS WHERE email = ?";
    if ($excludeUserId) $query .= " AND id != ?";
    $stmt = $conn->prepare($query);
    $excludeUserId ? $stmt->bind_param("si", $email, $excludeUserId) : $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) $errors[] = "Email already exists";

    return $errors;
}

try {
    $connection = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!');
    echo " [*] Connected to RabbitMQ at 100.87.203.113\n";
} catch (Exception $e) {
    echo " [!] Failed to connect to RabbitMQ: " . $e->getMessage() . "\n";
    exit(1);
}

$channel = $connection->channel();
$channel->queue_declare('user_request_queue', false, false, false, false);

echo " [*] Waiting for messages on 'user_request_queue'. To exit press CTRL+C\n";

$callback = function ($msg) use ($channel, $conn) {
    try {
        $payload = json_decode($msg->body, true);
        $response = ['status' => 'error', 'message' => 'Unknown action'];

        echo " [x] Processing: " . ($payload['type'] ?? 'unknown') . "\n";

        switch ($payload['type'] ?? '') {
            case 'login':
                $credential = validateEmailOrUsername($payload['username']);
                $query = "SELECT id, username, email, role, password FROM USERS WHERE {$credential['field']} = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $credential['value']);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (verifyPassword($payload['password'], $user['password'])) {
                        $response = [
                            'status' => 'success',
                            'user' => [
                                'id' => $user['id'],
                                'username' => $user['username'],
                                'email' => $user['email'],
                                'role' => $user['role']
                            ]
                        ];
                        echo " [+] Login success for user: {$user['username']}\\n";
                    } else {
                        $response['message'] = "Invalid credentials";
                        echo " [-] Login failed (bad password)\\n";
                    }
                } else {
                    $response['message'] = "User not found";
                    echo " [-] Login failed (user not found)\\n";
                }
                break;

            case 'register':
                $duplicateErrors = checkDuplicateCredentials($conn, $payload['username'], $payload['email']);
                if (!empty($duplicateErrors)) {
                    $response['message'] = implode(", ", $duplicateErrors);
                    echo " [-] Registration failed: " . $response['message'] . "\\n";
                    break;
                }

                $hashedPassword = hashPassword($payload['password']);
                $role = $payload['role'] ?? 'user';
                $query = "INSERT INTO USERS (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssss", $payload['username'], $payload['email'], $hashedPassword, $role);

                if ($stmt->execute()) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Registration successful',
                        'user' => [
                            'id' => $stmt->insert_id,
                            'username' => $payload['username'],
                            'email' => $payload['email']
                        ]
                    ];
                    echo " [+] Registered user: {$payload['username']}\\n";
                } else {
                    $response['message'] = "Database error: " . $conn->error;
                    echo " [-] Registration failed: " . $response['message'] . "\\n";
                }
                break;

            case 'update_profile':
                if (empty($payload['user_id'])) {
                    $response['message'] = "User ID required";
                    break;
                }

                $duplicateErrors = checkDuplicateCredentials($conn, $payload['username'], $payload['email'], $payload['user_id']);
                if (!empty($duplicateErrors)) {
                    $response['message'] = implode(", ", $duplicateErrors);
                    echo " [-] Profile update failed: " . $response['message'] . "\\n";
                    break;
                }

                $query = "UPDATE USERS SET username = ?, email = ?";
                $params = [$payload['username'], $payload['email']];
                $types = "ss";

                if (!empty($payload['password'])) {
                    $query .= ", password = ?";
                    $params[] = hashPassword($payload['password']);
                    $types .= "s";
                }

                $query .= " WHERE id = ?";
                $params[] = $payload['user_id'];
                $types .= "i";

                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    $roleQuery = $conn->prepare("SELECT role FROM USERS WHERE id = ?");
                    $roleQuery->bind_param("i", $payload['user_id']);
                    $roleQuery->execute();
                    $roleResult = $roleQuery->get_result();
                    $roleRow = $roleResult->fetch_assoc();

                    $response = [
                        'status' => 'success',
                        'message' => 'Profile updated',
                        'user' => [
                            'id' => $payload['user_id'],
                            'username' => $payload['username'],
                            'email' => $payload['email'],
                            'role' => $roleRow['role'] ?? 'user'
                        ]
                    ];
                    echo " [+] Updated profile for user ID: {$payload['user_id']}\\n";
                } else {
                    $response['message'] = "Update failed: " . $conn->error;
                    echo " [-] Profile update failed: " . $response['message'] . "\\n";
                }
                break;

            case 'password_reset':
                $response['message'] = "Password reset functionality not yet implemented";
                echo " [?] Password reset requested\\n";
                break;

            case 'logout':
                if (!empty($payload['user_id'])) {
                    echo " [+] Logout event for user ID: {$payload['user_id']}\n";
                    $response = ['status' => 'success', 'message' => 'Logout recorded'];
                } else {
                    $response['message'] = 'User ID missing for logout';
                    echo " [-] Logout failed: Missing user ID\n";
                }
                break;

            case 'add_dog':
                $stmt = $conn->prepare("INSERT INTO DOGS (owner_id, name, breed, age, notes, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $age = $payload['age'] ?? null;
                $notes = $payload['notes'] ?? '';
                $stmt->bind_param('issis', $payload['owner_id'], $payload['name'], $payload['breed'], $age, $notes);
                if ($stmt->execute()) {
                    $response = ['status'=>'success','dog_id'=>$stmt->insert_id];
                    echo " [+] Added dog {$payload['name']} for user {$payload['owner_id']}\n";
                } else {
                    $response['message'] = 'Failed to add dog: ' . $conn->error;
                    echo " [-] Failed to add dog: {$conn->error}\n";
                }
                break;

            case 'list_dogs':
                $stmt = $conn->prepare("SELECT id, name, breed, age, notes FROM DOGS WHERE owner_id = ? ORDER BY created_at DESC");
                $stmt->bind_param('i', $payload['owner_id']);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    $dogs = [];
                    while ($row = $res->fetch_assoc()) { $dogs[] = $row; }
                    $response = ['status'=>'success','dogs'=>$dogs];
                } else {
                    $response['message'] = 'Failed to fetch dogs: ' . $conn->error;
                    echo " [-] Failed to fetch dogs: {$conn->error}\n";
                }
                break;

            default:
                $response['message'] = "Unsupported action type";
                echo " [?] Unknown message type\\n";
                break;
        }

        if ($msg->has('reply_to')) {
            $responseMsg = new AMQPMessage(
                json_encode($response),
                ['correlation_id' => $msg->get('correlation_id')]
            );
            $msg->getChannel()->basic_publish($responseMsg, '', $msg->get('reply_to'));
        }


        $msg->ack();
    } catch (Exception $e) {
        error_log("Error processing message: " . $e->getMessage());
        echo " [!] Error: " . $e->getMessage() . "\\n";
    }
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('user_request_queue', '', false, false, false, false, $callback);

try {
    while ($channel->is_consuming()) {
        $channel->wait();
    }
} catch (Exception $e) {
    echo "Channel error: " . $e->getMessage() . "\n";
    $channel->close();
    $connection->close();
    exit(1);
}

$channel->close();
$connection->close();
