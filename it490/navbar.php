<?php include_once("auth.php"); ?>
<?php if (!defined('NAVBAR_INCLUDED')) define('NAVBAR_INCLUDED', true); ?>
<nav class="navbar">
    <div class="navbar-toggle" id="mobile-menu">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </div>
    
    <div class="navbar-menu">
        <div class="navbar-links">
            <a href="/it490/pages/landing.php" class="nav-link">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            
            <?php if (isAuthenticated()): ?>
                <a href="/it490/pages/profile.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="/it490/pages/dogs.php" class="nav-link">
                    <i class="fas fa-paw"></i>
                    <span>Dogs</span>
                </a>
                <a href="/it490/pages/gamification.php" class="nav-link">
                    <i class="fas fa-star"></i>
                    <span>Points</span>
                </a>
                <a href="/it490/pages/invite.php" class="nav-link">
                    <i class="fas fa-envelope-open-text"></i>
                    <span>Invites</span>
                </a>
                <a href="/it490/pages/playdates.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Playdates</span>
                </a>
                <a href="/it490/pages/playdate_request.php" class="nav-link">
                    <i class="fas fa-paper-plane"></i>
                    <span>Playdate Requests</span>
                </a>
                <a href="/it490/pages/lost_dogs.php" class="nav-link">
                    <i class="fas fa-dog"></i>
                    <span>Lost Dogs</span>
                </a>
                <?php if (isAdmin()): ?>
                    <a href="/it490/pages/admin.php" class="nav-link">
                        <i class="fas fa-shield-alt"></i>
                        <span>Admin</span>
                    </a>
                <?php endif; ?>
                <a href="/it490/pages/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <a href="/it490/pages/register.php" class="nav-link">
                    <i class="fas fa-user-plus"></i>
                    <span>Register</span>
                </a>
                <a href="/it490/pages/login.php" class="nav-link">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    // Mobile menu toggle
    const mobileMenu = document.getElementById('mobile-menu');
    const navbarMenu = document.querySelector('.navbar-menu');

    mobileMenu?.addEventListener('click', function() {
        this.classList.toggle('active');
        navbarMenu.classList.toggle('active');
    });
</script>


