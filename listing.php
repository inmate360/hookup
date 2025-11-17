<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Listing.php';
require_once 'includes/maintenance_check.php';

// Create includes directory if it doesn't exist
if(!is_dir(__DIR__ . '/includes')) {
    mkdir(__DIR__ . '/includes', 0755, true);
}

// Include favorite helper if it exists
if(file_exists(__DIR__ . '/includes/favorite_helper.php')) {
    require_once 'includes/favorite_helper.php';
}

$database = new Database();
$db = $database->getConnection();

// Check for maintenance mode
if(checkMaintenanceMode($db)) {
    header('Location: maintenance.php');
    exit();
}

if(!isset($_GET['id'])) {
    header('Location: choose-location.php');
    exit();
}

$listing = new Listing($db);
$listing_data = $listing->getById($_GET['id']);

if(!$listing_data) {
    $_SESSION['error'] = 'Listing not found';
    header('Location: choose-location.php');
    exit();
}

// Check if user has favorited this listing
$is_favorited = false;
if(isset($_SESSION['user_id']) && function_exists('isListingFavorited')) {
    $is_favorited = isListingFavorited($db, $_SESSION['user_id'], $listing_data['id']);
}

// Get listing photos
$photos = [];
try {
    $query = "SELECT photo_url FROM listing_photos WHERE listing_id = :listing_id ORDER BY display_order ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':listing_id', $listing_data['id']);
    $stmt->execute();
    $photos = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching photos: " . $e->getMessage());
}

// Track view (if logged in and not viewing own listing)
if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $listing_data['user_id']) {
    try {
        // Create listing_views table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS listing_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            listing_id INT NOT NULL,
            viewer_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_view (listing_id, viewer_id),
            FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
            FOREIGN KEY (viewer_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $db->exec($create_table);
        
        $query = "INSERT INTO listing_views (listing_id, viewer_id, created_at) VALUES (:listing_id, :viewer_id, NOW())
                  ON DUPLICATE KEY UPDATE created_at = NOW()";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':listing_id', $listing_data['id']);
        $stmt->bindParam(':viewer_id', $_SESSION['user_id']);
        $stmt->execute();
    } catch(PDOException $e) {
        error_log("Error tracking view: " . $e->getMessage());
    }
}

include 'views/header.php';
?>

<style>
.listing-detail {
    padding: 2rem 0;
}

.listing-header {
    margin-bottom: 2rem;
}

.listing-gallery {
    margin-bottom: 2rem;
}

.main-photo {
    width: 100%;
    height: 500px;
    object-fit: cover;
    border-radius: 15px;
    margin-bottom: 1rem;
}

.photo-thumbnails {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.5rem;
}

.photo-thumbnail {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s;
}

.photo-thumbnail:hover,
.photo-thumbnail.active {
    border-color: var(--primary-blue);
    transform: scale(1.05);
}

.listing-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
}

