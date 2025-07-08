<?php
include_once __DIR__ . '/../includes/mq_client_local.php';

$payload = [
    "type" => "login",
    "username" => "JerryS",
    "password" => "pass123"
];

$response = sendMessage($payload);
print_r($response);
?>