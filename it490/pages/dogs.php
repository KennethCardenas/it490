<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
include_once __DIR__ . '/../includes/mq_client.php';

// handle add dog
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'type' => 'create_dog',
        'user_id' => $user['id'],
        'name' => trim($_POST['name']),
        'breed' => trim($_POST['breed']),
        'health_status' => trim($_POST['health_status']),
        'notes' => trim($_POST['notes'])
    ];
    $addResponse = sendMessage($payload);
}

// fetch dogs
$dogs = [];
$response = sendMessage(['type' => 'get_dogs', 'user_id' => $user['id']]);
if ($response['status'] === 'success') {
    $dogs = $response['dogs'];
}
?>
<?php $title = "My Dogs"; include_once __DIR__ . '/../header.php'; ?>
<div class="dogs-container">
    <h2>Your Dogs</h2>
    <?php if (!empty($addResponse['message'])): ?>
        <p><?= htmlspecialchars($addResponse['message']) ?></p>
    <?php endif; ?>
    <ul>
        <?php foreach ($dogs as $d): ?>
            <li>
                <strong><?= htmlspecialchars($d['name']) ?></strong> (<?= htmlspecialchars($d['breed']) ?>)
                - <a href="tasks.php?dog_id=<?= $d['id'] ?>">Tasks</a>
            </li>
        <?php endforeach; ?>
    </ul>

    <h3>Add Dog</h3>
    <form method="POST">
        <input type="text" name="name" placeholder="Name" required>
        <input type="text" name="breed" placeholder="Breed">
        <input type="text" name="health_status" placeholder="Health Status">
        <textarea name="notes" placeholder="Care instructions"></textarea>
        <button type="submit">Add Dog</button>
    </form>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
