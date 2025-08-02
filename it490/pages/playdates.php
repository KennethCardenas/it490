<?php
// pages/playdates.php
include_once __DIR__ . '/../auth.php';
requireAuth();
$user = $_SESSION['user'];
include_once __DIR__ . '/../mq_client.php';

// filters from GET
$filters = [];
foreach ([
    'age_range','size','energy_level','temperament','play_style','gender_pref','location'
] as $f) {
    if (!empty($_GET[$f])) {
        $filters[$f] = $_GET[$f];
    }
}
// fetch playdates
echo "[x] Received playdates_list request\n";
$resp = sendMessage(array_merge(['type'=>'playdates_list'], $filters));
echo "[+] playdates_list response received\n";
$playdates = $resp['playdates'] ?? [];

// handle create submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "[x] Sending playdates_create request\n";
    $resp = sendMessage([
        'type' => 'playdates_create',
        'user_id' => $user['id'],
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'scheduled_at' => $_POST['scheduled_at'],
        'location' => $_POST['location'],
        'age_range' => $_POST['age_range'],
        'size' => $_POST['size'],
        'energy_level' => $_POST['energy_level'],
        'temperament' => $_POST['temperament'],
        'play_style' => $_POST['play_style'],
        'gender_pref' => $_POST['gender_pref'],
    ]);
    echo "[+] playdates_create response: " . json_encode($resp) . "\n";
    $message = $resp['message'] ?? '';
}

$title = 'Playdates';
include_once __DIR__ . '/../header.php';
?>
<div class="container">
  <h1>Community Playdates</h1>

  <?php if (!empty($message)): ?>
    <div class="alert"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="GET" class="form form-inline">
    <select name="age_range" class="input"><option value="">Any Age</option><option>puppy</option><option>adult</option><option>elder</option></select>
    <select name="size" class="input"><option value="">Any Size</option><option>Big</option><option>Medium</option><option>Small</option></select>
    <select name="energy_level" class="input"><option value="">Any Energy</option><option>low</option><option>moderate</option><option>high</option></select>
    <input name="location" placeholder="Location" class="input"/>
    <button type="submit" class="btn">Filter</button>
  </form>

  <h2>Create New Playdate</h2>
  <form method="POST" class="form">
    <input name="title" placeholder="Title" class="input" required>
    <input name="scheduled_at" type="datetime-local" class="input" required>
    <input name="location" placeholder="Location" class="input" required>
    <select name="age_range" class="input"><option>puppy</option><option>adult</option><option>elder</option></select>
    <select name="size" class="input"><option>Big</option><option>Medium</option><option>Small</option></select>
    <select name="energy_level" class="input"><option>low</option><option>moderate</option><option>high</option></select>
    <select name="temperament" class="input"><option>shy</option><option>friendly</option><option>aggressive</option></select>
    <input name="play_style" placeholder="Play Style (e.g. wrestling, fetch, etc)" class="input">
    <select name="gender_pref" class="input"><option>male</option><option>female</option><option>any</option></select>
    <textarea name="description" placeholder="Description" class="textarea"></textarea>
    <button type="submit" class="btn">Create</button>
  </form>

  <h2>Available Playdates</h2>
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
