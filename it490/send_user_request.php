<?php
// send_user_request.php
include_once __DIR__ . '/auth.php';
requireAuth();
$user = $_SESSION['user'];
include_once __DIR__ . '/includes/mq_client.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_GET['type'] ?? '';
    $redirect = '/pages/playdates.php';

    if ($type === 'playdate_request') {
        $resp = sendMessage([
            'type' => 'playdate_request',
            'user_id' => $user['id'],
            'playdate_id' => $_POST['playdate_id'],
            'recipient_id' => $_POST['recipient_id'],
            'dog_size_match' => $_POST['dog_size_match'],
            'location_preference' => $_POST['location_preference'],
            'custom_message' => $_POST['custom_message'] ?? ''
        ]);

        if ($resp['status'] === 'success') {
            $_SESSION['message'] = 'Playdate request sent successfully!';
        } else {
            $_SESSION['message'] = $resp['message'] ?? 'Failed to send request';
        }
    } elseif ($type === 'playdate_requests_update') {
        $redirect = '/pages/playdate_request.php';
        $resp = sendMessage([
            'type' => 'playdate_requests_update',
            'id' => $_POST['id'],
            'status' => $_POST['status']
        ]);

        if ($resp['status'] === 'success') {
            $_SESSION['message'] = 'Request updated successfully!';
        } else {
            $_SESSION['message'] = $resp['message'] ?? 'Failed to update request';
        }
    }

    header('Location: ' . $redirect);
    exit();
}

header('Location: /pages/playdates.php');
exit();
?>
