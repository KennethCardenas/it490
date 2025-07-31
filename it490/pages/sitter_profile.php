<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
include_once __DIR__ . '/../includes/mq_client.php';

if (!hasRole('sitter')) {
    echo 'Access denied';
    exit();
}

$user = $_SESSION['user'];
$resp = sendMessage(['type'=>'get_sitter_profile','user_id'=>$user['id']]);
$profile = ($resp['status'] ?? '') === 'success' ? ($resp['profile'] ?? []) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'type' => 'update_sitter_profile',
        'user_id' => $user['id'],
        'bio' => trim($_POST['bio'] ?? ''),
        'experience_years' => (int)($_POST['experience_years'] ?? 0)
    ];
    $u = sendMessage($payload);
    if (($u['status'] ?? '') === 'success') {
        $success = 'Profile saved';
        $resp = sendMessage(['type'=>'get_sitter_profile','user_id'=>$user['id']]);
        $profile = ($resp['status'] ?? '') === 'success' ? ($resp['profile'] ?? []) : [];
    } else {
        $error = 'Failed to save profile';
    }
}
?>
<?php $title='Sitter Profile'; include_once __DIR__ . '/../header.php';?>
<div class="profile-container">
<h2>Sitter Profile</h2>
<?php if(!empty($success)):?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if(!empty($error)):?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
<form method="POST">
    <div class="form-group">
        <label>Bio</label>
        <textarea name="bio" required><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
        <label>Experience (years)</label>
        <input type="number" name="experience_years" value="<?= htmlspecialchars($profile['experience_years'] ?? 0) ?>">
    </div>
    <button type="submit">Save</button>
</form>
</div>
<?php include_once __DIR__ . '/../footer.php';?>
