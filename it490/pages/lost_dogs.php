<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
$user = $_SESSION['user'];
include_once __DIR__ . '/../includes/mq_client.php';

// create or fetch
if ($_SERVER['REQUEST_METHOD']==='POST') {
    sendMessage(array_merge(['type'=>'lost_dogs_create'], $_POST, ['user_id'=>$user['id']]));
}
$resp = sendMessage(['type'=>'lost_dogs_list']);
$alerts = $resp['alerts'] ?? [];

$title = 'Lost Dog Alerts';
include_once __DIR__ . '/../header.php';
?>
<div class="container">
  <h1>Report a Lost Dog</h1>
  <form method="POST" class="form">
    <input name="dog_id" type="number" placeholder="Dog ID" class="input" required>
    <input name="dog_name" placeholder="Name" class="input" required>
    <input name="last_lat" type="text" placeholder="Lat" class="input">
    <input name="last_lng" type="text" placeholder="Lng" class="input">
    <input name="alert_radius" type="number" placeholder="Radius mi" class="input" value="10">
    <input name="photo_url" placeholder="Photo URL" class="input">
    <textarea name="description" placeholder="Description" class="textarea"></textarea>
    <button type="submit" class="btn">Submit</button>
  </form>

  <h2>Active Alerts</h2>
  <ul class="list">
    <?php foreach ($alerts as $a): ?>
    <li>
      <p><?= htmlspecialchars($a['dog_name']) ?> [<?= htmlspecialchars($a['status']) ?>]</p>
      <form method="POST" action="../send_user_request.php?type=lost_dogs_update">
        <input type="hidden" name="id" value="<?= htmlspecialchars($a['id']) ?>">
        <button name="status" value="found" class="btn small">Mark Found</button>
      </form>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>