<?php
session_start();
require_once 'config/database.php';
require_once 'classes/MediaContent.php';
require_once 'classes/CoinsSystem.php';

$database = new Database();
$db = $database->getConnection();
$mediaContent = new MediaContent($db);

$page = (int)($_GET['page'] ?? 1);
$content_type = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$filters = [];
if($content_type) $filters['content_type'] = $content_type;

$content_items = $mediaContent->browseContent($filters, $page, 24);

// Get user balance if logged in
$coin_balance = 0;
if(isset($_SESSION['user_id'])) {
    $coinsSystem = new CoinsSystem($db);
    $coin_balance = $coinsSystem->getBalance($_SESSION['user_id']);
}

include 'views/header.php';
?>

<link rel="stylesheet" href="/assets/css/dark-blue-theme.css">
<link rel="stylesheet" href="/assets/css/light-theme.css">

<style>
.marketplace-hero {
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.95));
    backdrop-filter: blur(20px);
    border-bottom: 2px solid rgba(66, 103, 245, 0.2);
    padding: 3rem 0 2rem;
    position: relative;
    overflow: hidden;
}

.marketplace-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(66, 103, 245, 0.15), transparent);
    border-radius: 50%;
}

.coin-balance-card {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 15px;
    padding: 1rem 1.5rem;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: 0 8px 24px rgba(251, 191, 36, 0.4);
}

.content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.content-card {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s;
    cursor: pointer;
    position: relative;
}

.content-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 60px rgba(66, 103, 245, 0.3);
    border-color: var(--primary-blue);
}

.content-thumbnail-wrapper {
    position: relative;
    width: 100%;
    height: 350px;
    overflow: hidden;
}

.content-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.content-thumbnail.blurred {
    filter: blur(20px);
}

.lock-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
}

.price-tag {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-weight: 800;
    font-size: 1.2rem;
    box-shadow: 0 4px 15px rgba(251, 191, 36, 0.5);
}

.creator-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    z-index: 2;
}

.creator-avatar-small {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4267F5, #1D9BF0);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 0.8rem;
}

.content-info {
    padding: 1.5rem;
}

.content-title {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--text-white);
    margin-bottom: 0.75rem;
}

.content-stats {
    display: flex;
    gap: 1.5rem;
    color: var(--text-gray);
    font-size: 0.9rem;
    margin-top: 1rem;
}

.filter-bar {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin: 2rem 0;
}

.filter-btn {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    color: var(--text-white);
    text-decoration: none;
    transition: all 0.3s;
    font-weight: 600;
}

.filter-btn:hover,
.filter-btn.active {
    background: var(--primary-blue);
    border-color: var(--primary-blue);
    color: white;
    transform: translateY(-2px);
}
</style>

<div class="marketplace-hero">
    <div class="container-modern">
        <div class="hero-content text-center">
            <h1 class="city-title" style="color: white;">
                💎 Content Marketplace
            </h1>
            <p class="city-subtitle" style="color: rgba(255, 255, 255, 0.9);">
                Discover exclusive content from creators
            </p>
            
            <?php if(isset($_SESSION['user_id'])): ?>
            <div style="margin-top: 2rem;">
                <div class="coin-balance-card">
                    <span style="font-size: 1.5rem;">💰</span>
                    <div>
                        <div style="font-size: 0.8rem; opacity: 0.9;">Your Balance</div>
                        <div style="font-size: 1.3rem; font-weight: 800;">
                            <?php echo number_format($coin_balance); ?> coins
                        </div>
                    </div>
                    <a href="/buy-coins.php" class="btn" style="background: white; color: #f59e0b; padding: 0.5rem 1rem; border-radius: 10px; font-weight: 700;">
                        Buy Coins
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container-modern" style="padding: 3rem 1.5rem;">
    
    <!-- Filters -->
    <div class="filter-bar">
        <a href="marketplace.php" class="filter-btn <?php echo empty($content_type) ? 'active' : ''; ?>">
            All Content
        </a>
        <a href="marketplace.php?type=photo" class="filter-btn <?php echo $content_type === 'photo' ? 'active' : ''; ?>">
            📸 Photos
        </a>
        <a href="marketplace.php?type=photo_set" class="filter-btn <?php echo $content_type === 'photo_set' ? 'active' : ''; ?>">
            📷 Photo Sets
        </a>
        <a href="marketplace.php?type=video" class="filter-btn <?php echo $content_type === 'video' ? 'active' : ''; ?>">
            🎥 Videos
        </a>
        <a href="marketplace.php?type=video_set" class="filter-btn <?php echo $content_type === 'video_set' ? 'active' : ''; ?>">
            🎬 Video Sets
        </a>
    </div>
    
    <?php if(empty($content_items)): ?>
    <div class="empty-state" style="background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 20px; padding: 4rem 2rem; text-align: center;">
        <div style="font-size: 5rem; margin-bottom: 1rem; opacity: 0.3;">📸</div>
        <h3>No content available</h3>
        <p style="color: var(--text-gray);">Be the first to share content!</p>
        <a href="/become-creator.php" class="btn-primary" style="margin-top: 1rem;">
            Become a Creator
        </a>
    </div>
    <?php else: ?>
    
    <!-- Content Grid -->
    <div class="content-grid">
        <?php foreach($content_items as $item): ?>
        <div class="content-card" onclick="window.location.href='/view-content.php?id=<?php echo $item['id']; ?>'">
            
            <div class="content-thumbnail-wrapper">
                <!-- Creator Badge -->
                <div class="creator-badge">
                    <div class="creator-avatar-small">
                        <?php echo strtoupper(substr($item['creator_name'], 0, 1)); ?>
                    </div>
                    <span style="color: white; font-weight: 600; font-size: 0.9rem;">
                        <?php echo htmlspecialchars($item['creator_name']); ?>
                    </span>
                    <?php if($item['is_verified']): ?>
                    <span style="color: #3b82f6;">✓</span>
                    <?php endif; ?>
                </div>
                
                <?php if($item['thumbnail']): ?>
                <img src="<?php echo htmlspecialchars($item['thumbnail']); ?>" 
                     class="content-thumbnail <?php echo $item['blur_preview'] ? 'blurred' : ''; ?>" 
                     alt="Content">
                <?php else: ?>
                <div class="content-thumbnail" style="background: linear-gradient(135deg, #4267F5, #1D9BF0); display: flex; align-items: center; justify-content: center; font-size: 5rem;">
                    <?php echo $item['content_type'] == 'video' ? '🎥' : '📷'; ?>
                </div>
                <?php endif; ?>
                
                <?php if(!$item['is_free']): ?>
                <div class="lock-overlay">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
                    <div class="price-tag">
                        💰 <?php echo number_format($item['price']); ?> coins
                    </div>
                </div>
                <?php else: ?>
                <div style="position: absolute; top: 1rem; right: 1rem; background: var(--success-green); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 700;">
                    FREE
                </div>
                <?php endif; ?>
            </div>
            
            <div class="content-info">
                <div class="content-title">
                    <?php echo htmlspecialchars($item['title']); ?>
                </div>
                
                <div class="content-stats">
                    <span>👁️ <?php echo number_format($item['view_count']); ?></span>
                    <span>❤️ <?php echo number_format($item['like_count']); ?></span>
                    <span>🛒 <?php echo number_format($item['purchase_count']); ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
</div>

<?php include 'views/footer.php'; ?>