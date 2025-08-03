<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
$user = $_SESSION['user'];
include_once __DIR__ . '/../includes/mq_client.php';

// fetch requests
$resp = sendMessage(['type'=>'playdate_requests_list','user_id'=>$user['id']]);
$reqs = $resp['requests'] ?? [];
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$title = 'Playdate Requests';
include_once __DIR__ . '/../header.php';
?>
<div class="container">
  <h1>Incoming Playdate Requests</h1>
  <?php if (!empty($message)): ?>
  <div class="alert"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <ul class="list">
    <?php foreach ($reqs as $r): ?>
    <li>
      <p>From user <?= htmlspecialchars($r['requester_id']) ?>: <?= htmlspecialchars($r['custom_message']) ?></p>
      <form method="POST" action="../send_user_request.php" class="form-inline">
        <input type="hidden" name="type" value="playdate_requests_update">
        <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
        <button name="status" value="accepted" class="btn small">Accept</button>
        <button name="status" value="declined" class="btn small">Decline</button>
      </form>
    </li>
    <?php endforeach; ?>
  </ul>
</div>

<?php include_once __DIR__ . '/../footer.php'; ?>
