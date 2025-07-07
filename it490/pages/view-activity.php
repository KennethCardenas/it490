<?php
include_once '../header.php';
include_once '../includes/mq_client.php';

if (!isset($_SESSION['user']['id'])) {
    echo "<p class='text-danger text-center'>You must be logged in to view this page.</p>";
    include_once '../footer.php';
    exit();
}

$userId = $_SESSION['user']['id'];

// Request activity logs via MQ
$payload = [
    'type' => 'get_activity_logs_by_sitter',
    'user_id' => $userId
];
$response = sendMessage($payload);

$logsByDog = $response['logs'] ?? [];
?>

<div class="container mt-5">
    <h2 class="text-center text-primary mb-4">Activity Log Per Dog</h2>

    <?php if (empty($logsByDog)): ?>
        <p class="text-center text-muted">No activity logs found.</p>
    <?php else: ?>
        <?php foreach ($logsByDog as $dogName => $logList): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title"><?= htmlspecialchars($dogName) ?></h4>
                    <ul>
                        <?php foreach ($logList as $entry): ?>
                            <li><strong><?= htmlspecialchars($entry['date']) ?></strong> – <?= htmlspecialchars($entry['entry']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <p class="text-muted text-center">* Data loaded from MQ and database</p>
</div>

<?php include_once '../footer.php'; ?>