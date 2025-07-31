<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
include_once __DIR__ . '/../includes/mq_client.php';

$dogId = intval($_GET['dog_id'] ?? 0);
if (!$dogId) {
    die('Dog not specified');
}

$careResp = [];
$msg = trim($_GET['msg'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'type' => 'add_care_log',
        'dog_id' => $dogId,
        'user_id' => $user['id'],
        'note' => trim($_POST['note'])
    ];
    $careResp = sendMessage($payload);
    $redirectMsg = urlencode($careResp['message'] ?? '');
    header("Location: care.php?dog_id={$dogId}&msg={$redirectMsg}");
    exit();
}

if ($msg) {
    $careResp['message'] = $msg;
}

$logs = [];
$resp = sendMessage(['type' => 'get_care_logs', 'dog_id' => $dogId]);
if ($resp['status'] === 'success') {
    $logs = $resp['logs'];
}
?>
<?php
$title = "Care Logs";
include_once __DIR__ . '/../header.php';
?>
<div class="care-container">
    <h2>Care Logs for Dog #<?= $dogId ?></h2>
    <?php if (!empty($careResp['message'])): ?>
        <p><?= htmlspecialchars($careResp['message']) ?></p>
    <?php endif; ?>
    <table>
        <tr><th>Note</th><th>Time</th></tr>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td><?= htmlspecialchars($l['note']) ?></td>
                <td><?= htmlspecialchars($l['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h3>Add Log</h3>
    <form method="POST">
        <textarea name="note" required></textarea>
        <button type="submit">Add</button>
    </form>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
