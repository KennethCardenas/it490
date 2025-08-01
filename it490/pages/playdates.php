<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
$user = $_SESSION['user'];
include_once __DIR__ . '/../includes/mq_client.php';

$filters = [];
foreach (['age_range','size','energy_level','temperament','play_style','gender_pref'] as $f) {
    if (!empty($_GET[$f])) {
        $filters[$f] = $_GET[$f];
    }
}
// fetch playdates
$resp = sendMessage(array_merge(['type'=>'playdates_list'], $filters));
$playdates = $resp['playdates'] ?? [];

$title = 'Playdates';
include_once __DIR__ . '/../header.php';
?>
<div class="container">
  <h1>Community Playdates</h1>
  <form method="GET" class="form form-inline">
    <select name="age_range" class="input"><option value="">Any Age</option><option>puppy</option><option>adult</option><option>elder</option></select>
    <select name="size" class="input"><option value="">Any Size</option><option>Big</option><option>Medium</option><option>Small</option></select>
    <select name="energy_level" class="input"><option value="">Any Energy</option><option>low</option><option>moderate</option><option>high</option></select>
    <button type="submit" class="btn">Filter</button>
  </form>

  <ul class="list">
    <?php foreach ($playdates as $p): ?>
    <li>
      <h3><?= htmlspecialchars($p['title']) ?></h3>
      <p><?= htmlspecialchars($p['scheduled_at']) ?> @ <?= htmlspecialchars($p['location']) ?></p>
      <p><?= htmlspecialchars($p['description']) ?></p>
      <form method="POST" action="../send_user_request.php?type=playdate_request" class="form-inline">
        <input type="hidden" name="target_owner_id" value="<?= htmlspecialchars($p['created_by']) ?>">
        <input type="hidden" name="dog_size_match" value="Medium">
        <input name="location_preference" placeholder="Location" required class="input">
        <input name="custom_message" placeholder="Message" class="input">
        <button type="submit" class="btn small">Request</button>
      </form>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>