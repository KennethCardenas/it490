<?php
include_once __DIR__ . '/../header.php';
include_once __DIR__ . '/../includes/mq_client.php';

if (!isset($_SESSION['user']['id'])) {
    echo "<p class='text-danger text-center'>You must be logged in to view this page.</p>";
    include_once __DIR__ . '/../footer.php';
    exit();
}

$userId = $_SESSION['user']['id'];

// Send MQ request to get active dogs for this sitter
$payload = [
    'type' => 'get_sitter_dogs',
    'user_id' => $userId
];
$response = sendMessage($payload);

$dogs = $response['dogs'] ?? [];
?>

<div class="container mt-5">
    <h2 class="text-center text-success mb-4">Active Dogs Assigned to You</h2>

    <?php if (empty($dogs)): ?>
        <p class="text-center text-muted">No active dogs assigned.</p>
    <?php else: ?>
        <?php foreach ($dogs as $dog): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="card-title"><?= htmlspecialchars($dog['name']) ?> (<?= htmlspecialchars($dog['breed']) ?>)</h4>
                    <p><strong>Age:</strong> <?= htmlspecialchars($dog['age']) ?></p>
                    <p><strong>Care Instructions:</strong> <?= htmlspecialchars($dog['instructions']) ?></p>
                    <p><strong>Start Date:</strong> <?= htmlspecialchars($dog['start_date']) ?></p>
                    <p><strong>End Date:</strong> <?= htmlspecialchars($dog['end_date']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../footer.php'; ?>