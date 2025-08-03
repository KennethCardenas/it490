<?php
// send_user_request.php
include_once __DIR__ . '/auth.php';
requireAuth();
$user = $_SESSION['user'];
include_once __DIR__ . '/includes/mq_client.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_GET['type'] ?? '';
    
    if ($type === 'playdate_request') {
        $resp = sendMessage([
            'type' => 'playdate_request',
            'user_id' => $user['id'],
            'target_owner_id' => $_POST['target_owner_id'],
            'dog_size_match' => $_POST['dog_size_match'],
            'location_preference' => $_POST['location_preference'],
            'custom_message' => $_POST['custom_message'] ?? ''
        ]);
        
        if ($resp['status'] === 'success') {
            $_SESSION['message'] = 'Playdate request sent successfully!';
        } else {
            $_SESSION['message'] = $resp['message'] ?? 'Failed to send request';
        }
    }
}

header('Location: /pages/playdates.php');
exit();
?>
