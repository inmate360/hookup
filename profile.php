<?php
session_start();
require_once 'config/database.php';
require_once 'classes/UserProfile.php';
require_once 'classes/MessageLimits.php';

if(!isset($_GET['id'])) {
    if(isset($_SESSION['user_id'])) {
        header('Location: profile.php?id=' . $_SESSION['user_id']);
    } else {
        header('Location: login.php');
    }
    exit();
}

$database = new Database();
$db = $database->getConnection();
$userProfile = new UserProfile($db);

$profile_user_id = $_GET['id'];
$profile_data = $userProfile->getProfile($profile_user_id);

if(!$profile_data) {
    header('Location: choose-location.php');
    exit();
}

$is_own_profile = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_user_id;

// Check message limits
$can_message = false;
$message_limit_info = null;
if(isset($_SESSION['user_id']) && !$is_own_profile) {
    $messageLimits = new MessageLimits($db);
    $message_limit_info = $messageLimits->canSendMessage($_SESSION['user_id']);
    $can_message = $message_limit_info['can_send'];
}

// Get user's listings
$query = "SELECT l.*, c.name as category_name, ct.name as city_name, s.abbreviation as state_abbr
          FROM listings l
          LEFT JOIN categories c ON l.category_id = c.id
          LEFT JOIN cities ct ON l.city_id = ct.id
          LEFT JOIN states s ON ct.state_id = s.id
          WHERE l.user_id = :user_id AND l.status = 'active'
          ORDER BY l.created_at DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $profile_user_id);
$stmt->execute();
$user_listings = $stmt->fetchAll();

include 'views/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
.profile-wrapper {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

.profile-cover {
    height: 300px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24px 24px 0 0;
    position: relative;
    overflow: hidden;
}

.profile-cover::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('/assets/images/pattern.svg') repeat;
    opacity: 0.1;
}

