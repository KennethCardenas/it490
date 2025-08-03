<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
$user = $_SESSION['user'];
include_once __DIR__ . '/../includes/mq_client.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resp = sendMessage([
        'type' => 'invite_update',
        'code' => trim($_POST['code']),
        'status' => 'accepted',
        'accepted_by' => $user['id']
    ]);
    $message = $resp['message'] ?? '';
}

$title = 'Accept Invitation';
include_once __DIR__ . '/../header.php';
?>
<div class="container">
  <h1>Accept Invitation</h1>
  <?php if (!empty($message)): ?>
    <div class="alert"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <form method="POST" class="form">
    <input name="code" placeholder="Invitation Code" required class="input">
    <button type="submit" class="btn">Accept</button>
  </form>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
