<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
// connect directly to the database
require_once __DIR__ . '/../api/connect.php';
$dogId = intval($_GET['dog_id'] ?? 0);
if (!$dogId) { die('Dog not specified'); }

// add task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare(
        "INSERT INTO DOG_TASKS (dog_id, user_id, title, description, due_date) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "iisss",
        $dogId,
        $user['id'],
        $title,
        $desc,
        $due
    );
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $due = trim($_POST['due_date']);
    if ($stmt->execute()) {
        $taskMessage = 'Task added';
    } else {
        $taskMessage = 'Failed to add task: ' . $conn->error;
    }
    $stmt->close();
}

// toggle completion if requested
if (isset($_GET['toggle'])) {
    $toggleId = intval($_GET['toggle']);
    $stmt = $conn->prepare("UPDATE DOG_TASKS SET completed = NOT completed WHERE id = ?");
    $stmt->bind_param("i", $toggleId);
    $stmt->execute();
    $stmt->close();
    header('Location: tasks.php?dog_id=' . $dogId);
    exit;
}

$tasks = [];
$stmt = $conn->prepare("SELECT * FROM DOG_TASKS WHERE dog_id = ? ORDER BY due_date");
$stmt->bind_param("i", $dogId);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    $tasks = $res->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();
?>
<?php
    $title = "Tasks";
    $pageCss = '/it490/styles/tasks.css';
    include_once __DIR__ . '/../header.php';
?>
<div class="tasks-container">
    <h2>Tasks for Dog #<?= $dogId ?></h2>
    <?php if (!empty($taskMessage)): ?>
        <p><?= htmlspecialchars($taskMessage) ?></p>
    <?php endif; ?>
    <table>
        <tr><th>Title</th><th>Due</th><th>Status</th><th></th></tr>
        <?php foreach ($tasks as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['title']) ?></td>
                <td><?= htmlspecialchars($t['due_date']) ?></td>
                <td><?= $t['completed'] ? 'Done' : 'Pending' ?></td>
                <td><a href="?dog_id=<?= $dogId ?>&toggle=<?= $t['id'] ?>">Toggle</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h3>Add Task</h3>
    <form method="POST">
        <input type="text" name="title" placeholder="Title" required>
        <input type="datetime-local" name="due_date" required>
        <textarea name="description" placeholder="Description"></textarea>
        <button type="submit">Add</button>
    </form>
</div>
<?php $conn->close(); ?>
<?php include_once __DIR__ . '/../footer.php'; ?>
