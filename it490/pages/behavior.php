<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
include_once __DIR__ . '/../includes/mq_client.php';

$dogId = intval($_GET['dog_id'] ?? 0);
if (!$dogId) { die('Dog not specified'); }

$behResp = [];
$msg = trim($_GET['msg'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'type' => 'add_behavior',
        'dog_id' => $dogId,
        'user_id' => $user['id'],
        'behavior' => trim($_POST['behavior']),
        'notes' => trim($_POST['notes'])
    ];
    $behResp = sendMessage($payload);
    $redirectMsg = urlencode($behResp['message'] ?? '');
    header("Location: behavior.php?dog_id={$dogId}&msg={$redirectMsg}");
    exit();
}

if ($msg) { $behResp['message'] = $msg; }

$entries = [];
$resp = sendMessage(['type' => 'get_behaviors', 'dog_id' => $dogId]);
if ($resp['status'] === 'success') { $entries = $resp['behaviors']; }
?>
<?php $title = "Behavior"; include_once __DIR__ . '/../header.php'; ?>
<div class="behavior-container">
    <h2>Behavior Logs for Dog #<?= $dogId ?></h2>
    <?php if (!empty($behResp['message'])): ?>
        <p><?= htmlspecialchars($behResp['message']) ?></p>
    <?php endif; ?>
    <table>
        <tr><th>Behavior</th><th>Notes</th><th>Time</th></tr>
        <?php foreach ($entries as $e): ?>
            <tr>
                <td><?= htmlspecialchars($e['behavior']) ?></td>
                <td><?= htmlspecialchars($e['notes']) ?></td>
                <td><?= htmlspecialchars($e['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h3>Add Entry</h3>
    <form method="POST">
        <input type="text" name="behavior" placeholder="Behavior" required>
        <textarea name="notes" placeholder="Notes"></textarea>
        <button type="submit">Add</button>
    </form>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
