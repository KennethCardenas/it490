<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
include_once __DIR__ . '/../includes/mq_client.php';
$dogId = intval($_GET['dog_id'] ?? 0);
if (!$dogId) { die('Dog not specified'); }

// Add water entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'type' => 'add_water',
        'dog_id' => $dogId,
        'user_id' => $user['id'],
        'amount' => intval($_POST['amount']),
        'notes' => trim($_POST['notes'])
    ];
    $waterResp = sendMessage($payload);
}

// Get water entries
$waterEntries = [];
$resp = sendMessage(['type' => 'get_water', 'dog_id' => $dogId]);
if ($resp['status'] === 'success') { $waterEntries = $resp['entries']; }
?>
<?php
    $title = "Water Tracking";
    include_once __DIR__ . '/../header.php';
?>
<div class="water-container">
    <h2>Water Tracking for Dog #<?= $dogId ?></h2>
    
    <?php if (!empty($waterResp['message'])): ?>
        <p><?= htmlspecialchars($waterResp['message']) ?></p>
    <?php endif; ?>
    
    <div class="water-entries">
        <h3>Recent Water Entries</h3>
        <table>
            <tr>
                <th>Amount (ml)</th>
                <th>Time</th>
                <th>Notes</th>
            </tr>
            <?php foreach ($waterEntries as $entry): ?>
                <tr>
                    <td><?= htmlspecialchars($entry['amount_ml']) ?></td>
                    <td><?= htmlspecialchars($entry['timestamp']) ?></td>
                    <td><?= htmlspecialchars($entry['notes']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="add-water">
        <h3>Add Water Entry</h3>
        <form method="POST">
            <div class="form-group">
                <label for="amount">Amount (ml)</label>
                <input type="number" id="amount" name="amount" required min="1">
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" placeholder="Optional notes about this entry"></textarea>
            </div>
            <button type="submit">Add Entry</button>
        </form>
    </div>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>