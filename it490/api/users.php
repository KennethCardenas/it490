<?php
header('Content-Type: application/json');
require 'connect.php';

$sql = "SELECT * FROM USERS";
$result = $conn->query($sql);

$users = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

echo json_encode($users);
?>