.listing-actions {
    position: sticky;
    top: 80px;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

@media (max-width: 968px) {
    .listing-content {
        grid-template-columns: 1fr;
    }
    
    .listing-actions {
        position: static;
    }
    
    .main-photo {
        height: 300px;
    }
}
</style>

<div class="listing-detail">
    <div class="container">
        <div class="listing-header">
            <?php if(isset($listing_data['city_slug']) && isset($listing_data['category_slug'])): ?>
            <a href="browse.php?city=<?php echo htmlspecialchars($listing_data['city_slug']); ?>&category=<?php echo htmlspecialchars($listing_data['category_slug']); ?>" 
               style="color: var(--primary-blue); text-decoration: none; display: inline-block; margin-bottom: 1rem;">
                ← Back to <?php echo htmlspecialchars($listing_data['category_name'] ?? 'Listings'); ?>
            </a>
            <?php endif; ?>
            
            <?php if(isset($listing_data['is_featured']) && $listing_data['is_featured']): ?>
            <div style="margin-bottom: 1rem;">
                <span class="category-badge" style="background: linear-gradient(135deg, var(--featured-gold), var(--warning-orange));">
                    ⭐ Featured Ad
                </span>
            </div>
            <?php endif; ?>
            
            <h1 style="font-size: 2rem; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($listing_data['title']); ?>
            </h1>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <?php if(isset($listing_data['category_name'])): ?>
                <span style="background: rgba(66, 103, 245, 0.2); padding: 0.4rem 0.8rem; border-radius: 10px; color: var(--primary-blue);">
                    <?php echo htmlspecialchars($listing_data['category_name']); ?>
                </span>
                <?php endif; ?>
                
                <span style="color: var(--text-gray);">
                    📍 <?php echo htmlspecialchars($listing_data['city_name'] ?? 'Unknown City'); ?><?php echo isset($listing_data['state_abbr']) ? ', ' . htmlspecialchars($listing_data['state_abbr']) : ''; ?>
                </span>
                
                <span style="color: var(--text-gray);">
                    🕒 Posted <?php echo date('M j, Y', strtotime($listing_data['created_at'])); ?>
                </span>
            </div>
        </div>
        
        <div class="listing-content">
            <div>
                <?php if(count($photos) > 0): ?>
                <div class="listing-gallery">
                    <img src="<?php echo htmlspecialchars($photos[0]['photo_url']); ?>" 
                         alt="<?php echo htmlspecialchars($listing_data['title']); ?>"
                         class="main-photo"
                         id="mainPhoto">
                    
                    <?php if(count($photos) > 1): ?>
                    <div class="photo-thumbnails">
                        <?php foreach($photos as $index => $photo): ?>
                        <img src="<?php echo htmlspecialchars($photo['photo_url']); ?>" 
                             alt="Photo <?php echo $index + 1; ?>"
                             class="photo-thumbnail <?php echo $index == 0 ? 'active' : ''; ?>"
                             onclick="changeMainPhoto('<?php echo htmlspecialchars($photo['photo_url']); ?>', this)">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div style="width: 100%; height: 400px; background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue)); border-radius: 15px; display: flex; align-items: center; justify-content: center; margin-bottom: 2rem;">
                    <span style="font-size: 6rem;">📋</span>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <h2 style="margin-bottom: 1rem;">Description</h2>
                    <div style="color: var(--text-gray); line-height: 1.8; white-space: pre-wrap;">
                        <?php echo nl2br(htmlspecialchars($listing_data['description'])); ?>
                    </div>
                </div>
                
                <?php if(!empty($listing_data['phone']) || !empty($listing_data['email'])): ?>
                <div class="card">
                    <h2 style="margin-bottom: 1rem;">Contact Information</h2>
                    <?php if(!empty($listing_data['phone'])): ?>
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: var(--primary-blue);">📱 Phone:</strong>
                        <a href="tel:<?php echo htmlspecialchars($listing_data['phone']); ?>" 
                           style="color: var(--text-white); text-decoration: none; margin-left: 0.5rem;">
                            <?php echo htmlspecialchars($listing_data['phone']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($listing_data['email'])): ?>
                    <div>
                        <strong style="color: var(--primary-blue);">📧 Email:</strong>
                        <a href="mailto:<?php echo htmlspecialchars($listing_data['email']); ?>" 
                           style="color: var(--text-white); text-decoration: none; margin-left: 0.5rem;">
                            <?php echo htmlspecialchars($listing_data['email']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="listing-actions">
                <div class="card">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue)); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2.5rem;">
                            👤
                        </div>
                        <h3 style="margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($listing_data['username']); ?>
                        </h3>
                        <p style="color: var(--text-gray); font-size: 0.9rem;">
                            Member since <?php echo date('M Y', strtotime($listing_data['user_created_at'] ?? $listing_data['created_at'])); ?>
                        </p>
                    </div>
                    
                    <div class="action-buttons">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <?php if($_SESSION['user_id'] == $listing_data['user_id']): ?>
                            <!-- Own listing - show edit button -->
                            <a href="edit-listing.php?id=<?php echo $listing_data['id']; ?>" class="btn-primary btn-block">
                                ✏️ Edit Listing
                            </a>
                            <?php else: ?>
                            <!-- Someone else's listing - show message button -->
                            <a href="send-message.php?user_id=<?php echo $listing_data['user_id']; ?>&listing_id=<?php echo $listing_data['id']; ?>" 
                               class="btn-primary btn-block">
                                💬 Send Message
                            </a>
                            
                            <!-- Favorite button -->
                            <form method="POST" action="toggle-favorite.php" style="margin: 0;">
                                <input type="hidden" name="listing_id" value="<?php echo $listing_data['id']; ?>">
                                <button type="submit" class="btn-secondary btn-block">
                                    <?php echo $is_favorited ? '⭐ Saved' : '☆ Save'; ?>
                                </button>
                            </form>
                            
                            <a href="profile.php?id=<?php echo $listing_data['user_id']; ?>" class="btn-secondary btn-block">
                                👤 View Profile
                            </a>
                            
                            <!-- Report button -->
                            <button onclick="showReportModal()" class="btn-danger btn-block">
                                🚨 Report Ad
                            </button>
                            <?php endif; ?>
                        <?php else: ?>
                        <!-- Not logged in - prompt to login -->
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-primary btn-block">
                            Login to Message
                        </a>
                        <a href="register.php" class="btn-secondary btn-block">
                            Sign Up Free
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <h3 style="margin-bottom: 1rem;">Safety Tips</h3>
                    <ul style="color: var(--text-gray); font-size: 0.9rem; line-height: 1.8; margin-left: 1.5rem;">
                        <li>Meet in public places</li>
                        <li>Tell a friend where you're going</li>
                        <li>Never send money</li>
                        <li>Trust your instincts</li>
                        <li>Report suspicious activity</li>
                    </ul>
                    <a href="safety.php" style="color: var(--primary-blue); font-size: 0.9rem; display: block; margin-top: 1rem;">
                        Read full safety guidelines →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div id="reportModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
    <div class="card" style="max-width: 500px; width: 90%; margin: 2rem;">
        <h3 style="margin-bottom: 1rem;">🚨 Report This Ad</h3>
        <form method="POST" action="report.php">
            <input type="hidden" name="reported_type" value="listing">
            <input type="hidden" name="reported_id" value="<?php echo $listing_data['id']; ?>">
            
            <div class="form-group">
                <label>Reason for Report</label>
                <select name="reason" required>
                    <option value="">Select a reason...</option>
                    <option value="spam">Spam or misleading</option>
                    <option value="inappropriate">Inappropriate content</option>
                    <option value="scam">Scam or fraud</option>
                    <option value="illegal">Illegal activity</option>
                    <option value="harassment">Harassment</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Additional Details (optional)</label>
                <textarea name="description" rows="4" placeholder="Please provide more information..."></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <button type="button" class="btn-secondary btn-block" onclick="closeReportModal()">Cancel</button>
                <button type="submit" class="btn-danger btn-block">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
function changeMainPhoto(photoUrl, thumbnail) {
    document.getElementById('mainPhoto').src = photoUrl;
    
    // Update active thumbnail
    document.querySelectorAll('.photo-thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
    });
    thumbnail.classList.add('active');
}

function showReportModal() {
    document.getElementById('reportModal').style.display = 'flex';
}

function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('reportModal')?.addEventListener('click', function(e) {
    if(e.target === this) {
        closeReportModal();
    }
});
</script>

<?php include 'views/footer.php'; ?>