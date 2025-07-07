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
                    <a href="landing.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                    
                    <?php if (isAuthenticated()): ?>
                        <?php if (isAdmin()): ?>
                        <a href="admin.php" class="nav-link">
                            <i class="fas fa-tools"></i>
                            <span>Admin</span>
                        </a>
                        <?php endif; ?>
                        <a href="profile.php" class="nav-link">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                        <a href="dogs.php" class="nav-link">
                            <i class="fas fa-dog"></i>
                            <span>Dogs</span>
                        </a>
                        <a href="sitters.php" class="nav-link">
                            <i class="fas fa-search"></i>
                            <span>Sitters</span>
                        </a>
                        <?php if (hasRole('sitter')): ?>
                        <a href="sitter_profile.php" class="nav-link">
                            <i class="fas fa-id-badge"></i>
                            <span>Sitter Profile</span>
                        </a>
                        <a href="active_dogs.php" class="nav-link">
                            <i class="fas fa-list"></i>
                            <span>My Dogs</span>
                        </a>
                        <?php endif; ?>
                        <?php if (hasRole('user')): ?>
                        <a href="owner-profile.php" class="nav-link">
                            <i class="fas fa-paw"></i> 
                            <span>Owner Profile</span>
                        </a>
                        <?php endif; ?>
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="nav-link">
                            <i class="fas fa-user-plus"></i>
                            <span>Register</span>
                        </a>
                        <a href="login.php" class="nav-link">
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
