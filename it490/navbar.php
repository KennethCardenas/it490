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
