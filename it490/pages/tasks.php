<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth.php';
if (!function_exists('requireAuth')) {
    die('Authentication system not available');
}
requireAuth();

$mqClientPath = __DIR__ . '/../includes/mq_client.php';
if (!file_exists($mqClientPath)) {
    die('MQ client system not available');
}
require_once $mqClientPath;
require_once __DIR__ . '/../api/connect.php';

function addTaskToDatabase($conn, $dogId, $userId, $title, $description, $dueDate) {
    $stmt = $conn->prepare("INSERT INTO DOG_TASKS (dog_id, user_id, title, description, due_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $dogId, $userId, $title, $description, $dueDate);
    return $stmt->execute();
}

function toggleTaskInDatabase($conn, $taskId) {
    $stmt = $conn->prepare("UPDATE DOG_TASKS SET completed = NOT completed WHERE id = ?");
    $stmt->bind_param("i", $taskId);
    return $stmt->execute();
}

function deleteTaskFromDatabase($conn, $taskId) {
    $stmt = $conn->prepare("DELETE FROM DOG_TASKS WHERE id = ?");
    $stmt->bind_param("i", $taskId);
    return $stmt->execute();
}

function getTasksFromDatabase($conn, $dogId) {
    $stmt = $conn->prepare("SELECT * FROM DOG_TASKS WHERE dog_id = ? ORDER BY due_date");
    $stmt->bind_param("i", $dogId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$dogId = isset($_GET['dog_id']) ? (int)$_GET['dog_id'] : 0;
if ($dogId <= 0) {
    header("Location: dogs.php");
    exit();
}

$user = $_SESSION['user'] ?? null;
$taskResp = [];
$msg = isset($_GET['msg']) ? trim($_GET['msg']) : '';
$dog = null;
$tasks = [];

// Handle task deletion
if (isset($_GET['delete'])) {
    $taskId = (int)$_GET['delete'];
    try {
        $payload = [
            'type' => 'delete_task',
            'task_id' => $taskId
        ];
        
        $response = @sendMessage($payload);
        
        if (empty($response) || ($response['status'] ?? '') !== 'success') {
            if (deleteTaskFromDatabase($conn, $taskId)) {
                $redirectMsg = urlencode('Task deleted successfully');
            } else {
                throw new Exception('Failed to delete task');
            }
        } else {
            $redirectMsg = urlencode($response['message'] ?? 'Task deleted successfully');
        }
        
        header("Location: tasks.php?dog_id={$dogId}&msg={$redirectMsg}");
        exit();
    } catch (Exception $e) {
        $taskResp['message'] = 'Error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $dueDate = isset($_POST['due_date']) ? trim($_POST['due_date']) : '';
        
        if (empty($title) || empty($dueDate)) {
            throw new Exception('Title and due date are required');
        }

        $payload = [
            'type' => 'add_task',
            'dog_id' => $dogId,
            'user_id' => $user['id'] ?? 0,
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate
        ];

        $taskResp = @sendMessage($payload);

        if (empty($taskResp) || ($taskResp['status'] ?? '') !== 'success') {
            if (addTaskToDatabase($conn, $dogId, $user['id'] ?? 0, $title, $description, $dueDate)) {
                $redirectMsg = urlencode('Task added successfully');
            } else {
                throw new Exception('Failed to add task');
            }
        } else {
            $redirectMsg = urlencode($taskResp['message'] ?? 'Task added successfully');
        }

        header("Location: tasks.php?dog_id={$dogId}&msg={$redirectMsg}");
        exit();
    } catch (Exception $e) {
        $taskResp['message'] = 'Error: ' . $e->getMessage();
    }
}

if (isset($_GET['toggle'])) {
    try {
        $taskId = (int)$_GET['toggle'];
        if ($taskId > 0) {
            $resp = @sendMessage(['type' => 'toggle_task', 'task_id' => $taskId]);
            if (empty($resp) || ($resp['status'] ?? '') !== 'success') {
                toggleTaskInDatabase($conn, $taskId);
            }
            header("Location: tasks.php?dog_id={$dogId}");
            exit();
        }
    } catch (Exception $e) {
        $taskResp['message'] = 'Error toggling task: ' . $e->getMessage();
    }
}

if ($msg) {
    $taskResp['message'] = urldecode($msg);
}

try {
    $dogResp = @sendMessage(['type' => 'get_dog', 'dog_id' => $dogId]);
    if (($dogResp['status'] ?? '') === 'success') {
        $dog = $dogResp['dog'] ?? null;
    } else {
        $stmt = $conn->prepare("SELECT * FROM DOGS WHERE id = ?");
        $stmt->bind_param("i", $dogId);
        $stmt->execute();
        $dog = $stmt->get_result()->fetch_assoc();
    }
} catch (Exception $e) {
    $stmt = $conn->prepare("SELECT * FROM DOGS WHERE id = ?");
    $stmt->bind_param("i", $dogId);
    $stmt->execute();
    $dog = $stmt->get_result()->fetch_assoc();
}

try {
    $resp = @sendMessage(['type' => 'get_tasks', 'dog_id' => $dogId]);
    if (($resp['status'] ?? '') === 'success') {
        $tasks = $resp['tasks'] ?? [];
    } else {
        $tasks = getTasksFromDatabase($conn, $dogId);
    }
} catch (Exception $e) {
    $tasks = getTasksFromDatabase($conn, $dogId);
}

$title = "Tasks" . ($dog ? " - " . htmlspecialchars($dog['name']) : "");

$headerPath = __DIR__ . '/../header.php';
if (file_exists($headerPath)) {
    include_once $headerPath;
} else {
    echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($title) . '</title><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"></head><body>';
}
?>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Deletion</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this task? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel">Cancel</button>
            <a id="confirmDelete" class="btn-delete">Delete Permanently</a>
        </div>
    </div>
</div>

<div class="tasks-app">
    <div class="tasks-header">
        <div class="header-content">
            <h1><i class="fas fa-tasks tasks-icon"></i> Task Management</h1>
            <?php if ($dog): ?>
                <h2>For <?= htmlspecialchars($dog['name']) ?> <i class="fas fa-paw paw-icon"></i></h2>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-container">
        <?php if (!empty($taskResp['message'])): ?>
            <div class="alert <?= strpos($taskResp['message'], 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                <i class="fas <?= strpos($taskResp['message'], 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                <?= htmlspecialchars($taskResp['message']) ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="form-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> Add New Task
                </div>
                <form method="POST" class="task-form">
                    <div class="form-group">
                        <label for="title"><i class="fas fa-heading"></i> Title</label>
                        <input type="text" id="title" name="title" required placeholder="Enter task title">
                    </div>
                    <div class="form-group">
                        <label for="due_date"><i class="fas fa-calendar-day"></i> Due Date</label>
                        <input type="datetime-local" id="due_date" name="due_date" required>
                    </div>
                    <div class="form-group">
                        <label for="description"><i class="fas fa-align-left"></i> Description</label>
                        <textarea id="description" name="description" placeholder="Enter task description"></textarea>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Add Task
                    </button>
                </form>
            </div>

            <div class="history-card">
                <div class="card-header">
                    <i class="fas fa-tasks"></i> Current Tasks
                </div>
                <?php if (empty($tasks)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No tasks found</p>
                    </div>
                <?php else: ?>
                    <div class="task-entries">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-heading"></i> Title</th>
                                    <th><i class="fas fa-calendar-day"></i> Due Date</th>
                                    <th><i class="fas fa-check-circle"></i> Status</th>
                                    <th><i class="fas fa-cog"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($task['title'] ?? '') ?></td>
                                        <td><?= isset($task['due_date']) ? date('M j, g:i a', strtotime($task['due_date'])) : '' ?></td>
                                        <td>
                                            <span class="status-badge <?= $task['completed'] ? 'completed' : 'pending' ?>">
                                                <?= $task['completed'] ? 'Completed' : 'Pending' ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <a href="?dog_id=<?= $dogId ?>&toggle=<?= $task['id'] ?>" class="action-link" title="<?= $task['completed'] ? 'Reopen' : 'Complete' ?>">
                                                <i class="fas <?= $task['completed'] ? 'fa-undo' : 'fa-check' ?>"></i>
                                            </a>
                                            <a href="#" class="action-link delete-link" title="Delete" data-task-id="<?= $task['id'] ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php if (!empty($task['description'])): ?>
                                        <tr class="task-description-row">
                                            <td colspan="4">
                                                <div class="description-content">
                                                    <strong>Description:</strong> <?= htmlspecialchars($task['description']) ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #3498db;
    --secondary-color: #e74c3c;
    --success-color: #2ecc71;
    --error-color: #e74c3c;
    --text-color: #2c3e50;
    --light-gray: #ecf0f1;
    --medium-gray: #bdc3c7;
    --white: #ffffff;
    --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --border-radius: 12px;
    --transition: all 0.3s ease;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    background-color: #f5f7fa;
}

.tasks-app {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.tasks-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px 0;
}

.header-content {
    max-width: 600px;
    margin: 0 auto;
}

.tasks-header h1 {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.tasks-header h2 {
    font-size: 1.5rem;
    color: var(--text-color);
    font-weight: 400;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.tasks-icon {
    color: var(--primary-color);
}

.paw-icon {
    color: var(--secondary-color);
}

.main-container {
    max-width: 1100px;
    margin: 0 auto;
}

.alert {
    padding: 15px 20px;
    border-radius: var(--border-radius);
    margin: 20px auto;
    max-width: 800px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--shadow);
}

.alert-success {
    background-color: rgba(46, 204, 113, 0.1);
    color: var(--success-color);
    border-left: 4px solid var(--success-color);
}

.alert-error {
    background-color: rgba(231, 76, 60, 0.1);
    color: var(--error-color);
    border-left: 4px solid var(--error-color);
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-top: 20px;
}

.form-card, .history-card {
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, var(--primary-color), #2980b9);
    color: var(--white);
    padding: 18px 25px;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.task-form {
    padding: 25px;
}

.form-group {
    margin-bottom: 25px;
    width: 100%;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: var(--text-color);
    font-size: 1rem;
}

.form-group input[type="text"],
.form-group input[type="datetime-local"],
.form-group textarea {
    width: calc(100% - 28px);
    padding: 14px;
    border: 2px solid var(--light-gray);
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition);
    margin: 0;
    box-sizing: border-box;
}

input[type="datetime-local"] {
    height: 48px;
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
    width: calc(100% - 28px);
}

.btn-submit {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--success-color), #27ae60);
    color: var(--white);
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: var(--transition);
}

.btn-submit:hover {
    background: linear-gradient(135deg, #27ae60, #219653);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(46, 204, 113, 0.3);
}

.empty-state {
    padding: 50px 20px;
    text-align: center;
    color: var(--medium-gray);
}

.empty-state i {
    font-size: 3.5rem;
    color: var(--light-gray);
    margin-bottom: 20px;
}

.empty-state p {
    font-size: 1.3rem;
    margin: 0;
    color: var(--medium-gray);
}

.task-entries {
    padding: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid var(--light-gray);
}

th {
    background-color: #f8f9fa;
    color: var(--text-color);
    font-weight: 600;
    font-size: 0.95rem;
}

tr:hover {
    background-color: #f8f9fa;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-badge.completed {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.pending {
    background-color: #fff3cd;
    color: #856404;
}

.actions {
    display: flex;
    gap: 10px;
}

.action-link {
    color: var(--primary-color);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
    padding: 5px 10px;
    border-radius: 4px;
}

.action-link:hover {
    background-color: #f0f0f0;
    text-decoration: none;
}

.delete-link {
    color: var(--secondary-color) !important;
}

.delete-link:hover {
    background-color: #fdecea;
}

.task-description-row {
    background-color: #f8f9fa;
}

.description-content {
    padding: 10px;
    font-size: 0.9rem;
    color: #555;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from {opacity: 0;}
    to {opacity: 1;}
}

.modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    overflow: hidden;
    animation: slideIn 0.3s;
}

@keyframes slideIn {
    from {transform: translateY(-50px); opacity: 0;}
    to {transform: translateY(0); opacity: 1;}
}

.modal-header {
    padding: 20px;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.4rem;
}

.close-modal {
    font-size: 1.8rem;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s;
}

.close-modal:hover {
    color: #ecf0f1;
}

.modal-body {
    padding: 20px;
    border-bottom: 1px solid #ecf0f1;
}

.modal-body p {
    margin: 0 0 15px;
    color: #2c3e50;
    line-height: 1.5;
}

.modal-footer {
    padding: 15px 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-cancel, .btn-delete {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    font-size: 0.9rem;
}

.btn-cancel {
    background-color: #ecf0f1;
    color: #7f8c8d;
}

.btn-cancel:hover {
    background-color: #d5dbdb;
}

.btn-delete {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    text-decoration: none;
    display: inline-block;
}

.btn-delete:hover {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

@media (max-width: 900px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .tasks-header h1 {
        font-size: 2rem;
    }
    
    .tasks-header h2 {
        font-size: 1.3rem;
    }
}

@media (max-width: 600px) {
    .tasks-header h1 {
        font-size: 1.8rem;
        flex-direction: column;
        gap: 5px;
    }
    
    .tasks-header h2 {
        font-size: 1.1rem;
    }
    
    .card-header {
        font-size: 1.1rem;
        padding: 15px 20px;
    }
    
    .task-form {
        padding: 20px;
    }
    
    th, td {
        padding: 10px 12px;
        font-size: 0.9rem;
    }
    
    .status-badge {
        padding: 4px 8px;
        font-size: 0.8rem;
    }
    
    .actions {
        flex-direction: column;
        gap: 5px;
    }
    
    .action-link {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('deleteModal');
    const deleteLinks = document.querySelectorAll('.delete-link');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    const closeModal = document.querySelector('.close-modal');
    const cancelBtn = document.querySelector('.btn-cancel');
    
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const taskId = this.getAttribute('data-task-id');
            
            confirmDeleteBtn.href = `tasks.php?dog_id=<?= $dogId ?>&delete=${taskId}`;
            
            modal.style.display = 'block';
        });
    });
    
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    cancelBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
});
</script>

<?php
$footerPath = __DIR__ . '/../footer.php';
if (file_exists($footerPath)) {
    include_once $footerPath;
} else {
    echo '</body></html>';
}
?>
