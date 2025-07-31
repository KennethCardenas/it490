<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
include_once __DIR__ . '/../includes/mq_client.php';

$user = $_SESSION['user'];
$resp = sendMessage(['type' => 'get_points', 'user_id' => $user['id']]);
$points = ($resp['status'] === 'success') ? $resp['points'] : 0;
?>
<?php $title = "Gamification"; include_once __DIR__ . '/../header.php'; ?>
<div class="points-container">
    <h2>Your Points</h2>
    <p><?= $points ?></p>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
