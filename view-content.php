<?php
session_start();
require_once 'config/database.php';
require_once 'classes/MediaContent.php';
require_once 'classes/CoinsSystem.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$content_id = (int)($_GET['id'] ?? 0);

$database = new Database();
$db = $database->getConnection();
$mediaContent = new MediaContent($db);
$coinsSystem = new CoinsSystem($db);

$content = $mediaContent->getContent($content_id, $_SESSION['user_id']);

if(!$content) {
    header('Location: marketplace.php');
    exit();
}

$is_owner = $content['creator_id'] == $_SESSION['user_id'];
$has_access = $content['user_has_access'] || $is_owner || $content['is_free'];
$user_balance = $coinsSystem->getBalance($_SESSION['user_id']);

// Update view count
if($has_access && !$is_owner) {
    $query = "UPDATE media_content SET view_count = view_count + 1 WHERE id = :content_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':content_id', $content_id);
    $stmt->execute();
}

include 'views/header.php';
?>

<link rel="stylesheet" href="/assets/css/dark-blue-theme.css">
<link rel="stylesheet" href="/assets/css/light-theme.css">

<style>
.content-view-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 20px;
}

.content-header {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.media-container {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 2rem;
    position: relative;
}

.media-item {
    width: 100%;
    max-height: 80vh;
    object-fit: contain;
    background: #000;
}

.locked-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(20px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.unlock-card {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 20px;
    padding: 3rem;
    text-align: center;
    max-width: 500px;
    margin: 2rem;
}

.price-display {
    font-size: 4rem;
    font-weight: 800;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 1rem 0;
}

.media-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1rem;
}

.gallery-item {
    cursor: pointer;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid var(--border-color);
    transition: all 0.3s;
}

.gallery-item:hover {
    transform: scale(1.05);
    border-color: var(--primary-blue);
}

.gallery-item img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.content-stats {
    display: flex;
    gap: 2rem;
    justify-content: center;
    margin: 1.5rem 0;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-gray);
}

@media (max-width: 768px) {
    .unlock-card {
        padding: 2rem 1rem;
    }
    
    .price-display {
        font-size: 3rem;
    }
}
</style>

