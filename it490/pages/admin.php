<?php
require_once __DIR__ . '/../auth.php';

startSecureSession();

// Block non-admins
if (!isAdmin()) {
    header("Location: landing.php");
    exit();
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

        <!-- Example content -->
        <ul>
            <li><a href="../api/logs.php" target="_blank">View Log File</a></li>
            </ul>

            
            <hr>
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