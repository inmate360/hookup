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

// Check message limits if viewing someone else's profile
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

<style>
.profile-page {
    padding: 2rem 0;
}

.profile-header {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border-color);
}

.profile-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 5rem;
    margin: 0 auto 1.5rem;
    border: 4px solid var(--border-color);
}

.profile-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .profile-avatar {
        width: 120px;
        height: 120px;
        font-size: 4rem;
    }
    
    .profile-actions {
        flex-direction: column;
    }
}
</style>

<div class="profile-page">
    <div class="container-narrow">
        <div class="profile-header">
            <div class="profile-avatar">
                👤
            </div>
            
            <div style="text-align: center;">
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($profile_data['username']); ?>
                </h1>
                
                <div style="color: var(--text-gray); margin-bottom: 1.5rem;">
                    <span>Member since <?php echo date('M Y', strtotime($profile_data['created_at'])); ?></span>
                    <?php if($profile_data['is_online']): ?>
                    <span style="color: var(--success-green); margin-left: 1rem;">● Online now</span>
                    <?php endif; ?>
                </div>
                
                <?php if($profile_data['bio']): ?>
                <p style="color: var(--text-gray); line-height: 1.8; max-width: 600px; margin: 0 auto 2rem;">
                    <?php echo nl2br(htmlspecialchars($profile_data['bio'])); ?>
                </p>
                <?php endif; ?>
                
                <div class="profile-actions">
                    <?php if($is_own_profile): ?>
                        <a href="edit-profile.php" class="btn-primary">
                            ✏️ Edit Profile
                        </a>
                        <a href="my-listings.php" class="btn-secondary">
                            📝 My Ads
                        </a>
                        <a href="settings.php" class="btn-secondary">
                            ⚙️ Settings
                        </a>
                    <?php else: ?>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <?php if($can_message): ?>
                                <a href="messages-compose.php?to=<?php echo $profile_user_id; ?>" class="btn-primary">
                                    💬 Send Message
                                    <?php if(!$message_limit_info['is_premium']): ?>
                                    <span style="font-size: 0.85rem; opacity: 0.9;">
                                        (<?php echo $message_limit_info['messages_left']; ?> left today)
                                    </span>
                                    <?php endif; ?>
                                </a>
                            <?php else: ?>
                                <button onclick="showUpgradeModal()" class="btn-primary">
                                    💬 Send Message (Limit Reached)
                                </button>
                            <?php endif; ?>
                            <button class="btn-secondary" onclick="showReportModal()">
                                🚨 Report User
                            </button>
                        <?php else: ?>
                            <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-primary">
                                Login to Message
                            </a>
                            <a href="register.php" class="btn-secondary">
                                Sign Up Free
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if(count($user_listings) > 0): ?>
        <div class="card">
            <h2 style="margin-bottom: 1.5rem;">
                <?php echo $is_own_profile ? 'My Recent Ads' : htmlspecialchars($profile_data['username']) . "'s Ads"; ?>
            </h2>
            
            <div style="display: grid; gap: 1.5rem;">
                <?php foreach($user_listings as $listing): ?>
                <a href="listing.php?id=<?php echo $listing['id']; ?>" 
                   style="text-decoration: none; display: flex; gap: 1rem; padding: 1rem; background: rgba(66, 103, 245, 0.05); border-radius: 10px; transition: all 0.3s;"
                   onmouseover="this.style.background='rgba(66, 103, 245, 0.1)'"
                   onmouseout="this.style.background='rgba(66, 103, 245, 0.05)'">
                    <div style="flex: 1;">
                        <h3 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($listing['title']); ?>
                        </h3>
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                            <span style="background: rgba(66, 103, 245, 0.2); padding: 0.2rem 0.6rem; border-radius: 8px; font-size: 0.8rem; color: var(--primary-blue);">
                                <?php echo htmlspecialchars($listing['category_name']); ?>
                            </span>
                            <span style="background: rgba(29, 155, 240, 0.2); padding: 0.2rem 0.6rem; border-radius: 8px; font-size: 0.8rem; color: var(--info-cyan);">
                                📍 <?php echo htmlspecialchars($listing['city_name']); ?>
                            </span>
                        </div>
                        <p style="color: var(--text-gray); font-size: 0.9rem;">
                            <?php echo htmlspecialchars(substr($listing['description'], 0, 150)); ?>...
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; color: var(--text-gray); font-size: 0.85rem;">
                        <?php echo date('M j', strtotime($listing['created_at'])); ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Upgrade Modal -->
<div id="upgradeModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
    <div class="card" style="max-width: 500px; width: 90%; margin: 2rem;">
        <div style="text-align: center;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">💎</div>
            <h3 style="margin-bottom: 1rem;">Upgrade to Premium</h3>
            <p style="color: var(--text-gray); margin-bottom: 2rem; line-height: 1.8;">
                You've reached your daily message limit (<?php echo $message_limit_info['limit'] ?? 5; ?> messages). 
                Upgrade to premium for unlimited messaging!
            </p>
            
            <div style="background: rgba(66, 103, 245, 0.1); padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; text-align: left;">
                <strong style="color: var(--primary-blue);">Premium Benefits:</strong>
                <ul style="color: var(--text-gray); line-height: 2; margin-top: 0.5rem;">
                    <li>✅ Unlimited messages</li>
                    <li>✅ Featured ads</li>
                    <li>✅ Incognito mode</li>
                    <li>✅ Advanced search</li>
                    <li>✅ Priority support</li>
                </ul>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <button onclick="closeUpgradeModal()" class="btn-secondary btn-block">Maybe Later</button>
                <a href="membership.php" class="btn-primary btn-block">Upgrade Now</a>
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div id="reportModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
    <div class="card" style="max-width: 500px; width: 90%; margin: 2rem;">
        <h3 style="margin-bottom: 1rem;">🚨 Report User</h3>
        <form method="POST" action="report.php">
            <input type="hidden" name="reported_type" value="user">
            <input type="hidden" name="reported_id" value="<?php echo $profile_user_id; ?>">
            
            <div class="form-group">
                <label>Reason for Report</label>
                <select name="reason" required>
                    <option value="">Select a reason...</option>
                    <option value="spam">Spam or fake profile</option>
                    <option value="inappropriate">Inappropriate behavior</option>
                    <option value="scam">Scam or fraud</option>
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
function showUpgradeModal() {
    document.getElementById('upgradeModal').style.display = 'flex';
}

function closeUpgradeModal() {
    document.getElementById('upgradeModal').style.display = 'none';
}

function showReportModal() {
    document.getElementById('reportModal').style.display = 'flex';
}

function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}

// Close modals when clicking outside
document.getElementById('upgradeModal')?.addEventListener('click', function(e) {
    if(e.target === this) closeUpgradeModal();
});

document.getElementById('reportModal')?.addEventListener('click', function(e) {
    if(e.target === this) closeReportModal();
});
</script>

<?php include 'views/footer.php'; ?>