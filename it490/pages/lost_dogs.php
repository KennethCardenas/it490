<?php
// pages/lost_dogs.php
include_once __DIR__ . '/../auth.php';
requireAuth();
$user = $_SESSION['user'];
include_once __DIR__ . '/../includes/mq_client.php';

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resp = sendMessage([
        'type'    => 'lost_dogs_create',
        'user_id' => $user['id'],
        'dog_id'  => (int)$_POST['dog_id'],
        'dog_name'=> trim($_POST['dog_name']),
        'last_lat'=> trim($_POST['last_lat']),
        'last_lng'=> trim($_POST['last_lng']),
        'alert_radius'   => (int)$_POST['alert_radius'],
        'photo_url'=> trim($_POST['photo_url']),
        'description'=> trim($_POST['description']),
    ]);
    $message = $resp['message'] ?? '';
}

// Fetch active alerts
$list = sendMessage(['type'=>'lost_dogs_list']);
$alerts = $list['alerts'] ?? [];

$title = 'Lost Dog Alerts';
include_once __DIR__ . '/../header.php';
?>
<div class="container">
    <h1>Report a Lost Dog</h1>

    <?php if (!empty($message)): ?>
        <div class="alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" class="form">
        <div class="form-group">
            <label for="dog_id">Dog ID</label>
            <input id="dog_id" name="dog_id" type="number" required class="input"/>
        </div>
        <div class="form-group">
            <label for="dog_name">Name</label>
            <input id="dog_name" name="dog_name" type="text" required class="input"/>
        </div>
        <div class="form-group">
            <label for="last_lat">Last Seen Lat</label>
            <input id="last_lat" name="last_lat" type="text" class="input"/>
        </div>
        <div class="form-group">
            <label for="last_lng">Last Seen Lng</label>
            <input id="last_lng" name="last_lng" type="text" class="input"/>
        </div>
        <div class="form-group">
            <label for="alert_radius">Radius (mi)</label>
            <input id="alert_radius" name="alert_radius" type="number" value="10" class="input"/>
        </div>
        <div class="form-group">
            <label for="photo_url">Photo URL</label>
            <input id="photo_url" name="photo_url" type="url" class="input"/>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="textarea"></textarea>
        </div>
        <button type="submit" class="btn">Submit Alert</button>
    </form>

    <h2>Active Alerts</h2>
    <ul class="list">
        <?php foreach ($alerts as $a): ?>
        <li>
            <strong><?= htmlspecialchars($a['dog_name']) ?></strong>
            <span>[<?= htmlspecialchars($a['status']) ?>]</span><br>
            <em>Last seen:</em> <?= htmlspecialchars($a['last_lat']).', '.htmlspecialchars($a['last_lng']) ?>
            (<em>Radius:</em> <?= htmlspecialchars($a['alert_radius']) ?>mi)<br>
            <?php if ($a['photo_url']): ?>
                <img src="<?= htmlspecialchars($a['photo_url']) ?>" class="thumbnail" alt="Lost dog photo" />
            <?php endif; ?>
            <p><?= htmlspecialchars($a['description']) ?></p>
            <form method="POST" action="../send_user_request.php?type=lost_dogs_update" class="form-inline">
                <input type="hidden" name="id" value="<?= htmlspecialchars($a['id']) ?>" />
                <button name="status" value="found" class="btn small">Mark Found</button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>