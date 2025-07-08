<?php
include_once '../header.php';
include_once '../includes/mq_client.php';

if (!isset($_SESSION['user']['id'])) {
    echo "<p class='text-danger text-center'>You must be logged in to view this page.</p>";
    include_once '../footer.php';
    exit();
}

$userId = $_SESSION['user']['id'];

// Send MQ request to fetch owner profile
$payload = [
    'type' => 'get_owner_profile',
    'user_id' => $userId
];

$response = sendMessage($payload);
$owner = $response['owner'] ?? null;
$dogs = $response['dogs'] ?? [];
?>

<div class="container mt-4">
    <h2 class="text-center text-primary">Owner Profile</h2>

    <?php if (!$owner): ?>
        <p class="text-center text-danger">Failed to load profile information.</p>
    <?php else: ?>
        <div class="card mt-4 p-3 shadow-sm">
            <h4>Owner: <?= htmlspecialchars($owner['username']) ?></h4>
            <p><strong>Email:</strong> <?= htmlspecialchars($owner['email']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($owner['phone'] ?? 'N/A') ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($owner['address'] ?? 'N/A') ?></p>
            <hr>
            <h5>Registered Dogs</h5>
            <?php if (empty($dogs)): ?>
                <p>No dogs registered.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($dogs as $dog): ?>
                        <li><strong><?= htmlspecialchars($dog['name']) ?></strong> (<?= htmlspecialchars($dog['breed']) ?>) - Age: <?= htmlspecialchars($dog['age']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../footer.php'; ?>