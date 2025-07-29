<?php
require_once __DIR__ . '/../auth.php'; 
require_once __DIR__ . '/../db_connect.php'; // adjust if your DB connection file is named differently

startSecureSession();

// Block non-admins
if (!isAdmin()) {
    header("Location: landing.php");
    exit();
}

// Fetch logs from DB
$conn = getDBConnection(); // Make sure this matches your DB connection function
$sql = "SELECT id, user_id, type, message, created_at FROM logs ORDER BY created_at DESC LIMIT 100";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Logs | BarkBuddy</title>
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
<?php include_once __DIR__ . '/../navbar.php'; ?>
<div class="admin-container">
    <h2>Centralized Logs</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>User ID</th>
                <th>Type</th>
                <th>Message</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['user_id']) ?></td>
                <td><?= htmlspecialchars($row['type']) ?></td>
                <td><?= htmlspecialchars($row['message']) ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No logs found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>