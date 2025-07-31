<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
include_once __DIR__ . '/../includes/mq_client.php';

if(!hasRole('sitter')) { echo 'Access denied'; exit(); }
$user = $_SESSION['user'];
$resp = sendMessage(['type'=>'list_active_dogs','sitter_id'=>$user['id']]);
$dogs = ($resp['status'] ?? '')==='success'?($resp['dogs']??[]):[];
?>
<?php $title='My Dogs'; include_once __DIR__ . '/../header.php';?>
<div class="profile-container">
<h2>Active Dogs</h2>
<ul>
<?php foreach($dogs as $d): ?>
    <li>
        <?= htmlspecialchars($d['name']) ?>
        <a href="record_activity.php?dog_id=<?= $d['id'] ?>">Record Activity</a>
        <a href="dog_activities.php?dog_id=<?= $d['id'] ?>">View Activity</a>
    </li>
<?php endforeach; ?>
</ul>
</div>
<?php include_once __DIR__ . '/../footer.php';?>