<div class="page-content">
    <div class="content-view-container">
        
        <!-- Header -->
        <div class="content-header">
            <div style="display: flex; justify-content: space-between; align-items: start; gap: 2rem; flex-wrap: wrap;">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <a href="profile-enhanced.php?id=<?php echo $content['creator_id']; ?>" style="text-decoration: none;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue)); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                👤
                            </div>
                        </a>
                        <div>
                            <a href="profile-enhanced.php?id=<?php echo $content['creator_id']; ?>" style="text-decoration: none;">
                                <strong style="color: var(--text-white); font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($content['creator_name']); ?>
                                </strong>
                            </a>
                            <div style="color: var(--text-gray); font-size: 0.9rem;">
                                <?php echo date('M j, Y', strtotime($content['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <h1 style="font-size: 2rem; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($content['title']); ?>
                    </h1>
                    
                    <?php if($content['description']): ?>
                    <p style="color: var(--text-gray); line-height: 1.8;">
                        <?php echo nl2br(htmlspecialchars($content['description'])); ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="content-stats">
                        <div class="stat-item">
                            <span style="font-size: 1.2rem;">👁️</span>
                            <span><?php echo number_format($content['view_count']); ?> views</span>
                        </div>
                        <div class="stat-item">
                            <span style="font-size: 1.2rem;">❤️</span>
                            <span><?php echo number_format($content['total_likes']); ?> likes</span>
                        </div>
                        <div class="stat-item">
                            <span style="font-size: 1.2rem;">🛒</span>
                            <span><?php echo number_format($content['total_purchases']); ?> purchases</span>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <?php if($content['is_free']): ?>
                    <div style="background: var(--success-green); color: white; padding: 1rem 2rem; border-radius: 20px; font-weight: 700; font-size: 1.2rem;">
                        🎉 FREE
                    </div>
                    <?php else: ?>
                    <div style="background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(245, 158, 11, 0.1)); border: 2px solid #fbbf24; padding: 1rem 2rem; border-radius: 20px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.25rem;">Price</div>
                        <div style="font-size: 2rem; font-weight: 800; color: #fbbf24;">
                            💰 <?php echo number_format($content['price']); ?>
                        </div>
                        <div style="color: var(--text-gray); font-size: 0.9rem;">coins</div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($has_access): ?>
                    <div style="margin-top: 1rem; color: var(--success-green); font-weight: 600;">
                        ✓ You have access
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Media Container -->
        <div class="media-container">
            <?php if($has_access): ?>
                <!-- Show full content -->
                <?php if(count($content['files']) == 1): ?>
                    <?php $file = $content['files'][0]; ?>
                    <?php 
                    $file_type = $file['file_type'] ?? '';
                    if(!empty($file_type) && strpos($file_type, 'image') !== false): 
                    ?>
                    <img src="<?php echo htmlspecialchars($file['file_path']); ?>" class="media-item" alt="Content">
                    <?php else: ?>
                    <video controls class="media-item">
                        <source src="<?php echo htmlspecialchars($file['file_path']); ?>" type="<?php echo htmlspecialchars($file_type); ?>">
                    </video>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Gallery -->
                    <div id="mainMedia" style="min-height: 500px; display: flex; align-items: center; justify-content: center; background: #000;">
                        <?php 
                        $first = $content['files'][0]; 
                        $first_type = $first['file_type'] ?? '';
                        if(!empty($first_type) && strpos($first_type, 'image') !== false): 
                        ?>
                        <img id="mainImage" src="<?php echo htmlspecialchars($first['file_path']); ?>" class="media-item" alt="Content">
                        <?php else: ?>
                        <video id="mainVideo" controls class="media-item">
                            <source src="<?php echo htmlspecialchars($first['file_path']); ?>" type="<?php echo htmlspecialchars($first_type); ?>">
                        </video>
                        <?php endif; ?>
                    </div>
                    
                    <div class="media-gallery">
                        <?php foreach($content['files'] as $index => $file): ?>
                        <?php 
                        $media_type = $file['file_type'] ?? '';
                        ?>
                        <div class="gallery-item" onclick="showMedia(<?php echo $index; ?>)">
                            <?php if(!empty($media_type) && strpos($media_type, 'image') !== false): ?>
                            <img src="<?php echo htmlspecialchars($file['file_path']); ?>" alt="Thumbnail">
                            <?php else: ?>
                            <div style="height: 200px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #4267F5, #1D9BF0); font-size: 3rem;">
                                🎥
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Locked content -->
                <?php if($content['thumbnail']): ?>
                <img src="<?php echo htmlspecialchars($content['thumbnail']); ?>" 
                     class="media-item" 
                     style="filter: blur(30px);" 
                     alt="Preview">
                <?php else: ?>
                <div style="height: 500px; background: linear-gradient(135deg, #4267F5, #1D9BF0);"></div>
                <?php endif; ?>
                
                <div class="locked-overlay">
                    <div class="unlock-card">
                        <div style="font-size: 5rem; margin-bottom: 1rem;">🔒</div>
                        <h2 style="margin-bottom: 1rem;">Unlock This Content</h2>
                        
                        <div class="price-display">
                            <?php echo number_format($content['price']); ?> coins
                        </div>
                        
                        <div style="color: var(--text-gray); margin-bottom: 2rem;">
                            Your balance: <strong style="color: var(--text-white);"><?php echo number_format($user_balance); ?> coins</strong>
                        </div>
                        
                        <?php if($user_balance >= $content['price']): ?>
                        <button onclick="purchaseContent(<?php echo $content_id; ?>)" class="btn-primary btn-block" style="font-size: 1.2rem; padding: 1rem 2rem; margin-bottom: 1rem;">
                            🔓 Unlock for <?php echo number_format($content['price']); ?> coins
                        </button>
                        <?php else: ?>
                        <div style="background: rgba(239, 68, 68, 0.2); border: 2px solid #ef4444; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; color: #ef4444;">
                            ⚠️ Insufficient coins
                        </div>
                        <a href="buy-coins.php" class="btn-primary btn-block" style="font-size: 1.2rem; padding: 1rem 2rem; margin-bottom: 1rem;">
                            💰 Buy More Coins
                        </a>
                        <?php endif; ?>
                        
                        <!-- MESSAGE CREATOR BUTTON -->
                        <a href="messages-compose.php?to=<?php echo $content['creator_id']; ?>" class="btn-secondary btn-block" style="font-size: 1rem; padding: 0.75rem 1.5rem; text-decoration: none; display: inline-block;">
                            💬 Message Creator
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Actions -->
        <?php if($has_access): ?>
        <div class="card">
            <div class="action-buttons">
                <button onclick="likeContent(<?php echo $content_id; ?>)" 
                        id="likeBtn"
                        class="btn-secondary"
                        style="<?php echo $content['user_liked'] ? 'background: var(--danger-red); color: white;' : ''; ?>">
                    <?php echo $content['user_liked'] ? '❤️' : '🤍'; ?> 
                    <?php echo $content['user_liked'] ? 'Liked' : 'Like'; ?>
                </button>
                
                <a href="profile-enhanced.php?id=<?php echo $content['creator_id']; ?>" class="btn-secondary">
                    👤 View Creator
                </a>
                
                <!-- MESSAGE CREATOR BUTTON -->
                <a href="messages-compose.php?to=<?php echo $content['creator_id']; ?>" class="btn-secondary">
                    💬 Message Creator
                </a>
                
                <button onclick="showTipModal()" class="btn-secondary">
                    💰 Send Tip
                </button>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- Tip Modal -->
<div id="tipModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center;">
    <div class="card" style="max-width: 500px; width: 90%; margin: 2rem;">
        <h3 style="margin-bottom: 1.5rem;">💰 Send a Tip</h3>
        <div class="form-group">
            <label>Amount (coins)</label>
            <input type="number" id="tipAmount" class="form-control" min="10" placeholder="Enter amount...">
        </div>
        <div class="form-group">
            <label>Message (optional)</label>
            <textarea id="tipMessage" class="form-control" rows="3"></textarea>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <button onclick="closeTipModal()" class="btn-secondary btn-block">Cancel</button>
            <button onclick="sendTip()" class="btn-primary btn-block">Send Tip</button>
        </div>
    </div>
</div>

<script>
const mediaFiles = <?php echo json_encode($content['files']); ?>;

function showMedia(index) {
    const mainMedia = document.getElementById('mainMedia');
    const file = mediaFiles[index];
    
    if(file.file_type && file.file_type.includes('image')) {
        mainMedia.innerHTML = `<img id="mainImage" src="${file.file_path}" class="media-item" alt="Content">`;
    } else {
        mainMedia.innerHTML = `<video id="mainVideo" controls class="media-item">
            <source src="${file.file_path}" type="${file.file_type || 'video/mp4'}">
        </video>`;
    }
}

function purchaseContent(contentId) {
    if(!confirm('Unlock this content for <?php echo number_format($content['price']); ?> coins?')) {
        return;
    }
    
    fetch('/api/purchase-content.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({content_id: contentId})
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            alert('✅ Content unlocked!');
            location.reload();
        } else {
            alert('❌ ' + (data.error || 'Purchase failed'));
        }
    });
}

function likeContent(contentId) {
    const btn = document.getElementById('likeBtn');
    const isLiked = btn.textContent.includes('Liked');
    
    fetch('/api/like-content.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            content_id: contentId,
            action: isLiked ? 'unlike' : 'like'
        })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            if(isLiked) {
                btn.innerHTML = '🤍 Like';
                btn.style.background = '';
                btn.style.color = '';
            } else {
                btn.innerHTML = '❤️ Liked';
                btn.style.background = 'var(--danger-red)';
                btn.style.color = 'white';
            }
        }
    });
}

function showTipModal() {
    document.getElementById('tipModal').style.display = 'flex';
}

function closeTipModal() {
    document.getElementById('tipModal').style.display = 'none';
}

function sendTip() {
    const amount = document.getElementById('tipAmount').value;
    const message = document.getElementById('tipMessage').value;
    
    if(!amount || amount < 10) {
        alert('Minimum tip is 10 coins');
        return;
    }
    
    fetch('/api/send-tip.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            to_user_id: <?php echo $content['creator_id']; ?>,
            content_id: <?php echo $content_id; ?>,
            amount: amount,
            message: message
        })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            alert('✅ Tip sent successfully!');
            closeTipModal();
        } else {
            alert('❌ ' + (data.error || 'Failed to send tip'));
        }
    });
}

document.getElementById('tipModal')?.addEventListener('click', function(e) {
    if(e.target === this) closeTipModal();
});
</script>

<?php include 'views/footer.php'; ?>