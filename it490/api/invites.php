<?php
header('Content-Type: application/json');
require 'connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->prepare(
        "SELECT id, code, used_by, created_at, expires_at
         FROM invitations
         WHERE inviter_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($res);

} elseif ($method === 'POST') {
    $code = bin2hex(random_bytes(16));
    $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
    $stmt = $conn->prepare(
        "INSERT INTO invitations (code, inviter_id, expires_at)
         VALUES (?, ?, ?)");
    $stmt->bind_param('sis', $code, $user_id, $expires);
    $stmt->execute();
    echo json_encode(['code' => $code, 'expires_at' => $expires]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
?>