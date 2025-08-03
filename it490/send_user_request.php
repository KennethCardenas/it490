<?php
// send_user_request.php
include_once __DIR__ . '/auth.php';
requireAuth();
$user = $_SESSION['user'];
include_once __DIR__ . '/includes/mq_client.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_GET['type'] ?? '';
    $redirect = '/it490/pages/playdates.php';

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
    } elseif ($type === 'playdate_requests_update') {
        $resp = sendMessage([
            'type' => 'playdate_requests_update',
            'id' => $_POST['id'],
            'status' => $_POST['status']
        ]);

        if ($resp['status'] === 'success') {
            $_SESSION['message'] = 'Request updated';
        } else {
            $_SESSION['message'] = $resp['message'] ?? 'Failed to update request';
        }

        $redirect = '/it490/pages/playdate_request.php';
    } elseif ($type === 'lost_dogs_update') {
        $resp = sendMessage([
            'type' => 'lost_dogs_update',
            'id'   => $_POST['id'],
            'status' => $_POST['status']
        ]);

        if ($resp['status'] === 'success') {
            $_SESSION['message'] = 'Alert updated';
        } else {
            $_SESSION['message'] = $resp['message'] ?? 'Failed to update alert';
        }

        $redirect = '/it490/pages/lost_dogs.php';
    } else {
        $_SESSION['message'] = 'Unsupported action type';
    }

    header("Location: {$redirect}");
    exit();
}

header('Location: /it490/pages/playdates.php');
exit();
?>
