<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
include_once __DIR__ . '/../includes/mq_client.php';

$user = $_SESSION['user'];
$dogId = (int)($_GET['id'] ?? 0);
if (!$dogId) {
    header('Location: dogs.php');
    exit();
}

$resp = sendMessage(['type'=>'get_dog','dog_id'=>$dogId]);
$dog = ($resp['status'] ?? '') === 'success' ? ($resp['dog'] ?? null) : null;
if (!$dog || $dog['owner_id'] != $user['id']) {
    echo 'Dog not found or access denied';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'type' => 'update_dog',
        'dog_id' => $dogId,
        'owner_id' => $user['id'],
        'name' => trim($_POST['name']),
        'breed' => trim($_POST['breed']),
        'age' => (int)($_POST['age'] ?? 0),
        'notes' => trim($_POST['notes'] ?? ''),
        'care_instructions' => trim($_POST['care_instructions'] ?? '')
    ];
    $uResp = sendMessage($payload);
    if (($uResp['status'] ?? '') === 'success') {
        $success = 'Dog updated';
        $resp = sendMessage(['type'=>'get_dog','dog_id'=>$dogId]);
        $dog = ($resp['status'] ?? '') === 'success' ? ($resp['dog'] ?? $dog) : $dog;
    } else {
        $error = 'Update failed';
    }
}
?>
<?php $title='Dog Profile'; include_once __DIR__ . '/../header.php';?>
<div class="profile-container">
<h2>Edit Dog</h2>
<?php if(!empty($success)):?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if(!empty($error)):?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
<form method="POST">
    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($dog['name']) ?>" required>
    </div>
    <div class="form-group">
        <label>Breed</label>
        <input type="text" name="breed" value="<?= htmlspecialchars($dog['breed']) ?>" required>
    </div>
    <div class="form-group">
        <label>Age</label>
        <input type="number" name="age" value="<?= htmlspecialchars($dog['age']) ?>">
    </div>
    <div class="form-group">
        <label>Notes</label>
        <textarea name="notes"><?= htmlspecialchars($dog['notes']) ?></textarea>
    </div>
    <div class="form-group">
        <label>Care Instructions</label>
        <textarea name="care_instructions"><?= htmlspecialchars($dog['care_instructions']) ?></textarea>
    </div>
    <button type="submit">Save</button>
</form>
</div>
<?php include_once __DIR__ . '/../footer.php';?>
