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

function awardAchievement(mysqli $conn, int $userId, string $code): void {
    $stmt = $conn->prepare("SELECT id FROM ACHIEVEMENTS WHERE code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $ach = $stmt->get_result()->fetch_assoc();
    if (!$ach) {
        return; // achievement not defined
    }
    $aid = $ach['id'];
    $stmt = $conn->prepare("INSERT IGNORE INTO USER_ACHIEVEMENTS (user_id, achievement_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $aid);
    $stmt->execute();
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
                $query = "SELECT id, username, email, password FROM USERS WHERE {$credential['field']} = ?";
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
                                'email' => $user['email']
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
                    $response = [
                        'status' => 'success',
                        'message' => 'Profile updated',
                        'user' => [
                            'id' => $payload['user_id'],
                            'username' => $payload['username'],
                            'email' => $payload['email']
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
                            $stmt = $conn->prepare("SELECT completed, user_id FROM DOG_TASKS WHERE id = ?");
                            $stmt->bind_param("i", $payload['task_id']);
                            $stmt->execute();
                            $row = $stmt->get_result()->fetch_assoc();
                            $completed = (int)($row['completed'] ?? 0);
                            $userForPoints = (int)($row['user_id'] ?? 0);

                            $stmt = $conn->prepare("UPDATE DOG_TASKS SET completed = NOT completed WHERE id = ?");
                            $stmt->bind_param("i", $payload['task_id']);
                            $stmt->execute();
                            $response = ['status' => 'success'];

                            if ($completed === 0 && $userForPoints) {
                                $stmt = $conn->prepare("INSERT INTO USER_POINTS (user_id, points) VALUES (?, 10) ON DUPLICATE KEY UPDATE points = points + 10");
                                $stmt->bind_param("i", $userForPoints);
                                $stmt->execute();
                                awardAchievement($conn, $userForPoints, 'first_task_complete');
                            }
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

                case 'add_care_log':
                    $stmt = $conn->prepare("INSERT INTO CARE_LOGS (dog_id, user_id, note) VALUES (?, ?, ?)");
                    $stmt->bind_param("iis", $payload['dog_id'], $payload['user_id'], $payload['note']);
                    if ($stmt->execute()) {
                        $response = ['status' => 'success', 'message' => 'Care log added'];
                        $stmt = $conn->prepare("INSERT INTO USER_POINTS (user_id, points) VALUES (?, 5) ON DUPLICATE KEY UPDATE points = points + 5");
                        $stmt->bind_param("i", $payload['user_id']);
                        $stmt->execute();
                        awardAchievement($conn, $payload['user_id'], 'first_care_log');
                    } else {
                        $response['message'] = 'Failed to add care log: ' . $conn->error;
                    }
                    break;

                case 'get_care_logs':
                    $stmt = $conn->prepare("SELECT * FROM CARE_LOGS WHERE dog_id = ? ORDER BY created_at DESC");
                    $stmt->bind_param("i", $payload['dog_id']);
                    $stmt->execute();
                    $careLogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $response = ['status' => 'success', 'logs' => $careLogs];
                    break;

                case 'schedule_medication':
                    $stmt = $conn->prepare("INSERT INTO MEDICATION_SCHEDULES (dog_id, user_id, medication, dosage, schedule_time, notes) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissss", $payload['dog_id'], $payload['user_id'], $payload['medication'], $payload['dosage'], $payload['schedule_time'], $payload['notes']);
                    if ($stmt->execute()) {
                        $response = ['status' => 'success', 'message' => 'Medication scheduled'];
                        $stmt = $conn->prepare("INSERT INTO USER_POINTS (user_id, points) VALUES (?, 5) ON DUPLICATE KEY UPDATE points = points + 5");
                        $stmt->bind_param("i", $payload['user_id']);
                        $stmt->execute();
                        awardAchievement($conn, $payload['user_id'], 'first_med_schedule');
                    } else {
                        $response['message'] = 'Failed to schedule medication: ' . $conn->error;
                    }
                    break;

                case 'complete_medication':
                    $stmt = $conn->prepare("UPDATE MEDICATION_SCHEDULES SET completed = 1 WHERE id = ?");
                    $stmt->bind_param("i", $payload['med_id']);
                    $stmt->execute();
                    $response = ['status' => 'success'];
                    if ($stmt->affected_rows > 0) {
                        $stmt = $conn->prepare("SELECT user_id FROM MEDICATION_SCHEDULES WHERE id = ?");
                        $stmt->bind_param("i", $payload['med_id']);
                        $stmt->execute();
                        $uid = $stmt->get_result()->fetch_assoc()['user_id'] ?? 0;
                        if ($uid) {
                            $stmt = $conn->prepare("INSERT INTO USER_POINTS (user_id, points) VALUES (?, 5) ON DUPLICATE KEY UPDATE points = points + 5");
                            $stmt->bind_param("i", $uid);
                            $stmt->execute();
                        }
                    }
                    break;

                case 'get_medications':
                    $stmt = $conn->prepare("SELECT * FROM MEDICATION_SCHEDULES WHERE dog_id = ? ORDER BY schedule_time");
                    $stmt->bind_param("i", $payload['dog_id']);
                    $stmt->execute();
                    $meds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $response = ['status' => 'success', 'medications' => $meds];
                    break;

                case 'add_behavior':
                    $stmt = $conn->prepare("INSERT INTO BEHAVIOR_LOGS (dog_id, user_id, behavior, notes) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $payload['dog_id'], $payload['user_id'], $payload['behavior'], $payload['notes']);
                    if ($stmt->execute()) {
                        $response = ['status' => 'success', 'message' => 'Behavior entry added'];
                        $stmt = $conn->prepare("INSERT INTO USER_POINTS (user_id, points) VALUES (?, 5) ON DUPLICATE KEY UPDATE points = points + 5");
                        $stmt->bind_param("i", $payload['user_id']);
                        $stmt->execute();
                        awardAchievement($conn, $payload['user_id'], 'first_behavior_log');
                    } else {
                        $response['message'] = 'Failed to add behavior entry: ' . $conn->error;
                    }
                    break;

                case 'get_behaviors':
                    $stmt = $conn->prepare("SELECT * FROM BEHAVIOR_LOGS WHERE dog_id = ? ORDER BY created_at DESC");
                    $stmt->bind_param("i", $payload['dog_id']);
                    $stmt->execute();
                    $behaviors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $response = ['status' => 'success', 'behaviors' => $behaviors];
                    break;

                case 'get_points':
                    $stmt = $conn->prepare("SELECT points FROM USER_POINTS WHERE user_id = ?");
                    $stmt->bind_param("i", $payload['user_id']);
                    $stmt->execute();
                    $points = $stmt->get_result()->fetch_assoc()['points'] ?? 0;
                    $response = ['status' => 'success', 'points' => (int)$points];
                    break;

                case 'get_achievements':
                    $stmt = $conn->prepare("SELECT A.code, A.name, A.description, A.badge_img, UA.earned_at FROM USER_ACHIEVEMENTS UA JOIN ACHIEVEMENTS A ON UA.achievement_id = A.id WHERE UA.user_id = ?");
                    $stmt->bind_param("i", $payload['user_id']);
                    $stmt->execute();
                    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $response = ['status' => 'success', 'achievements' => $rows];
                    break;
                
                case 'get_sitters':
                    $stmt = $conn->prepare("SELECT * FROM SITTERS ORDER BY ")

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
