<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/mq_client.php';

// Require admin or sitter role
requireAnyRole(['admin', 'sitter']);

$user = $_SESSION['user'];

// Get all users (this would be filtered based on role in a real app)
$payload = ['type' => 'get_all_users', 'admin_id' => $user['id']];
$usersResponse = sendMessage($payload);
$users = $usersResponse['users'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management | BarkBuddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/style.css">
    <style>
        .user-management {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #0077cc;
        }
        
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .user-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: transform 0.2s ease;
        }
        
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .user-info h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-info p {
            margin: 0.25rem 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .role-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../navbar.php'; ?>
    
    <div class="user-management">
        <div class="header">
            <h2><i class="fas fa-users"></i> User Management</h2>
            <p>Managing BarkBuddy users (<?= htmlspecialchars($user['role']) ?> access)</p>
            <?php if (!isAdmin()): ?>
                <small style="color: #666;">Limited access - contact admin for full management features</small>
            <?php endif; ?>
        </div>
        
        <div class="stats-section">
            <p><strong>Total Users:</strong> <?= count($users) ?></p>
            <p><strong>Dog Owners:</strong> <?= count(array_filter($users, fn($u) => $u['role'] === 'owner')) ?></p>
            <p><strong>Pet Sitters:</strong> <?= count(array_filter($users, fn($u) => $u['role'] === 'sitter')) ?></p>
            <p><strong>Administrators:</strong> <?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></p>
        </div>
        
        <div class="users-grid">
            <?php foreach ($users as $usr): ?>
            <div class="user-card">
                <div class="user-info">
                    <h4>
                        <i class="fas fa-user"></i>
                        <?= htmlspecialchars($usr['username']) ?>
                    </h4>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($usr['email']) ?></p>
                    <p><i class="fas fa-id-badge"></i> ID: <?= htmlspecialchars($usr['id']) ?></p>
                </div>
                <div class="role-section">
                    <span class="role-badge role-<?= htmlspecialchars($usr['role']) ?>">
                        <?= ucfirst(htmlspecialchars($usr['role'])) ?>
                    </span>
                    <?php if ($usr['role'] === 'admin'): ?>
                        <small style="display: block; margin-top: 0.5rem; color: #d32f2f;">
                            <i class="fas fa-shield-alt"></i> Administrator privileges
                        </small>
                    <?php elseif ($usr['role'] === 'sitter'): ?>
                        <small style="display: block; margin-top: 0.5rem; color: #7b1fa2;">
                            <i class="fas fa-hands-helping"></i> Pet sitter services
                        </small>
                    <?php else: ?>
                        <small style="display: block; margin-top: 0.5rem; color: #1976d2;">
                            <i class="fas fa-heart"></i> Dog owner
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (isAdmin()): ?>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="/it490/pages/admin.php" class="action-btn">
                    <i class="fas fa-shield-alt"></i> Go to Admin Panel
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>