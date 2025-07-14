<?php
include_once '../header.php';
include_once '../includes/mq_client.php';

$sitter_id = $_GET['id'] ?? null;
if (!$sitter_id) {
    echo "<p>No sitter selected.</p>";
    include_once '../footer.php';
    exit();
}

// Build payload for MQ
$payload = [
    'type' => 'get_sitter_profile',
    'sitter_id' => (int)$sitter_id
];

// Send message to RabbitMQ
$response = sendMessage($payload);

// Check response
if (!isset($response['status']) || $response['status'] !== 'success') {
    echo "<p>Error: " . htmlspecialchars($response['message'] ?? 'Unknown error') . "</p>";
    include_once '../footer.php';
    exit();
}

// Extract data from response
$sitter = $response['sitter'];
$dogs = $response['dogs'];
$logs = $response['logs'];
?>

<h2 style="text-align:center; color:#4CAF50;">Sitter Profile: <?= htmlspecialchars($sitter['username']) ?></h2>

<div class="profile-container">
    <p><strong>Username:</strong> <?= htmlspecialchars($sitter['username']) ?></p>
    <p><strong>Rate:</strong> $<?= htmlspecialchars($sitter['rate']) ?>/hr</p>
    <p><strong>Experience:</strong> <?= htmlspecialchars($sitter['experience_years']) ?> years</p>
    <p><strong>Bio:</strong> <?= htmlspecialchars($sitter['bio']) ?></p>

    <h3>Assigned Dogs</h3>
    <?php if (!empty($dogs)): ?>
        <ul>
            <?php foreach ($dogs as $dog): ?>
                <li><?= htmlspecialchars($dog['name']) ?> – <?= htmlspecialchars($dog['breed']) ?> – Age <?= htmlspecialchars($dog['age']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No dogs currently assigned.</p>
    <?php endif; ?>

    <h3>Activity Log</h3>
    <?php if (!empty($logs)): ?>
        <ul>
            <?php foreach ($logs as $log): ?>
                <li><?= htmlspecialchars($log['date']) ?> – <?= htmlspecialchars($log['entry']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No recent activity logs.</p>
    <?php endif; ?>
</div>

<style>
.profile-container {
    background-color: #fff;
    border-radius: 10px;
    padding: 20px;
    max-width: 600px;
    margin: 20px auto;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
</style>

<?php include_once '../footer.php'; ?>