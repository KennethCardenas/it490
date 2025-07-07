<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
include_once __DIR__ . '/../includes/mq_client.php';

if(!hasRole('sitter')) { echo 'Access denied'; exit(); }
$user = $_SESSION['user'];
$dogId = (int)($_GET['dog_id'] ?? 0);
if(!$dogId){ echo 'Dog missing'; exit(); }

if($_SERVER['REQUEST_METHOD']==='POST') {
    $payload=[
        'type'=>'record_activity',
        'dog_id'=>$dogId,
        'sitter_id'=>$user['id'],
        'description'=>trim($_POST['description']),
        'mood'=>trim($_POST['mood'] ?? ''),
        'intensity'=>(int)($_POST['intensity'] ?? 0),
        'trigger_text'=>trim($_POST['trigger_text'] ?? '')
    ];
    $r=sendMessage($payload);
    $msg = ($r['status']??'')==='success' ? 'Recorded' : 'Failed';
}
?>
<?php $title='Record Activity'; include_once __DIR__ . '/../header.php';?>
<div class="profile-container">
<h2>Record Activity</h2>
<?php if(!empty($msg)):?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
<form method="POST">
    <div class="form-group">
        <label>Description</label>
        <textarea name="description" required></textarea>
    </div>
    <div class="form-group">
        <label>Mood</label>
        <input type="text" name="mood">
    </div>
    <div class="form-group">
        <label>Intensity</label>
        <input type="number" name="intensity" min="0" max="10">
    </div>
    <div class="form-group">
        <label>Trigger</label>
        <input type="text" name="trigger_text">
    </div>
    <button type="submit">Save</button>
</form>
</div>
<?php include_once __DIR__ . '/../footer.php';?>
