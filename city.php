<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Location.php';
require_once 'classes/Category.php';
require_once 'classes/Listing.php';
require_once 'includes/announcements.php';

if(!isset($_GET['location'])) {
    header('Location: choose-location.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$location = new Location($db);
$category = new Category($db);
$listing = new Listing($db);

$city = $location->getCityBySlug($_GET['location']);

if(!$city) {
    header('Location: choose-location.php');
    exit();
}

// Save to session
$_SESSION['current_city'] = $city['slug'];
$_SESSION['current_city_id'] = $city['id'];

// Get active announcements
$announcements = getActiveAnnouncements($db, 'homepage');

// Get categories
$categories = $category->getAll();

// Group categories
$connect_now = [];
$lets_date = [];

foreach($categories as $cat) {
    if(in_array($cat['id'], [1,2,3,4,5,6,7,8,9,10,11])) {
        $connect_now[] = $cat;
    } else {
        $lets_date[] = $cat;
    }
}

// Count total new posts in last 24 hours for this city
try {
    $query = "SELECT COUNT(*) as count FROM listings 
              WHERE city_id = :city_id 
              AND status = 'active' 
              AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':city_id', $city['id']);
    $stmt->execute();
    $new_posts_result = $stmt->fetch();
    $new_posts_count = $new_posts_result['count'] ?? 0;
} catch(PDOException $e) {
    $new_posts_count = 0;
    error_log("Error counting new posts: " . $e->getMessage());
}

include 'views/header.php';
?>

<div class="city-page">
    <div class="container-narrow">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="font-size: 3rem; margin-bottom: 0.5rem;">📋</div>
            <h1>Turnpage: <?php echo htmlspecialchars($city['name']); ?></h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Local Hookup Classifieds</p>
        </div>

        <?php 
        // Display announcements
        if(!empty($announcements)) {
            echo displayAnnouncements($announcements);
        }
        ?>

        <div class="categories-container">
            <!-- Connect Now Section -->
            <div class="category-section">
                <div class="section-header">
                    <span class="section-icon">🔥</span>
                    <h2>Connect Now</h2>
                    <span class="new-posts"><?php echo $new_posts_count; ?> new posts!</span>
                </div>
                <div class="category-list">
                    <?php foreach($connect_now as $cat): ?>
                    <a href="browse.php?city=<?php echo $city['slug']; ?>&category=<?php echo $cat['slug']; ?>" class="category-link">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Let's Date Section -->
            <div class="category-section">
                <div class="section-header">
                    <span class="section-icon">💝</span>
                    <h2>Casual Encounters</h2>
                </div>
                <div class="category-list">
                    <?php foreach($lets_date as $cat): ?>
                    <a href="browse.php?city=<?php echo $city['slug']; ?>&category=<?php echo $cat['slug']; ?>" class="category-link">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sponsored Ads Section -->
            <div class="category-section">
                <div class="section-header">
                    <span class="section-icon">📢</span>
                    <h2>Sponsored Ads</h2>
                </div>
                <div class="category-list">
                    <a href="#" class="category-link">
                        Adult Meetups <span class="ad-badge">Ad</span>
                    </a>
                    <a href="#" class="category-link">
                        Local Hookups <span class="ad-badge">Ad</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="location-actions" style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap;">
            <a href="choose-location.php" class="btn-secondary">
                Change Location
            </a>
            <?php if(isset($_SESSION['user_id'])): ?>
            <a href="create-listing.php" class="btn-primary">
                + Post Ad
            </a>
            <?php else: ?>
            <a href="login.php" class="btn-primary">
                Login to Post
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.announcements-container {
    margin-bottom: 2rem;
}

.announcement-item {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .announcement-item {
        padding: 1rem;
    }
    
    .announcement-item strong {
        font-size: 0.95rem;
    }
    
    .announcement-item p {
        font-size: 0.9rem;
    }
}
</style>

<?php include 'views/footer.php'; ?>