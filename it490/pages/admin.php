<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api/connect.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

startSecureSession();

if (!isAdmin()) {
    header("Location: landing.php");
    exit();
}

// Send log_event to MQ
try {
    $mq = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!');
    $channel = $mq->channel();
    $channel->queue_declare('user_request_queue', false, false, false, false);

    $logPayload = [
        'type' => 'log_event',
        'user_id' => $_SESSION['user']['id'],
        'log_type' => 'admin_access',
        'message' => 'Admin panel accessed'
    ];

    $msg = new AMQPMessage(json_encode($logPayload), ['delivery_mode' => 2]);
    $channel->basic_publish($msg, '', 'user_request_queue');

    $channel->close();
    $mq->close();
} catch (Exception $e) {
    error_log("Failed to send MQ log from admin.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel | BarkBuddy</title>
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <?php include_once __DIR__ . '/../navbar.php'; ?>

    <div class="admin-container">
        <h2>Admin Dashboard</h2>
        <p>Welcome, admin! Here you can manage system data and review logs.</p>

        <!-- System Logs Section -->
        <div class="log-section">
            <h3>System Logs</h3>
            <table id="log-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Message</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Log Table JS -->
        <script>
        fetch('../api/logs.php')
            .then(res => res.json())
            .then(logs => {
                const tbody = document.querySelector('#log-table tbody');
                logs.forEach(log => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${log.ID}</td>
                        <td>${log.USERNAME}</td>
                        <td>${log.LOG_TYPE}</td>
                        <td>${log.MESSAGE}</td>
                        <td>${log.CREATED_AT}</td>
                    `;
                    tbody.appendChild(row);
                });
            });
        </script>

        <!-- Optional Raw Log View -->
        <ul>
            <li><a href="../api/logs.php" target="_blank">View Log File</a></li>
        </ul>

        <hr>

        <!-- Registered Users Section -->
        <div class="user-section">
            <h3>Registered Users</h3>
            <table id="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- User Table JS -->
        <script>
        fetch('../api/users.php')
            .then(res => res.json())
            .then(users => {
                const tbody = document.querySelector('#user-table tbody');
                users.forEach(user => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${user.ID}</td>
                        <td>${user.USERNAME}</td>
                        <td>${user.EMAIL}</td>
                        <td>${user.ROLE}</td>
                        <td>${user.CREATED_AT}</td>
                    `;
                    tbody.appendChild(row);
                });
            });
        </script>
    </div>
</body>
</html>