<?php
include_once '../header.php';
require_once '../api/connect.php';

$sitter_id = $_GET['id'] ?? null;
if (!$sitter_id) {
    echo "<p>No sitter selected.</p>";
    include_once '../footer.php';
    exit();
}

// Fetch sitter info
$stmt = $conn->prepare("SELECT u.username, s.bio, s.rate, s.experience_years
                        FROM SITTERS s
                        JOIN USERS u ON s.user_id = u.id
                        WHERE s.id = ?");
$stmt->bind_param("i", $sitter_id);
$stmt->execute();
$sitter_result = $stmt->get_result();

if ($sitter_result->num_rows === 0) {
    echo "<p>Sitter not found.</p>";
    include_once '../footer.php';
    exit();
}

$sitter = $sitter_result->fetch_assoc();

// Fetch assigned dogs
$stmt = $conn->prepare("SELECT d.name, d.breed, d.age
                        FROM DOG_ACCESS da
                        JOIN DOGS d ON da.dog_id = d.id
                        WHERE da.sitter_id = ?");
$stmt->bind_param("i", $sitter_id);
$stmt->execute();
$dogs_result = $stmt->get_result();

$dogs = [];
while ($dog = $dogs_result->fetch_assoc()) {
    $dogs[] = $dog;
}

// Fetch dog activity logs
$stmt = $conn->prepare("SELECT dl.entry, dl.created_at
                        FROM DOG_ACCESS da
                        JOIN DOG_LOGS dl ON da.dog_id = dl.dog_id
                        WHERE da.sitter_id = ?
                        ORDER BY dl.created_at DESC");
$stmt->bind_param("i", $sitter_id);
$stmt->execute();
$logs_result = $stmt->get_result();

$logs = [];
while ($log = $logs_result->fetch_assoc()) {
    $logs[] = [
        'entry' => $log['entry'],
        'date' => date('m/d/Y', strtotime($log['created_at']))
    ];
}
?>

<h2 style="text-align:center; color:#4CAF50;">Sitter Profile: <?= htmlspecialchars($sitter['username']) ?></h2>

<div class="profile-container">
    <p><strong>Username:</strong> <?= htmlspecialchars($sitter['username']) ?></p>
    <p><strong>Rate:</strong> $<?= htmlspecialchars($sitter['rate']) ?>/hr</p>
    <p><strong>Experience:</strong> <?= htmlspecialchars($sitter['experience_years']) ?> years</p>
    <p><strong>Bio:</strong> <?= htmlspecialchars($sitter['bio']) ?></p>

    <h3>Assigned Dogs</h3>
    <ul>
        <?php foreach ($dogs as $dog): ?>
            <li><?= htmlspecialchars($dog['name']) ?> – <?= htmlspecialchars($dog['breed']) ?> – Age <?= htmlspecialchars($dog['age']) ?></li>
        <?php endforeach; ?>
    </ul>

    <h3>Activity Log</h3>
    <ul>
        <?php foreach ($logs as $log): ?>
            <li><?= $log['date'] ?> – <?= htmlspecialchars($log['entry']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<style>
.profile-container {
    background-color: #fff;
    border-radius: 10px;
    padding: 20px;
    max-width: 600px;
    margin: 20px auto;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
</style>

<?php include_once '../footer.php'; ?>