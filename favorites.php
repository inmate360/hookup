<?php
session_start();
require_once 'config/database.php';
require_once 'includes/maintenance_check.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check for maintenance mode
if(checkMaintenanceMode($db)) {
    header('Location: maintenance.php');
    exit();
}

// Check if favorites table exists, if not create it
try {
    $check_query = "SHOW TABLES LIKE 'favorites'";
    $stmt = $db->prepare($check_query);
    $stmt->execute();
    
    if($stmt->rowCount() == 0) {
        // Create favorites table
        $create_table = "CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            listing_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
            UNIQUE KEY unique_favorite (user_id, listing_id),
            INDEX idx_user_favorites (user_id, created_at DESC),
            INDEX idx_listing_favorites (listing_id)
        )";
        $db->exec($create_table);
    }
} catch(PDOException $e) {
    error_log("Error checking/creating favorites table: " . $e->getMessage());
}

// Get user's favorites
$favorites = [];
try {
    $query = "SELECT l.*, u.username, c.name as category_name, ct.name as city_name, s.abbreviation as state_abbr,
              f.created_at as favorited_at
              FROM favorites f
              LEFT JOIN listings l ON f.listing_id = l.id
              LEFT JOIN users u ON l.user_id = u.id
              LEFT JOIN categories c ON l.category_id = c.id
              LEFT JOIN cities ct ON l.city_id = ct.id
              LEFT JOIN states s ON ct.state_id = s.id
              WHERE f.user_id = :user_id AND l.status = 'active' AND l.id IS NOT NULL
              ORDER BY f.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $favorites = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching favorites: " . $e->getMessage());
    $favorites = [];
}

include 'views/header.php';
?>

<div class="page-content">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1 style="font-size: 1.8rem;">⭐ My Favorites</h1>
            <?php if(count($favorites) > 0): ?>
            <span style="color: var(--text-gray); font-size: 0.9rem;">
                <?php echo count($favorites); ?> saved
            </span>
            <?php endif; ?>
        </div>
        
        <?php if(count($favorites) > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
            <?php foreach($favorites as $listing): ?>
            <div class="card" style="position: relative;">
                <div style="width: 100%; height: 200px; background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue)); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <span style="font-size: 4rem;">📋</span>
                </div>
                
                <h3 style="margin-bottom: 0.5rem; color: var(--text-white);">
                    <?php echo htmlspecialchars($listing['title']); ?>
                </h3>
                
                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
                    <?php if($listing['category_name']): ?>
                    <span style="background: rgba(66, 103, 245, 0.2); padding: 0.3rem 0.7rem; border-radius: 10px; font-size: 0.8rem; color: var(--primary-blue);">
                        <?php echo htmlspecialchars($listing['category_name']); ?>
                    </span>
                    <?php endif; ?>
                    <?php if($listing['city_name']): ?>
                    <span style="background: rgba(29, 155, 240, 0.2); padding: 0.3rem 0.7rem; border-radius: 10px; font-size: 0.8rem; color: var(--info-cyan);">
                        📍 <?php echo htmlspecialchars($listing['city_name']); ?>, <?php echo $listing['state_abbr']; ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <p style="color: var(--text-gray); font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars(substr($listing['description'], 0, 120)); ?><?php echo strlen($listing['description']) > 120 ? '...' : ''; ?>
                </p>
                
                <div style="padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <small style="color: var(--text-gray); display: block; margin-bottom: 0.5rem;">
                        Saved <?php echo date('M j, Y', strtotime($listing['favorited_at'])); ?>
                    </small>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <a href="listing.php?id=<?php echo $listing['id']; ?>" class="btn-primary btn-small">
                            View Ad
                        </a>
                        <form method="POST" action="toggle-favorite.php" style="margin: 0;">
                            <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                            <button type="submit" class="btn-secondary btn-small" style="width: 100%;">
                                Remove
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card" style="text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 5rem; margin-bottom: 1rem;">⭐</div>
            <h2 style="margin-bottom: 1rem;">No Favorites Yet</h2>
            <p style="color: var(--text-gray); margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto;">
                Save your favorite ads to easily find them later! Click the star icon on any ad to add it to your favorites.
            </p>
            <a href="<?php echo isset($_SESSION['current_city']) ? 'city.php?location=' . $_SESSION['current_city'] : 'choose-location.php'; ?>" 
               class="btn-primary">
                Browse Ads
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/footer.php'; ?>