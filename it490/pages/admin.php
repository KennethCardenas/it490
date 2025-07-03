<?php
require_once __DIR__ . '/../auth.php';
requireAuth();
requireRole('admin');

$user = $_SESSION['user'];
$title = 'Admin Dashboard';
include_once __DIR__ . '/../header.php';
?>
<div class="admin-container">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($user['username']) ?>. You have administrative access.</p>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
