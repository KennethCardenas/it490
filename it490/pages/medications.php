<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
include_once __DIR__ . '/../includes/mq_client.php';

$dogId = intval($_GET['dog_id'] ?? 0);
if (!$dogId) { die('Dog not specified'); }

$medResp = [];
$msg = trim($_GET['msg'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'type' => 'schedule_medication',
        'dog_id' => $dogId,
        'user_id' => $user['id'],
        'medication' => trim($_POST['medication']),
        'dosage' => trim($_POST['dosage']),
        'schedule_time' => trim($_POST['schedule_time']),
        'notes' => trim($_POST['notes'])
    ];
    $medResp = sendMessage($payload);
    $redirectMsg = urlencode($medResp['message'] ?? '');
    header("Location: medications.php?dog_id={$dogId}&msg={$redirectMsg}");
    exit();
}

if ($msg) { $medResp['message'] = $msg; }

$meds = [];
$resp = sendMessage(['type' => 'get_medications', 'dog_id' => $dogId]);
if ($resp['status'] === 'success') { $meds = $resp['medications']; }

if (isset($_GET['complete'])) {
    sendMessage(['type' => 'complete_medication', 'med_id' => intval($_GET['complete'])]);
    header('Location: medications.php?dog_id=' . $dogId);
    exit();
}
?>
<?php $title = "Medications"; include_once __DIR__ . '/../header.php'; ?>
<div class="med-container">
    <h2>Medication Schedule for Dog #<?= $dogId ?></h2>
    <?php if (!empty($medResp['message'])): ?>
        <p><?= htmlspecialchars($medResp['message']) ?></p>
    <?php endif; ?>
    <table>
        <tr><th>Medication</th><th>Dosage</th><th>Time</th><th>Status</th><th></th></tr>
        <?php foreach ($meds as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['medication']) ?></td>
                <td><?= htmlspecialchars($m['dosage']) ?></td>
                <td><?= htmlspecialchars($m['schedule_time']) ?></td>
                <td><?= $m['completed'] ? 'Done' : 'Pending' ?></td>
                <td><?php if(!$m['completed']): ?><a href="?dog_id=<?= $dogId ?>&complete=<?= $m['id'] ?>">Mark Done</a><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h3>Schedule Medication</h3>
    <form method="POST">
        <input type="text" name="medication" placeholder="Medication" required>
        <input type="text" name="dosage" placeholder="Dosage">
        <input type="datetime-local" name="schedule_time" required>
        <textarea name="notes" placeholder="Notes"></textarea>
        <button type="submit">Add</button>
    </form>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
