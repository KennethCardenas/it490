<?php 
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api/connect.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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
                $query = "SELECT id, username, email, password, role FROM USERS WHERE {$credential['field']} = ?";
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
                                'role' => $user['role'] ?? 'owner'
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
                $query = "INSERT INTO USERS (username, email, password, created_at) VALUES (?, ?, ?, NOW())";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sss", $payload['username'], $payload['email'], $hashedPassword);

                if ($stmt->execute()) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Registration successful',
                        'user' => [
                            'id' => $stmt->insert_id,
                            'username' => $payload['username'],
                            'email' => $payload['email'],
                            'role' => 'owner'
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
                    $roleStmt = $conn->prepare("SELECT role FROM USERS WHERE id = ?");
                    $roleStmt->bind_param("i", $payload['user_id']);
                    $roleStmt->execute();
                    $roleRes = $roleStmt->get_result()->fetch_assoc();
                    $roleStmt->close();
                    $response = [
                        'status' => 'success',
                        'message' => 'Profile updated',
                        'user' => [
                            'id' => $payload['user_id'],
                            'username' => $payload['username'],
                            'email' => $payload['email'],
                            'role' => $roleRes['role'] ?? 'owner'
                        ]
                    ];
                    echo " [+] Updated profile for user ID: {$payload['user_id']}\\n";
                } else {
                    $response['message'] = "Update failed: " . $conn->error;
                    echo " [-] Profile update failed: " . $response['message'] . "\\n";
                }
                break;

            case 'verify_user':
                $query = "SELECT id, username, email FROM USERS WHERE username = ? AND email = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $payload['username'], $payload['email']);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $response = [
                        'status' => 'success',
                        'message' => 'User verified successfully',
                        'user' => [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'email' => $user['email']
                        ]
                    ];
                    echo " [+] User verified: {$user['username']}\\n";
                } else {
                    $response['message'] = "User not found or username/email combination is incorrect";
                    echo " [-] User verification failed\\n";
                }
                break;

            case 'password_reset':
                $response['message'] = "Password reset functionality not yet implemented";
                echo " [?] Password reset requested\\n";
                break;

                        case 'create_dog':
                            // match database schema which stores the owner in owner_id
                            $stmt = $conn->prepare("INSERT INTO DOGS (owner_id, name, breed, health_status, notes) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("issss", $payload['user_id'], $payload['name'], $payload['breed'], $payload['health_status'], $payload['notes']);
                            if ($stmt->execute()) {
                                $response = ['status' => 'success', 'message' => 'Dog added', 'dog_id' => $stmt->insert_id];
                                echo " [+] Dog created id {$stmt->insert_id}\n";
                            } else {
                                $response['message'] = 'Failed to create dog: ' . $conn->error;
                                echo " [-] Dog creation failed\n";
                            }
                            break;
            
                        case 'get_dogs':
                            // return all dogs that belong to the user (owner_id)
                            $stmt = $conn->prepare("SELECT * FROM DOGS WHERE owner_id = ?");
                            $stmt->bind_param("i", $payload['user_id']);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $dogs = $res->fetch_all(MYSQLI_ASSOC);
                            $response = ['status' => 'success', 'dogs' => $dogs];
                            break;
            
                        case 'add_task':
                            $stmt = $conn->prepare("INSERT INTO DOG_TASKS (dog_id, user_id, title, description, due_date) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("iisss", $payload['dog_id'], $payload['user_id'], $payload['title'], $payload['description'], $payload['due_date']);
                            if ($stmt->execute()) {
                                $response = ['status' => 'success', 'message' => 'Task added'];
                                echo " [+] Task added for dog {$payload['dog_id']}\n";
                            } else {
                                $response['message'] = 'Failed to add task: ' . $conn->error;
                                echo " [-] Task add failed\n";
                            }
                            break;
            
                        case 'get_tasks':
                            $stmt = $conn->prepare("SELECT * FROM DOG_TASKS WHERE dog_id = ? ORDER BY due_date");
                            $stmt->bind_param("i", $payload['dog_id']);
                            $stmt->execute();
                            $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $response = ['status' => 'success', 'tasks' => $tasks];
                            break;
            
                        case 'toggle_task':
                            $stmt = $conn->prepare("UPDATE DOG_TASKS SET completed = NOT completed WHERE id = ?");
                            $stmt->bind_param("i", $payload['task_id']);
                            $stmt->execute();
                            $response = ['status' => 'success'];
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

                case 'add_water':
                    $stmt = $conn->prepare("INSERT INTO WATER_TRACKING (dog_id, user_id, amount_ml, notes) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiis", $payload['dog_id'], $payload['user_id'], $payload['amount'], $payload['notes']);
                    if ($stmt->execute()) {
                        $response = ['status' => 'success', 'message' => 'Water entry added'];
                        echo " [+] Water entry added for dog {$payload['dog_id']}\n";
                    } else {
                        $response['message'] = 'Failed to add water entry: ' . $conn->error;
                        echo " [-] Water entry add failed\n";
                    }
                    break;
                
                case 'get_water':
                    $stmt = $conn->prepare("SELECT * FROM WATER_TRACKING WHERE dog_id = ? ORDER BY timestamp DESC");
                    $stmt->bind_param("i", $payload['dog_id']);
                    $stmt->execute();
                    $waterEntries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $response = ['status' => 'success', 'entries' => $waterEntries];
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
