<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
include_once __DIR__ . '/../includes/mq_client.php';

$user = $_SESSION['user'];
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'type' => 'add_dog',
        'owner_id' => $user['id'],
        'name' => trim($_POST['name']),
        'breed' => trim($_POST['breed']),
        'age' => (int)($_POST['age'] ?? 0),
        'notes' => trim($_POST['notes'] ?? '')
    ];
    $resp = sendMessage($payload);
    if (($resp['status'] ?? '') === 'success') {
        $success = 'Dog added successfully';
    } else {
        $error = 'Failed to add dog: ' . htmlspecialchars($resp['message'] ?? '');
    }
}

$dogResp = sendMessage(['type' => 'list_dogs', 'owner_id' => $user['id']]);
$dogs = ($dogResp['status'] ?? '') === 'success' ? ($dogResp['dogs'] ?? []) : [];
?>
<?php $title = 'My Dogs'; include_once __DIR__ . '/../header.php'; ?>
<div class="profile-container">
    <h2>Your Dogs</h2>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <ul>
    <?php foreach ($dogs as $dog): ?>
        <li><?= htmlspecialchars($dog['name']) ?> (<?= htmlspecialchars($dog['breed']) ?>)</li>
    <?php endforeach; ?>
    </ul>
    <h3>Add Dog</h3>
    <form method="POST">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Breed</label>
            <input type="text" name="breed" required>
        </div>
        <div class="form-group">
            <label>Age</label>
            <input type="number" name="age" min="0">
        </div>
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes"></textarea>
        </div>
        <button type="submit">Add Dog</button>
    </form>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
