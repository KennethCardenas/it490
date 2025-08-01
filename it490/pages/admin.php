<?php
require_once __DIR__ . '/../auth.php';
requireAuth();

if (!isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied';
    exit();
}

require_once __DIR__ . '/../api/connect.php';

// Fetch users
$users = [];
$res = $conn->query("SELECT id, username, email, role FROM USERS ORDER BY id");
if ($res) {
    $users = $res->fetch_all(MYSQLI_ASSOC);
}

// Fetch logs
$logs = [];
$logRes = $conn->query("SELECT l.ID, u.USERNAME, l.LOG_TYPE, l.MESSAGE, l.CREATED_AT FROM LOGS l JOIN USERS u ON l.USER_ID = u.ID ORDER BY l.CREATED_AT DESC LIMIT 100");
if ($logRes) {
    $logs = $logRes->fetch_all(MYSQLI_ASSOC);
}

$title = 'Admin Dashboard';
include_once __DIR__ . '/../header.php';
?>
<div class="admin-container">
    <h2>User Management</h2>
    <table class="admin-table">
        <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['id']) ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Recent Logs</h2>
    <table class="admin-table">
        <tr><th>ID</th><th>User</th><th>Type</th><th>Message</th><th>Time</th></tr>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['ID']) ?></td>
                <td><?= htmlspecialchars($log['USERNAME']) ?></td>
                <td><?= htmlspecialchars($log['LOG_TYPE']) ?></td>
                <td><?= htmlspecialchars($log['MESSAGE']) ?></td>
                <td><?= htmlspecialchars($log['CREATED_AT']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
