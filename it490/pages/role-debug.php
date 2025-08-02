<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/mq_client.php';

requireAuth();
$user = $_SESSION['user'];

$message = '';
$messageType = '';

// Handle role change for testing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_role'])) {
    $newRole = $_POST['new_role'];
    
    // For testing purposes, let's directly update the database
    // In production, this would go through proper admin controls
    require_once __DIR__ . '/../api/connect.php';
    
    $query = "UPDATE USERS SET role = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $newRole, $user['id']);
    
    if ($stmt->execute()) {
        $message = "Role updated! Please log out and log back in for changes to take effect.";
        $messageType = 'success';
    } else {
        $message = "Failed to update role: " . $conn->error;
        $messageType = 'error';
    }
}

// Get current role from database
require_once __DIR__ . '/../api/connect.php';
$query = "SELECT role FROM USERS WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$dbUser = $result->fetch_assoc();
$dbRole = $dbUser['role'] ?? 'NOT SET';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Role Debug | BarkBuddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/style.css">
    <style>
        .debug-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .role-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .role-form {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #e3f2fd;
            border-radius: 8px;
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
        
        .role-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 0.5rem;
        }
        
        .btn {
            background: #0077cc;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: #005aa3;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../navbar.php'; ?>
    
    <div class="debug-container">
        <h2><i class="fas fa-bug"></i> Role Debug Tool</h2>
        <p>This is a temporary tool to help you test the role-based permission system.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="role-info">
            <h3>Current Role Information:</h3>
            <p><strong>User ID:</strong> <?= htmlspecialchars($user['id']) ?></p>
            <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
            <p><strong>Session Role:</strong> 
                <span class="role-badge role-<?= htmlspecialchars($user['role'] ?? 'unknown') ?>">
                    <?= htmlspecialchars($user['role'] ?? 'NOT SET') ?>
                </span>
            </p>
            <p><strong>Database Role:</strong> 
                <span class="role-badge role-<?= htmlspecialchars($dbRole) ?>">
                    <?= htmlspecialchars($dbRole) ?>
                </span>
            </p>
            
            <?php if (($user['role'] ?? '') !== $dbRole): ?>
                <div class="warning">
                    <strong>⚠️ Mismatch detected!</strong> Your session role doesn't match the database. Please log out and log back in.
                </div>
            <?php endif; ?>
        </div>
        
        <div class="role-form">
            <h3>Change Role (Testing Only)</h3>
            <p><strong>Warning:</strong> This is for testing purposes. In production, only admins should change roles.</p>
            
            <form method="POST">
                <label for="new_role">Select New Role:</label>
                <select name="new_role" id="new_role" class="role-select" required>
                    <option value="">Choose a role...</option>
                    <option value="owner" <?= $dbRole === 'owner' ? 'selected' : '' ?>>Owner (Dog owner)</option>
                    <option value="sitter" <?= $dbRole === 'sitter' ? 'selected' : '' ?>>Sitter (Pet sitter)</option>
                    <option value="admin" <?= $dbRole === 'admin' ? 'selected' : '' ?>>Admin (Full access)</option>
                </select>
                <br><br>
                <button type="submit" class="btn">Update Role</button>
            </form>
        </div>
        
        <div style="margin-top: 2rem;">
            <h3>Test Pages:</h3>
            <ul>
                <li><a href="/it490/pages/dogs.php">Dogs Page</a> (Currently: All authenticated users)</li>
                <li><a href="/it490/pages/admin.php">Admin Panel</a> (Requires: Admin role)</li>
                <li><a href="/it490/pages/user-management.php">User Management</a> (Requires: Admin or Sitter)</li>
            </ul>
        </div>
        
        <div style="margin-top: 2rem; text-align: center;">
            <a href="/it490/pages/logout.php" class="btn" style="background: #dc3545;">
                <i class="fas fa-sign-out-alt"></i> Logout & Login Again
            </a>
        </div>
    </div>
</body>
</html>