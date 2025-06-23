<?php
header('Content-Type: application/json');
require 'connect.php';

$sql = "
SELECT 
    l.ID, u.USERNAME, l.LOG_TYPE, l.MESSAGE, l.CREATED_AT
FROM 
    LOGS l
JOIN 
    USERS u ON l.USER_ID = u.ID
ORDER BY l.CREATED_AT DESC
";

$result = $conn->query($sql);
$logs = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

echo json_encode($logs);
?>
