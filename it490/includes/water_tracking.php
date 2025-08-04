<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
include_once __DIR__ . '/../includes/mq_client.php';

$dogId = (int)($_GET['dog_id'] ?? 0);
if (!$dogId) {
    header('Location: dogs.php');
    exit();
}

// Fetch dog details
$dogResp = sendMessage(['type' => 'get_dog', 'dog_id' => $dogId]);
$dog = ($dogResp['status'] ?? '') === 'success' ? ($dogResp['dog'] ?? null) : null;

if (!$dog || $dog['owner_id'] != $_SESSION['user']['id']) {
    echo 'Dog not found or access denied';
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (int)($_POST['amount'] ?? 0);
    $resp = sendMessage([
        'type' => 'add_water',
        'dog_id' => $dogId,
        'user_id' => $_SESSION['user']['id'] ?? 0,
        'amount' => $amount,
        'notes' => ''
    ]);
    
    if (($resp['status'] ?? '') === 'success') {
        $success = "Water intake recorded: {$amount}ml";
        // Refresh dog data
        $dogResp = sendMessage(['type' => 'get_dog', 'dog_id' => $dogId]);
        $dog = ($dogResp['status'] ?? '') === 'success' ? ($dogResp['dog'] ?? $dog) : $dog;
    } else {
        $error = $resp['message'] ?? 'Failed to record water intake';
    }
}

// Get water history
$waterResp = sendMessage(['type' => 'get_water', 'dog_id' => $dogId]);
$waterData = ($waterResp['status'] ?? '') === 'success' ? ($waterResp['entries'] ?? []) : [];
$percentage = 0;

$title = "Water Tracking - " . htmlspecialchars($dog['name']);
include_once __DIR__ . '/../header.php';
?>

<div class="profile-container">
    <h2>Water Tracking for <?= htmlspecialchars($dog['name']) ?></h2>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <div class="water-progress">
        <h3>Today's Water Intake</h3>
        <div class="progress-bar">
            <div class="progress" style="width: <?= min($percentage, 100) ?>%"></div>
            <span class="progress-text">
                <?= ($waterData['last_water_amount'] ?? 0) ?>ml / 
                <?= ($waterData['daily_water_goal'] ?? 1000) ?>ml
            </span>
        </div>
    </div>

    <form method="POST" class="water-form">
        <div class="form-group">
            <label for="amount">Water Amount (ml)</label>
            <input type="number" id="amount" name="amount" min="50" max="5000" step="50" required>
        </div>
        <button type="submit" class="btn-water">Record Water</button>
    </form>

    <?php if (!empty($waterData['last_water_time'])): ?>
        <div class="water-history">
            <h4>Last Recorded</h4>
            <p><?= date('M j, Y g:i a', strtotime($waterData['last_water_time'])) ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.water-progress {
    margin: 2rem 0;
}

.progress-bar {
    height: 30px;
    background: #e0e0e0;
    border-radius: 15px;
    position: relative;
    overflow: hidden;
    margin: 1rem 0;
}

.progress {
    height: 100%;
    background: #0077cc;
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #333;
    font-weight: bold;
}

.btn-water {
    background: #1e88e5;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s;
}

.btn-water:hover {
    background: #1565c0;
}

.water-history {
    margin-top: 2rem;
    padding: 1rem;
    background: #f5f5f5;
    border-radius: 8px;
}
</style>

<?php include_once __DIR__ . '/../footer.php'; ?>

