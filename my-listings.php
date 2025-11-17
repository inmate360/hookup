<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Listing.php';
require_once 'classes/ImageUpload.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$listing = new Listing($db);
$imageUpload = new ImageUpload($db);

$my_listings = $listing->getUserListings($_SESSION['user_id']);

include 'views/header.php';
?>

<div class="page-content">
    <div class="container">
        <h2>My Listings</h2>
        
        <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if(count($my_listings) > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
            <?php foreach($my_listings as $item): ?>
            <?php 
            // Get primary image
            $primary_img = $imageUpload->getPrimaryImage($item['id']);
            ?>
            <div class="card">
                <?php if($primary_img): ?>
                <img src="<?php echo htmlspecialchars($primary_img['file_path']); ?>" 
                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                     style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 1rem;">
                <?php endif; ?>
                
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 0.5rem;">
                            <a href="listing.php?id=<?php echo $item['id']; ?>" style="color: var(--text-white); text-decoration: none;">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </a>
                        </h3>
                        <span class="category-badge"><?php echo htmlspecialchars($item['category_name']); ?></span>
                    </div>
                    <span style="padding: 0.3rem 0.8rem; background: <?php 
                        echo $item['status'] == 'active' ? 'var(--success-green)' : 
                            ($item['status'] == 'expired' ? 'var(--warning-orange)' : 'var(--text-gray)'); 
                    ?>; color: white; border-radius: 12px; font-size: 0.8rem;">
                        <?php echo ucfirst($item['status']); ?>
                    </span>
                </div>
                
                <p style="color: var(--text-gray); margin-bottom: 1rem; line-height: 1.5;">
                    <?php echo htmlspecialchars(substr($item['description'], 0, 150)) . '...'; ?>
                </p>
                
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; font-size: 0.9rem; color: var(--text-gray);">
                    <span>📍 <?php echo htmlspecialchars($item['location']); ?></span>
                    <span>👁️ <?php echo $item['views']; ?> views</span>
                    <span>📅 <?php echo date('M j, Y', strtotime($item['created_at'])); ?></span>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;">
                    <a href="listing.php?id=<?php echo $item['id']; ?>" class="btn-secondary btn-small">View</a>
                    <a href="edit-listing.php?id=<?php echo $item['id']; ?>" class="btn-secondary btn-small">Edit</a>
                    <a href="manage-images.php?listing_id=<?php echo $item['id']; ?>" class="btn-secondary btn-small">Images</a>
                    <a href="delete-listing.php?id=<?php echo $item['id']; ?>" class="btn-danger btn-small" 
                       onclick="return confirm('Are you sure you want to delete this listing?')">Delete</a>
                </div>
                
                <?php if($item['status'] == 'active'): ?>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <a href="feature-ad.php?listing_id=<?php echo $item['id']; ?>" class="btn-primary btn-small" style="width: 100%;">
                        ⭐ Feature This Ad
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <h3 style="margin-bottom: 1rem;">You haven't posted any listings yet.</h3>
            <p style="color: var(--text-gray); margin-bottom: 2rem;">Create your first listing to connect with others!</p>
            <a href="choose-location.php" class="btn-primary">Create Your First Listing</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/footer.php'; ?>