<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/mq_client.php';

// Require admin role - this will redirect non-admins
requireRole('admin');

$user = $_SESSION['user'];
$error_message = '';
$success_message = '';

// Handle role changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_role') {
        $user_id = $_POST['user_id'] ?? '';
        $new_role = $_POST['new_role'] ?? '';
        
        if (!empty($user_id) && !empty($new_role)) {
            $payload = [
                'type' => 'change_user_role',
                'admin_id' => $user['id'],
                'user_id' => $user_id,
                'new_role' => $new_role
            ];
            
            $response = sendMessage($payload);
            
            if (isset($response['status']) && $response['status'] === 'success') {
                $success_message = "User role updated successfully!";
            } else {
                $error_message = "Failed to update role: " . ($response['message'] ?? 'Unknown error');
            }
        }
    }
}

// Get all users for management
$payload = ['type' => 'get_all_users', 'admin_id' => $user['id']];
$usersResponse = sendMessage($payload);
$users = $usersResponse['users'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel | BarkBuddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/style.css">
    <style>
        .admin-panel {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .admin-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #0077cc;
        }
        
        .admin-header h2 {
            color: #0077cc;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #0077cc, #005aa3);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-box h3 {
            margin: 0;
            font-size: 2rem;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .users-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .role-owner {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .role-sitter {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .role-admin {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .role-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-update {
            background: #0077cc;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-update:hover {
            background: #005aa3;
        }
        
        .alert {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
            border: 1px solid;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../navbar.php'; ?>
    
    <div class="admin-panel">
        <div class="admin-header">
            <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
            <p>Welcome, <strong><?= htmlspecialchars($user['username']) ?></strong>! Manage your BarkBuddy application.</p>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <div class="admin-stats">
            <div class="stat-box">
                <h3><?= count($users) ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-box">
                <h3><?= count(array_filter($users, fn($u) => $u['role'] === 'owner')) ?></h3>
                <p>Dog Owners</p>
            </div>
            <div class="stat-box">
                <h3><?= count(array_filter($users, fn($u) => $u['role'] === 'sitter')) ?></h3>
                <p>Pet Sitters</p>
            </div>
            <div class="stat-box">
                <h3><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></h3>
                <p>Administrators</p>
            </div>
        </div>
        
        <h3>User Management</h3>
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Current Role</th>
                    <th>Change Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $usr): ?>
                <tr>
                    <td><?= htmlspecialchars($usr['id']) ?></td>
                    <td><?= htmlspecialchars($usr['username']) ?></td>
                    <td><?= htmlspecialchars($usr['email']) ?></td>
                    <td>
                        <span class="role-badge role-<?= htmlspecialchars($usr['role']) ?>">
                            <?= htmlspecialchars($usr['role']) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="change_role">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($usr['id']) ?>">
                            <select name="new_role" class="role-select">
                                <option value="owner" <?= $usr['role'] === 'owner' ? 'selected' : '' ?>>Owner</option>
                                <option value="sitter" <?= $usr['role'] === 'sitter' ? 'selected' : '' ?>>Sitter</option>
                                <option value="admin" <?= $usr['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                    </td>
                    <td>
                            <button type="submit" class="btn-update">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>