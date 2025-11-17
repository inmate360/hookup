<?php
// Get unread messages count if user is logged in
$unread_messages = 0;
$unread_notifications = 0;
$incognito_active = false;

if(isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../classes/Message.php';
    require_once __DIR__ . '/../classes/SmartNotifications.php';
    require_once __DIR__ . '/../classes/IncognitoMode.php';
    
    $db_header = new Database();
    $conn_header = $db_header->getConnection();
    
    $msg_header = new Message($conn_header);
    $unread_messages = $msg_header->getTotalUnreadCount($_SESSION['user_id']);
    
    $notif_header = new SmartNotifications($conn_header);
    $unread_notifications = $notif_header->getUnreadCount($_SESSION['user_id']);
    
    $incognito_header = new IncognitoMode($conn_header);
    $incognito_active = $incognito_header->isActive($_SESSION['user_id']);
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="Turnpage - Local hookup classifieds. Post and browse personal ads in your area.">
    <meta name="theme-color" content="#4267F5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Turnpage">
    <title>Turnpage - Local Hookup Classifieds</title>
    <link rel="stylesheet" href="/assets/css/dark-blue-theme.css">
    <link rel="stylesheet" href="/assets/css/bottom-nav.css">
    <meta name="format-detection" content="telephone=no">
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
</head>
<body class="<?php echo isset($_SESSION['user_id']) ? 'has-bottom-nav' : ''; ?>">
    <nav class="navbar-blue">
        <div class="container">
            <div class="nav-brand">
                <div class="brand-icon-small">📋</div>
                <a href="<?php echo isset($_SESSION['current_city']) ? '/city.php?location=' . $_SESSION['current_city'] : '/choose-location.php'; ?>">
                    Turnpage
                </a>
            </div>
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-menu" id="navMenu" role="navigation">
                <?php if(isset($_SESSION['user_id'])): ?>
                    
                    <a href="/my-listings.php" class="<?php echo $current_page == 'my-listings' ? 'active' : ''; ?>">
                        📝 My Ads
                    </a>
                    
                    <a href="/messages.php" class="<?php echo $current_page == 'messages' ? 'active' : ''; ?>">
                        💬 Messages 
                        <?php if($unread_messages > 0): ?>
                            <span class="notification-badge"><?php echo $unread_messages; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="/notifications.php" class="<?php echo $current_page == 'notifications' ? 'active' : ''; ?>">
                        🔔
                        <?php if($unread_notifications > 0): ?>
                            <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="/profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="<?php echo $current_page == 'profile' ? 'active' : ''; ?>">
                        👤 Profile
                    </a>
                    
                    <a href="/settings.php" class="<?php echo $current_page == 'settings' ? 'active' : ''; ?>">
                        ⚙️ Settings
                    </a>
                    
                    <?php 
                    // Check if moderator
                    if(isset($conn_header)) {
                        require_once __DIR__ . '/../classes/Moderator.php';
                        $mod_check = new Moderator($conn_header);
                        if($mod_check->isModerator($_SESSION['user_id'])): 
                    ?>
                    <a href="/moderator/dashboard.php" style="color: var(--featured-gold); font-weight: bold;">
                        🛡️ Moderator
                    </a>
                    <?php 
                        endif;
                    }
                    ?>
                    
                    <a href="/logout.php">Logout</a>
                <?php else: ?>
                    <a href="/membership.php" class="<?php echo $current_page == 'membership' ? 'active' : ''; ?>">
                        💎 Premium
                    </a>
                    <a href="/login.php" class="<?php echo $current_page == 'login' ? 'active' : ''; ?>">
                        Login
                    </a>
                    <a href="/register.php" class="btn-primary btn-small">
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <?php if($incognito_active && isset($_SESSION['user_id'])): ?>
    <div class="incognito-indicator">
        🕶️ Incognito Mode Active
    </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['user_id'])): ?>
    <!-- Bottom Navigation Bar (Mobile Only) -->
    <nav class="bottom-nav">
        <div class="bottom-nav-container">
            <a href="<?php echo isset($_SESSION['current_city']) ? '/city.php?location=' . $_SESSION['current_city'] : '/choose-location.php'; ?>" 
               class="bottom-nav-item <?php echo in_array($current_page, ['index', 'city', 'choose-location']) ? 'active' : ''; ?>">
                <div class="bottom-nav-icon">🏠</div>
                <span class="bottom-nav-label">Home</span>
            </a>
            
            <a href="/messages.php" class="bottom-nav-item <?php echo $current_page == 'messages' ? 'active' : ''; ?>">
                <div class="bottom-nav-icon">💬</div>
                <span class="bottom-nav-label">Messages</span>
                <?php if($unread_messages > 0): ?>
                <span class="bottom-nav-badge"><?php echo $unread_messages; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="/my-listings.php" class="bottom-nav-item <?php echo $current_page == 'my-listings' ? 'active' : ''; ?>">
                <div class="bottom-nav-icon">📝</div>
                <span class="bottom-nav-label">My Posts</span>
            </a>
            
            <a href="/create-listing.php" class="bottom-nav-item <?php echo $current_page == 'create-listing' ? 'active' : ''; ?>">
                <div class="bottom-nav-icon">➕</div>
                <span class="bottom-nav-label">New Post</span>
            </a>
            
            <a href="/favorites.php" class="bottom-nav-item <?php echo $current_page == 'favorites' ? 'active' : ''; ?>">
                <div class="bottom-nav-icon">⭐</div>
                <span class="bottom-nav-label">Favorites</span>
            </a>
        </div>
    </nav>
    <?php endif; ?>
    
    <main>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    
    if(menuToggle && navMenu) {
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            if(navMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if(!navMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                menuToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        // Close menu when clicking a link
        navMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                menuToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
    }
});
</script>