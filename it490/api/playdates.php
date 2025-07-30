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
$data = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET') {
    $result = $conn->query(
        "SELECT id, title, description, scheduled_at, location, created_by, created_at
         FROM playdates
         ORDER BY scheduled_at DESC");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));

} elseif ($method === 'POST') {
    $stmt = $conn->prepare(
        "INSERT INTO playdates (title, description, scheduled_at, location, created_by)
         VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param(
        'ssssi',
        $data['title'],
        $data['description'],
        $data['scheduled_at'],
        $data['location'],
        $user_id
    );
    $stmt->execute();
    http_response_code(201);
    echo json_encode(['message' => 'Playdate created']);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
?>