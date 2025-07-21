<?php
// Simple test to check if worker is responding
require_once __DIR__ . '/it490/includes/mq_client.php';

echo "Testing worker response...\n";

try {
    $payload = [
        "type" => "register",
        "username" => "testuser" . time(), // Make it unique
        "email" => "test" . time() . "@example.com", 
        "password" => "testpass"
    ];
    
    echo "Sending test message...\n";
    $response = sendMessage($payload);
    
    echo "Response received: ";
    print_r($response);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 