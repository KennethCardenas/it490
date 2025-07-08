<?php
require_once __DIR__ . '/../api/connect.php';

function handle_get_sitters($payload) {
    global $conn;

    $sitters = [];

    // Step 1: Get sitters with their user info
    $sql = "SELECT s.id AS sitter_id, u.username, s.bio, s.rate, s.experience_years, s.user_id
            FROM SITTERS s
            JOIN USERS u ON s.user_id = u.id";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sitterId = $row['sitter_id'];
            $sitters[$sitterId] = [
                'username' => $row['username'],
                'bio' => $row['bio'],
                'rate' => $row['rate'],
                'experience_years' => $row['experience_years'],
                'availability' => 'Unknown', // Default unless you store this somewhere
                'dogs' => [],
                'logs' => []
            ];
        }
    }

    // Step 2: Get dogs assigned to each sitter
    $dogSql = "SELECT da.sitter_id, d.name, d.breed
               FROM DOG_ACCESS da
               JOIN DOGS d ON da.dog_id = d.id";
    $dogResult = $conn->query($dogSql);

    if ($dogResult && $dogResult->num_rows > 0) {
        while ($row = $dogResult->fetch_assoc()) {
            $sitterId = $row['sitter_id'];
            if (isset($sitters[$sitterId])) {
                $sitters[$sitterId]['dogs'][] = [
                    'name' => $row['name'],
                    'breed' => $row['breed']
                ];
            }
        }
    }

    // Step 3: Get logs for each sitter's dogs
    $logSql = "SELECT da.sitter_id, dl.entry, dl.created_at
               FROM DOG_ACCESS da
               JOIN DOG_LOGS dl ON da.dog_id = dl.dog_id";
    $logResult = $conn->query($logSql);

    if ($logResult && $logResult->num_rows > 0) {
        while ($row = $logResult->fetch_assoc()) {
            $sitterId = $row['sitter_id'];
            if (isset($sitters[$sitterId])) {
                $sitters[$sitterId]['logs'][] = [
                    'entry' => $row['entry'],
                    'date' => date('Y-m-d', strtotime($row['created_at']))
                ];
            }
        }
    }

    return ['status' => 'success', 'sitters' => array_values($sitters)];
}
?>