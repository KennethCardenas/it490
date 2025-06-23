<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
?>

<?php $title = "Dashboard"; include_once __DIR__ . "/../header.php"; ?>
    <div class="dashboard-container">
        <div class="welcome-card">
            <div class="welcome-header">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h1>Welcome back, <span class="username"><?= htmlspecialchars($user['username']) ?></span>!</h1>
            </div>
            
            <div class="welcome-content">
                <p class="welcome-message">You're now logged in to your account. Here's what's happening today:</p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-calendar-check"></i>
                        <h3>Recent Activity</h3>
                        <p>Check your latest actions</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-cog"></i>
                        <h3>Account Settings</h3>
                        <p>Update your profile</p>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <a href="profile.php" class="action-btn">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                    <a href="logout.php" class="action-btn logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php include_once __DIR__ . "/../footer.php"; ?>
