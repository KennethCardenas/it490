<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
include_once __DIR__ . '/../includes/mq_client.php';
$dogId = intval($_GET['dog_id'] ?? 0);
if (!$dogId) { die('Dog not specified'); }

// add task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'type' => 'add_task',
        'dog_id' => $dogId,
        'user_id' => $user['id'],
        'title' => trim($_POST['title']),
        'description' => trim($_POST['description']),
        'due_date' => trim($_POST['due_date'])
    ];
    $taskResp = sendMessage($payload);
}

// toggle completion if requested
if (isset($_GET['toggle'])) {
    sendMessage(['type' => 'toggle_task', 'task_id' => intval($_GET['toggle'])]);
    header('Location: tasks.php?dog_id=' . $dogId);
    exit;
}

$tasks = [];
$resp = sendMessage(['type' => 'get_tasks', 'dog_id' => $dogId]);
if ($resp['status'] === 'success') { $tasks = $resp['tasks']; }
?>
<?php $title = "Tasks"; include_once __DIR__ . '/../header.php'; ?>
<div class="tasks-container">
    <h2>Tasks for Dog #<?= $dogId ?></h2>
    <?php if (!empty($taskResp['message'])): ?>
        <p><?= htmlspecialchars($taskResp['message']) ?></p>
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
<?php include_once __DIR__ . '/../footer.php'; ?>
