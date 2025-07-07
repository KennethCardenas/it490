<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
include_once __DIR__ . '/../includes/mq_client.php';

if(!hasRole('user')) { echo 'Access denied'; exit(); }

$user = $_SESSION['user'];
$sitterId = (int)($_GET['sitter_id'] ?? 0);
$dogsResp = sendMessage(['type'=>'list_dogs','owner_id'=>$user['id']]);
$dogs = ($dogsResp['status'] ?? '')==='success'?($dogsResp['dogs']??[]):[];

if($_SERVER['REQUEST_METHOD']==='POST') {
    $payload=[
        'type'=>'grant_dog_access',
        'dog_id'=>(int)$_POST['dog_id'],
        'sitter_id'=>$sitterId,
        'access_level'=>$_POST['access_level'] ?? 'viewer',
        'start_date'=>$_POST['start_date'] ?? null,
        'end_date'=>$_POST['end_date'] ?? null
    ];
    $r=sendMessage($payload);
    $msg = ($r['status']??'')==='success' ? 'Access granted' : 'Failed';
}
?>
<?php $title='Grant Access'; include_once __DIR__ . '/../header.php';?>
<div class="profile-container">
<h2>Grant Access</h2>
<?php if(!empty($msg)):?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
<form method="POST">
    <div class="form-group">
        <label>Dog</label>
        <select name="dog_id">
            <?php foreach($dogs as $d): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Access Level</label>
        <select name="access_level">
            <option value="viewer">Viewer</option>
            <option value="logger">Logger</option>
            <option value="medical">Medical</option>
        </select>
    </div>
    <div class="form-group">
        <label>Start Date</label>
        <input type="date" name="start_date">
    </div>
    <div class="form-group">
        <label>End Date</label>
        <input type="date" name="end_date">
    </div>
    <button type="submit">Save</button>
</form>
</div>
<?php include_once __DIR__ . '/../footer.php';?>
