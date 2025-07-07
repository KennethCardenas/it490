<?php
include_once '../includes/mq_client.php';
include_once '../header.php';

// Fetch sitters from MQ
$payload = [ "type" => "get_sitters" ];
$response = sendMessage($payload);
$sitters = $response['sitters'] ?? [];
?>

<h2 style="text-align:center; color:#4CAF50;">Dog Sitters</h2>

<div class="container mt-4">
    <?php foreach ($sitters as $sitter): ?>
        <div class="sitter-card">
            <h3><?= htmlspecialchars($sitter['username']) ?></h3>
            <p><strong>Rate:</strong> $<?= htmlspecialchars($sitter['rate']) ?>/hr</p>
            <p><strong>Availability:</strong> <?= htmlspecialchars($sitter['availability']) ?></p>
            <p><strong>Bio:</strong> <?= htmlspecialchars($sitter['bio']) ?></p>

            <?php if (!empty($sitter['dogs'])): ?>
                <h4>Active Dogs:</h4>
                <ul>
                    <?php foreach ($sitter['dogs'] as $dog): ?>
                        <li><?= htmlspecialchars($dog['name']) ?> (<?= htmlspecialchars($dog['breed']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($sitter['logs'])): ?>
                <h4>Recent Activity Logs:</h4>
                <ul>
                    <?php foreach ($sitter['logs'] as $log): ?>
                        <li>[<?= htmlspecialchars($log['date']) ?>] <?= htmlspecialchars($log['entry']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<style>
.sitter-card {
    margin: 20px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    max-width: 600px;
}
</style>

<?php include_once '../footer.php'; ?>