.profile-main-card {
    background: var(--card-bg, #fff);
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    margin-top: -100px;
    position: relative;
    z-index: 10;
}

.profile-header {
    padding: 0 2rem 2rem;
    text-align: center;
}

.profile-avatar-wrapper {
    margin-top: -80px;
    display: inline-block;
    position: relative;
}

.profile-avatar {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    border: 6px solid var(--card-bg, #fff);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    object-fit: cover;
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.profile-badges {
    position: absolute;
    bottom: 5px;
    right: 5px;
    display: flex;
    gap: 5px;
}

.profile-badge {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border: 3px solid white;
}

.profile-username {
    font-size: 2rem;
    font-weight: 800;
    margin: 1rem 0 0.5rem;
}

.profile-meta {
    color: var(--text-gray, #6b7280);
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.profile-meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.online-indicator {
    width: 10px;
    height: 10px;
    background: #10b981;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.profile-bio {
    max-width: 600px;
    margin: 0 auto 2rem;
    line-height: 1.8;
    color: var(--text-gray, #6b7280);
}

.profile-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    padding-bottom: 2rem;
}

.profile-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    padding: 2rem;
    background: #f8fafc;
    border-radius: 16px;
    margin: 2rem;
}

.stat-card {
    text-align: center;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.stat-value {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.stat-label {
    color: var(--text-gray, #6b7280);
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.listing-card {
    background: var(--card-bg, #fff);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 2px solid #e2e8f0;
    transition: all 0.3s;
}

.listing-card:hover {
    border-color: #667eea;
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(102,126,234,0.15);
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

@media (max-width: 768px) {
    .profile-cover { height: 200px; }
    .profile-avatar { width: 120px; height: 120px; }
    .profile-username { font-size: 1.5rem; }
    .profile-meta { flex-direction: column; gap: 0.5rem; }
}
</style>

<div class="profile-wrapper">
    <div class="profile-cover"></div>
    
    <div class="profile-main-card">
        <div class="profile-header">
            <div class="profile-avatar-wrapper">
                <img src="<?php echo htmlspecialchars($profile_data['avatar'] ?? '/assets/images/default-avatar.png'); ?>" 
                     alt="Profile" 
                     class="profile-avatar">
                <div class="profile-badges">
                    <?php if($profile_data['is_admin'] ?? false): ?>
                        <span class="profile-badge" style="background:#ef4444" title="Admin">🛡️</span>
                    <?php endif; ?>
                    <?php if($profile_data['verified'] ?? false): ?>
                        <span class="profile-badge" style="background:#3b82f6" title="Verified"><i class="bi bi-check-lg"></i></span>
                    <?php endif; ?>
                    <?php if($profile_data['creator'] ?? false): ?>
                        <span class="profile-badge" style="background:#f59e0b" title="Creator">⭐</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <h1 class="profile-username"><?php echo htmlspecialchars($profile_data['username']); ?></h1>
            
            <div class="profile-meta">
                <div class="profile-meta-item">
                    <i class="bi bi-calendar3"></i>
                    <span>Joined <?php echo date('M Y', strtotime($profile_data['created_at'])); ?></span>
                </div>
                <?php if($profile_data['is_online'] ?? false): ?>
                <div class="profile-meta-item">
                    <div class="online-indicator"></div>
                    <span class="text-success fw-bold">Online now</span>
                </div>
                <?php endif; ?>
                <?php if($profile_data['occupation'] ?? false): ?>
                <div class="profile-meta-item">
                    <i class="bi bi-briefcase"></i>
                    <span><?php echo htmlspecialchars($profile_data['occupation']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if($profile_data['bio']): ?>
            <p class="profile-bio"><?php echo nl2br(htmlspecialchars($profile_data['bio'])); ?></p>
            <?php endif; ?>
            
            <div class="profile-actions">
                <?php if($is_own_profile): ?>
                    <a href="edit-profile.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-pencil"></i> Edit Profile
                    </a>
                    <a href="my-listings.php" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-list-check"></i> My Ads
                    </a>
                    <a href="settings.php" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                <?php else: ?>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($can_message): ?>
                            <a href="messages-compose.php?to=<?php echo $profile_user_id; ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-chat-dots"></i> Send Message
                            </a>
                        <?php else: ?>
                            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#upgradeModal">
                                <i class="bi bi-chat-dots"></i> Send Message
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reportModal">
                            <i class="bi bi-flag"></i> Report
                        </button>
                    <?php else: ?>
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login to Message
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if($profile_data['interests'] || $profile_data['languages'] || $profile_data['occupation']): ?>
        <div class="profile-stats">
            <?php if($profile_data['occupation']): ?>
            <div class="stat-card">
                <div class="stat-value"><i class="bi bi-briefcase"></i></div>
                <div class="stat-label">Occupation</div>
                <div class="fw-bold mt-2"><?php echo htmlspecialchars($profile_data['occupation']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if($profile_data['languages']): ?>
            <div class="stat-card">
                <div class="stat-value"><i class="bi bi-globe"></i></div>
                <div class="stat-label">Languages</div>
                <div class="fw-bold mt-2"><?php echo htmlspecialchars(is_array($profile_data['languages']) ? implode(', ', $profile_data['languages']) : $profile_data['languages']); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo count($user_listings); ?></div>
                <div class="stat-label">Active Listings</div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if(count($user_listings) > 0): ?>
        <div style="padding: 2rem;">
            <h2 class="section-title">
                <i class="bi bi-grid-3x3"></i>
                <?php echo $is_own_profile ? 'My Listings' : 'Listings'; ?>
            </h2>
            
            <?php foreach($user_listings as $listing): ?>
            <a href="listing.php?id=<?php echo $listing['id']; ?>" class="listing-card d-block text-decoration-none">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h3 class="h5 mb-2 text-primary"><?php echo htmlspecialchars($listing['title']); ?></h3>
                        <div class="d-flex gap-2 mb-2 flex-wrap">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($listing['category_name']); ?></span>
                            <span class="badge bg-info"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($listing['city_name']); ?></span>
                        </div>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars(substr($listing['description'], 0, 150)); ?>...</p>
                    </div>
                    <div class="text-muted small">
                        <?php echo date('M j', strtotime($listing['created_at'])); ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Upgrade Modal -->
<div class="modal fade" id="upgradeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">💎 Upgrade to Premium</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <p class="lead mb-4">You've reached your daily message limit. Upgrade for unlimited messaging!</p>
                <div class="bg-light p-4 rounded-3 mb-4">
                    <h6 class="fw-bold mb-3">Premium Benefits</h6>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> Unlimited messages</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> Featured listings</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> Priority support</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> Advanced search</li>
                    </ul>
                </div>
                <a href="membership.php" class="btn btn-primary btn-lg w-100">Upgrade Now</a>
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">🚨 Report User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="report.php">
                    <input type="hidden" name="reported_type" value="user">
                    <input type="hidden" name="reported_id" value="<?php echo $profile_user_id; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason</label>
                        <select name="reason" class="form-select" required>
                            <option value="">Select reason...</option>
                            <option value="spam">Spam or fake profile</option>
                            <option value="inappropriate">Inappropriate behavior</option>
                            <option value="scam">Scam or fraud</option>
                            <option value="harassment">Harassment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Details (optional)</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Provide additional information..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-danger w-100">Submit Report</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'views/footer.php'; ?>