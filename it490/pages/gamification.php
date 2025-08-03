<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
include_once __DIR__ . '/../includes/mq_client.php';
require_once __DIR__ . '/../api/connect.php';

function getPointsFromDatabase($conn, $userId) {
    $stmt = $conn->prepare("SELECT points FROM USER_POINTS WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return (int)($stmt->get_result()->fetch_assoc()['points'] ?? 0);
}

function getAchievementsFromDatabase($conn, $userId) {
    $stmt = $conn->prepare("SELECT A.code, A.name, A.description, A.badge_img, UA.earned_at FROM USER_ACHIEVEMENTS UA JOIN ACHIEVEMENTS A ON UA.achievement_id = A.id WHERE UA.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$user = $_SESSION['user'];
$resp = @sendMessage(['type' => 'get_points', 'user_id' => $user['id']]);
if (($resp['status'] ?? '') === 'success') {
    $points = $resp['points'];
} else {
    $points = getPointsFromDatabase($conn, $user['id']);
}
$achResp = @sendMessage(['type' => 'get_achievements', 'user_id' => $user['id']]);
if (($achResp['status'] ?? '') === 'success') {
    $achievements = $achResp['achievements'] ?? [];
} else {
    $achievements = getAchievementsFromDatabase($conn, $user['id']);
}
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
