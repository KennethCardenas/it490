<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api/connect.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function hashPassword(string $p): string {
    return password_hash($p, PASSWORD_BCRYPT, ['cost' => 12]);
}
function verifyPassword(string $p, string $h): bool { return password_verify($p,$h); }

function validateEmailOrUsername(string $input): array {
    if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
        return ['field' => 'email', 'value' => $input];
    }
    return ['field' => 'username', 'value' => $input];
}

function checkDuplicates($conn, $username, $email, $exclude = null): array {
    $errors = [];
    $q = "SELECT id FROM USERS WHERE username = ?" . ($exclude ? " AND id != ?" : "");
    $stmt = $conn->prepare($q);
    if ($exclude) { $stmt->bind_param('si',$username,$exclude); } else { $stmt->bind_param('s',$username); }
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) $errors[]='Username already exists';

    $q = "SELECT id FROM USERS WHERE email = ?" . ($exclude ? " AND id != ?" : "");
    $stmt = $conn->prepare($q);
    if ($exclude) { $stmt->bind_param('si',$email,$exclude); } else { $stmt->bind_param('s',$email); }
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) $errors[]='Email already exists';

    return $errors;
}

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('user_actions_queue', false, true, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function($msg) use ($channel, $conn) {
    $payload = json_decode($msg->body, true) ?? [];
    $response = ['status'=>'error','message'=>'Unsupported action'];
    switch ($payload['type'] ?? '') {
        case 'login':
            $cred = validateEmailOrUsername($payload['username']);
            $stmt = $conn->prepare("SELECT id, username, email, password FROM USERS WHERE {$cred['field']} = ?");
            $stmt->bind_param('s', $cred['value']);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if (verifyPassword($payload['password'], $row['password'])) {
                    $response = ['status'=>'success','user'=>['id'=>$row['id'],'username'=>$row['username'],'email'=>$row['email']]];
                } else {
                    $response['message'] = 'Invalid credentials';
                }
            } else { $response['message']='User not found'; }
            break;
        case 'register':
            $dup = checkDuplicates($conn,$payload['username'],$payload['email']);
            if ($dup) { $response['message']=implode(',',$dup); break; }
            $hash = hashPassword($payload['password']);
            $stmt = $conn->prepare("INSERT INTO USERS (username,email,password,created_at) VALUES (?,?,?,NOW())");
            $stmt->bind_param('sss',$payload['username'],$payload['email'],$hash);
            if ($stmt->execute()) {
                $response = ['status'=>'success','user'=>['id'=>$stmt->insert_id,'username'=>$payload['username'],'email'=>$payload['email']]];
            } else { $response['message']='Database error'; }
            break;
        case 'update_profile':
            $dup = checkDuplicates($conn,$payload['username'],$payload['email'],$payload['user_id']);
            if ($dup) { $response['message']=implode(',',$dup); break; }
            $query = "UPDATE USERS SET username=?, email=?";
            $params = [$payload['username'],$payload['email']];
            $types = 'ss';
            if (!empty($payload['password'])) { $query .= ", password=?"; $params[] = hashPassword($payload['password']); $types.='s'; }
            $query .= " WHERE id=?"; $params[] = $payload['user_id']; $types.='i';
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $response = ['status'=>'success','user'=>['id'=>$payload['user_id'],'username'=>$payload['username'],'email'=>$payload['email']]];
            } else { $response['message']='Update failed'; }
            break;
        case 'reset_request':
            $response = ['status'=>'success','message'=>'Reset request received'];
            break;
    }
    if ($msg->get('reply_to')) {
        $reply = new AMQPMessage(json_encode($response), ['correlation_id'=>$msg->get('correlation_id')]);
        $channel->basic_publish($reply,'',$msg->get('reply_to'));
    }
    $msg->ack();
};

$channel->basic_qos(null,1,null);
$channel->basic_consume('user_actions_queue','',false,false,false,false,$callback);
while ($channel->is_consuming()) { $channel->wait(); }

