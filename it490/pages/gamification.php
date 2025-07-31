<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
include_once __DIR__ . '/../includes/mq_client.php';

$user = $_SESSION['user'];
$resp = sendMessage(['type' => 'get_points', 'user_id' => $user['id']]);
$points = ($resp['status'] === 'success') ? $resp['points'] : 0;
$achResp = sendMessage(['type' => 'get_achievements', 'user_id' => $user['id']]);
$achievements = ($achResp['status'] === 'success') ? $achResp['achievements'] : [];
?>
<?php $title = "Gamification"; include_once __DIR__ . '/../header.php'; ?>
<div class="points-container">
    <h2>Your Points</h2>
    <p><?= $points ?></p>
</div>
<div class="achievements-container">
    <h3>Your Badges</h3>
    <ul class="badge-list">
        <?php foreach ($achievements as $a): ?>
            <li class="badge-item">
                <img src="<?= htmlspecialchars($a['badge_img']) ?>" alt="<?= htmlspecialchars($a['name']) ?>" width="80" height="80">
                <div>
                    <strong><?= htmlspecialchars($a['name']) ?></strong><br>
                    <span><?= htmlspecialchars($a['description']) ?></span>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
