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
        "SELECT id, dog_name, description, last_seen_location, contact_info, reported_by, reported_at
         FROM lost_dogs
         ORDER BY reported_at DESC");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));

} elseif ($method === 'POST') {
    $stmt = $conn->prepare(
        "INSERT INTO lost_dogs (dog_name, description, last_seen_location, contact_info, reported_by)
         VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param(
        'ssssi',
        $data['dog_name'],
        $data['description'],
        $data['last_seen_location'],
        $data['contact_info'],
        $user_id
    );
    $stmt->execute();
    http_response_code(201);
    echo json_encode(['message' => 'Lost dog report submitted']);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
?>