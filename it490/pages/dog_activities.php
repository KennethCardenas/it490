<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
include_once __DIR__ . '/../includes/mq_client.php';

$dogId=(int)($_GET['dog_id'] ?? 0);
if(!$dogId){ echo 'Dog missing'; exit(); }
$resp=sendMessage(['type'=>'list_activities','dog_id'=>$dogId]);
$acts=($resp['status']??'')==='success'?($resp['activities']??[]):[];
?>
<?php $title='Activity Log'; include_once __DIR__ . '/../header.php';?>
<div class="profile-container">
<h2>Activity Log</h2>
<ul>
<?php foreach($acts as $a): ?>
    <li><?= htmlspecialchars($a['description']) ?> - <?= htmlspecialchars($a['created_at']) ?></li>
<?php endforeach; ?>
</ul>
</div>
<?php include_once __DIR__ . '/../footer.php';?>
