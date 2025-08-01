<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
$user = $_SESSION['user'];
include_once __DIR__ . '/../includes/mq_client.php';

// Create or list invites via MQ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // invite_create -> creates and returns message
    $resp = sendMessage([
        'type'             => 'invite_create',
        'user_id'          => $user['id'],
        'dog_id'           => (int)$_POST['dog_id'],
        'sitter_email'     => trim($_POST['sitter_email']),
        'permission_level' => $_POST['permission_level'],
    ]);
    $message = $resp['message'] ?? '';
}
// always fetch current list
$list = sendMessage(['type'=>'invite_list','user_id'=>$user['id']]);
$invites = $list['invites'] ?? [];

$title = 'Invitations';
include_once __DIR__ . '/../header.php';
?>
<div class="container">
  <h1>Manage Invitations</h1>

  <?php if (!empty($message)): ?>
    <div class="alert"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="POST" class="form">
    <div class="form-group">
      <label for="dog_id">Dog ID</label>
      <input id="dog_id" name="dog_id" type="number" required class="input"/>
    </div>
    <div class="form-group">
      <label for="sitter_email">Sitter Email</label>
      <input id="sitter_email" name="sitter_email" type="email" required class="input"/>
    </div>
    <div class="form-group">
      <label for="permission_level">Permission Level</label>
      <select id="permission_level" name="permission_level" class="input">
        <option value="viewer">Viewer</option>
        <option value="logger">Logger</option>
        <option value="medical">Medical</option>
      </select>
    </div>
    <button type="submit" class="btn">Create Invite</button>
  </form>

  <h2>Existing Invites</h2>
  <table class="table">
    <thead>
      <tr><th>ID</th><th>Code</th><th>Dog</th><th>Sitter</th><th>Perm</th><th>Status</th><th>Expires</th></tr>
    </thead>
    <tbody>
      <?php foreach ($invites as $i): ?>
      <tr>
        <td><?= htmlspecialchars($i['id']) ?></td>
        <td><?= htmlspecialchars($i['code']) ?></td>
        <td><?= htmlspecialchars($i['dog_id']) ?></td>
        <td><?= htmlspecialchars($i['sitter_email']) ?></td>
        <td><?= htmlspecialchars($i['permission_level']) ?></td>
        <td><?= htmlspecialchars($i['status']) ?></td>
        <td><?= htmlspecialchars($i['expires_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>