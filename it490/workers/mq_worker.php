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
    $connection = new AMQPStreamConnection(
        '100.87.203.113', 
        5672, 
        'kac63', 
        'Linklinkm1!',
        '/',
        false,
        'AMQPLAIN',
        null,
        'en_US',
        30.0,  // connection_timeout
        30.0,  // read_write_timeout
        null,
        false,
        30     // heartbeat
    );
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

        $type = isset($payload['type']) ? trim($payload['type']) : '';
        echo " [x] Processing: " . ($type ?: 'unknown') . "\n";

        switch ($type) {
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
                $stmt = $conn->prepare("INSERT INTO DOGS (owner_id, name, breed, age, notes, care_instructions, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $age = $payload['age'] ?? null;
                $notes = $payload['notes'] ?? '';
                $care = $payload['care_instructions'] ?? '';
                $stmt->bind_param('ississ', $payload['owner_id'], $payload['name'], $payload['breed'], $age, $notes, $care);
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

            case 'get_dog':
                $stmt = $conn->prepare("SELECT id, owner_id, name, breed, age, notes, care_instructions FROM DOGS WHERE id = ?");
                $stmt->bind_param('i', $payload['dog_id']);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    $dog = $res->fetch_assoc();
                    $response = ['status'=>'success','dog'=>$dog];
                } else {
                    $response['message'] = 'Failed to fetch dog: ' . $conn->error;
                    echo " [-] Failed to fetch dog: {$conn->error}\n";
                }
                break;

            case 'update_dog':
                $stmt = $conn->prepare("UPDATE DOGS SET name=?, breed=?, age=?, notes=?, care_instructions=? WHERE id=? AND owner_id=?");
                $age = $payload['age'] ?? null;
                $notes = $payload['notes'] ?? '';
                $care = $payload['care_instructions'] ?? '';
                $stmt->bind_param('ssissii', $payload['name'], $payload['breed'], $age, $notes, $care, $payload['dog_id'], $payload['owner_id']);
                if ($stmt->execute()) {
                    $response = ['status'=>'success'];
                    echo " [+] Updated dog {$payload['dog_id']}\n";
                } else {
                    $response['message'] = 'Failed to update dog: ' . $conn->error;
                    echo " [-] Failed to update dog: {$conn->error}\n";
                }
                break;

            case 'get_sitter_profile':
                $stmt = $conn->prepare("SELECT id, user_id, bio, experience_years, rating FROM SITTERS WHERE user_id=?");
                $stmt->bind_param('i', $payload['user_id']);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    $profile = $res->fetch_assoc();
                    $response = ['status'=>'success','profile'=>$profile];
                } else {
                    $response['message'] = 'Failed to fetch sitter profile: ' . $conn->error;
                    echo " [-] Failed to fetch sitter profile: {$conn->error}\n";
                }
                break;
            case 'log_event':
                $stmt = $conn->prepare("INSERT INTO logs (user_id, type, message) VALUES (?, ?, ?)");
                $userId = $payload['user_id'] ?? null;
                $logType = $payload['log_type'] ?? 'general';
                $logMessage = $payload['message'] ?? 'No message provided';
                $stmt->bind_param('iss', $userId, $logType, $logMessage);
                if ($stmt->execute()) {
                    $response = ['status' => 'success', 'message' => 'Log recorded'];
                    echo " [+] Logged event: $logType - $logMessage\n";
                } else {
                $response['message'] = 'Failed to log event: ' . $conn->error;
                echo " [-] Logging failed: {$conn->error}\n";
                }
                break;              








            case 'update_sitter_profile':
                $bio = $payload['bio'] ?? '';
                $exp = $payload['experience_years'] ?? 0;
                $check = $conn->prepare("SELECT id FROM SITTERS WHERE user_id=?");
                $check->bind_param('i', $payload['user_id']);
                $check->execute();
                $res = $check->get_result();
                if ($res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $stmt = $conn->prepare("UPDATE SITTERS SET bio=?, experience_years=? WHERE id=?");
                    $stmt->bind_param('sii', $bio, $exp, $row['id']);
                } else {
                    $stmt = $conn->prepare("INSERT INTO SITTERS (user_id, bio, experience_years) VALUES (?, ?, ?)");
                    $stmt->bind_param('isi', $payload['user_id'], $bio, $exp);
                }
                if ($stmt->execute()) {
                    $response = ['status'=>'success'];
                    echo " [+] Sitter profile saved for user {$payload['user_id']}\n";
                } else {
                    $response['message'] = 'Failed to save sitter profile: ' . $conn->error;
                    echo " [-] Failed to save sitter profile: {$conn->error}\n";
                }
                break;

            case 'list_sitters':
                $query = "SELECT SITTERS.id, USERS.username, SITTERS.bio, SITTERS.experience_years, SITTERS.rating FROM SITTERS JOIN USERS ON SITTERS.user_id = USERS.id";
                $res = $conn->query($query);
                $sitters = [];
                if ($res) {
                    while ($row = $res->fetch_assoc()) { $sitters[] = $row; }
                    $response = ['status'=>'success','sitters'=>$sitters];
                } else {
                    $response['message'] = 'Failed to list sitters: ' . $conn->error;
                    echo " [-] Failed to list sitters: {$conn->error}\n";
                }
                break;

            case 'grant_dog_access':
    // Validate ownership of dog before granting access
                $check = $conn->prepare("SELECT id FROM DOGS WHERE id=? AND owner_id=?");
                $check->bind_param('ii', $payload['dog_id'], $payload['owner_id']);
                $check->execute();
                $res = $check->get_result();

                if ($res && $res->num_rows === 1) {
                    $level = $payload['access_level'] ?? 'viewer';
                    $start = $payload['start_date'] ?? null;
                    $end = $payload['end_date'] ?? null;

                    $stmt = $conn->prepare("INSERT INTO DOG_ACCESS (dog_id, sitter_id, access_level, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param('iisss', $payload['dog_id'], $payload['sitter_id'], $level, $start, $end);

                    if ($stmt->execute()) {
                        $response = ['status' => 'success'];
                        echo " [+] Granted access to sitter {$payload['sitter_id']} for dog {$payload['dog_id']}\n";
                    } else {
                        $response['message'] = 'Failed to grant access: ' . $conn->error;
                        echo " [-] Failed to grant access: {$conn->error}\n";
                    }
                } else {
                    $response['message'] = 'Dog not found or permission denied';
                    echo " [-] Grant access denied: invalid owner for dog {$payload['dog_id']}\n";
                }
                break;

                

            case 'list_active_dogs':
                $stmt = $conn->prepare("SELECT D.id, D.name, D.breed, D.age, D.notes, D.care_instructions FROM DOGS D JOIN DOG_ACCESS A ON D.id=A.dog_id WHERE A.sitter_id=? AND (A.start_date IS NULL OR A.start_date <= CURDATE()) AND (A.end_date IS NULL OR A.end_date >= CURDATE())");
                $stmt->bind_param('i', $payload['sitter_id']);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    $dogs = [];
                    while ($row = $res->fetch_assoc()) { $dogs[] = $row; }
                    $response = ['status'=>'success','dogs'=>$dogs];
                } else {
                    $response['message'] = 'Failed to list dogs: ' . $conn->error;
                    echo " [-] Failed to list active dogs: {$conn->error}\n";
                }
                break;

            case 'record_activity':
                $mood = $payload['mood'] ?? null;
                $intensity = $payload['intensity'] ?? null;
                $trigger = $payload['trigger_text'] ?? null;
                $stmt = $conn->prepare("INSERT INTO ACTIVITIES (dog_id, sitter_id, description, mood, intensity, trigger_text, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('isssis', $payload['dog_id'], $payload['sitter_id'], $payload['description'], $mood, $intensity, $trigger);
                if ($stmt->execute()) {
                    $response = ['status'=>'success'];
                    echo " [+] Activity recorded for dog {$payload['dog_id']}\n";
                } else {
                    $response['message'] = 'Failed to record activity: ' . $conn->error;
                    echo " [-] Failed to record activity: {$conn->error}\n";
                }
                break;

            case 'list_activities':
                $stmt = $conn->prepare("SELECT id, sitter_id, description, mood, intensity, trigger_text, created_at FROM ACTIVITIES WHERE dog_id=? ORDER BY created_at DESC");
                $stmt->bind_param('i', $payload['dog_id']);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    $acts = [];
                    while ($row = $res->fetch_assoc()) { $acts[] = $row; }
                    $response = ['status'=>'success','activities'=>$acts];
                } else {
                    $response['message'] = 'Failed to list activities: ' . $conn->error;
                    echo " [-] Failed to list activities: {$conn->error}\n";
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