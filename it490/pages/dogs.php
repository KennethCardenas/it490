<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
// connect directly to the database
require_once __DIR__ . '/../api/connect.php';

// handle add dog
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // owner_id column stores the user that owns the dog
    $stmt = $conn->prepare(
        "INSERT INTO DOGS (OWNER_ID, NAME, BREED, HEALTH_STATUS, NOTES) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "issss",
        $user['id'],
        $name,
        $breed,
        $health,
        $notes
    );

    $name = trim($_POST['name']);
    $breed = trim($_POST['breed']);
    $health = trim($_POST['health_status']);
    $notes = trim($_POST['notes']);

    if ($stmt->execute()) {
        $addMessage = 'Dog added';
    } else {
        $addMessage = 'Failed to create dog: ' . $conn->error;
    }
    $stmt->close();
}

// fetch dogs
$dogs = [];
$stmt = $conn->prepare("SELECT * FROM DOGS WHERE OWNER_ID = ?");
$stmt->bind_param("i", $user['id']);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    $dogs = $res->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();
?>
<?php
    $title = "My Dogs";
    $pageCss = '/it490/styles/dogs.css';
    include_once __DIR__ . '/../header.php';
?>
<div class="dogs-container">
    <h2>Your Dogs</h2>
    <?php if (!empty($addMessage)): ?>
        <p><?= htmlspecialchars($addMessage) ?></p>
    <?php endif; ?>
    <ul>
        <?php foreach ($dogs as $d): ?>
            <li>
                <strong><?= htmlspecialchars($d['name']) ?></strong> (<?= htmlspecialchars($d['breed']) ?>)
                - <a href="tasks.php?dog_id=<?= $d['id'] ?>">Tasks</a>
            </li>
        <?php endforeach; ?>
    </ul>

    <h3>Add Dog</h3>
    <form method="POST">
        <input type="text" name="name" placeholder="Name" required>
        <input type="text" name="breed" placeholder="Breed">
        <input type="text" name="health_status" placeholder="Health Status">
        <textarea name="notes" placeholder="Care instructions"></textarea>
        <button type="submit">Add Dog</button>
    </form>
</div>
<?php $conn->close(); ?>
<?php include_once __DIR__ . '/../footer.php'; ?>
