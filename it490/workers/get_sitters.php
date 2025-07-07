<?php
require_once __DIR__ . '/../api/connect.php';

function getSittersFromDB($conn) {
    $sitters = [];

    // Step 1: Fetch all sitters with their user data
    $query = "SELECT s.id AS sitter_id, u.username, s.rate, s.availability, s.bio 
              FROM SITTERS s 
              JOIN USERS u ON s.user_id = u.id";
    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        $sitterId = $row['sitter_id'];
        $row['dogs'] = [];
        $row['logs'] = [];

        // Step 2: Get dogs for this sitter
        $dogQuery = $conn->prepare(
            "SELECT d.name, d.breed 
             FROM DOGS d 
             JOIN DOG_ACCESS da ON d.id = da.dog_id 
             WHERE da.sitter_id = ?"
        );
        $dogQuery->bind_param("i", $sitterId);
        $dogQuery->execute();
        $dogResult = $dogQuery->get_result();
        while ($dog = $dogResult->fetch_assoc()) {
            $row['dogs'][] = $dog;
        }
        $dogQuery->close();

        // Step 3: Get recent logs for this sitter
        $logQuery = $conn->prepare(
            "SELECT date, entry 
             FROM DOG_LOGS 
             WHERE sitter_id = ? 
             ORDER BY date DESC 
             LIMIT 3"
        );
        $logQuery->bind_param("i", $sitterId);
        $logQuery->execute();
        $logResult = $logQuery->get_result();
        while ($log = $logResult->fetch_assoc()) {
            $row['logs'][] = $log;
        }
        $logQuery->close();

        $sitters[] = $row;
    }

    return $sitters;
}

$payload = json_decode(file_get_contents("php://stdin"), true);
$response = ['status' => 'error', 'message' => 'Unknown request'];

if ($payload['type'] === 'get_sitters') {
    $response = [
        'status' => 'success',
        'sitters' => getSittersFromDB($conn)
    ];
}

echo json_encode($response);
?>