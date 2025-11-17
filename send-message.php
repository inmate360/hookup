<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Message.php';
require_once 'classes/MessageLimits.php';
require_once 'classes/SmartNotifications.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = new Message($db);
$messageLimits = new MessageLimits($db);
$notifications = new SmartNotifications($db);

$error = '';
$success = '';

// Get recipient info
$recipient_id = $_GET['user_id'] ?? null;
$listing_id = $_GET['listing_id'] ?? null;

if(!$recipient_id) {
    header('Location: choose-location.php');
    exit();
}

// Don't allow messaging yourself
if($recipient_id == $_SESSION['user_id']) {
    header('Location: choose-location.php');
    exit();
}

// Check message limits
$limit_check = $messageLimits->canSendMessage($_SESSION['user_id']);

// Get recipient details
$query = "SELECT id, username FROM users WHERE id = :id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $recipient_id);
$stmt->execute();
$recipient = $stmt->fetch();

if(!$recipient) {
    $_SESSION['error'] = 'User not found';
    header('Location: choose-location.php');
    exit();
}

// Get listing details if provided
$listing = null;
if($listing_id) {
    $query = "SELECT id, title FROM listings WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $listing_id);
    $stmt->execute();
    $listing = $stmt->fetch();
}

// Check if conversation exists
$conversation_id = $message->getOrCreateConversation($_SESSION['user_id'], $recipient_id, $listing_id);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Re-check limits before sending
    $limit_check = $messageLimits->canSendMessage($_SESSION['user_id']);
    
    if(!$limit_check['can_send']) {
        $error = 'You have reached your daily message limit. Upgrade to premium for unlimited messaging!';
    } else {
        $message_text = trim($_POST['message']);
        
        if(empty($message_text)) {
            $error = 'Please enter a message';
        } else {
            if($message->send($_SESSION['user_id'], $recipient_id, $message_text, $listing_id)) {
                // Increment message count for free users
                if(!$limit_check['is_premium']) {
                    $messageLimits->incrementMessageCount($_SESSION['user_id']);
                }
                
                // Send notification
                $notifications->send(
                    $recipient_id,
                    'message',
                    'New Message from ' . $_SESSION['username'],
                    substr($message_text, 0, 100),
                    '/conversation.php?id=' . $conversation_id,
                    $_SESSION['user_id'],
                    'high'
                );
                
                $success = 'Message sent successfully!';
                
                // Redirect to conversation after 1 second
                header("refresh:1;url=conversation.php?id=" . $conversation_id);
            } else {
                $error = 'Failed to send message';
            }
        }
    }
}

include 'views/header.php';
?>

<div class="page-content">
    <div class="container-narrow">
        <?php if(!$limit_check['can_send'] && !isset($_POST['message'])): ?>
        <!-- Message Limit Reached -->
        <div class="card" style="text-align: center; padding: 3rem 2rem;">
            <div style="font-size: 5rem; margin-bottom: 1rem;">🚫</div>
            <h2 style="color: var(--danger-red); margin-bottom: 1rem;">Daily Message Limit Reached</h2>
            <p style="color: var(--text-gray); margin-bottom: 2rem; line-height: 1.8;">
                Free members can send up to <?php echo $limit_check['limit']; ?> messages per day. 
                You've sent all <?php echo $limit_check['messages_sent']; ?> of your free messages today.
            </p>
            
            <div style="background: linear-gradient(135deg, rgba(66, 103, 245, 0.1), rgba(29, 155, 240, 0.1)); padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
                <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">💎 Upgrade to Premium</h3>
                <p style="color: var(--text-gray); margin-bottom: 1.5rem;">
                    Get unlimited messaging, incognito mode, advanced search, and more!
                </p>
                <ul style="text-align: left; color: var(--text-gray); line-height: 2; margin: 0 auto 2rem; max-width: 400px;">
                    <li>✅ Unlimited messages</li>
                    <li>✅ No daily limits</li>
                    <li>✅ Featured ads</li>
                    <li>✅ Incognito mode</li>
                    <li>✅ Priority support</li>
                </ul>
                <a href="membership.php" class="btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
                    Upgrade Now - $9.99/month
                </a>
            </div>
            
            <p style="color: var(--text-gray); font-size: 0.9rem;">
                Your messages will reset tomorrow. Come back then to send more free messages!
            </p>
        </div>
        <?php else: ?>
        <!-- Message Form -->
        <div class="card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">💬</div>
                <h2 style="color: var(--primary-blue);">Send Message to <?php echo htmlspecialchars($recipient['username']); ?></h2>
                <?php if($listing): ?>
                <p style="color: var(--text-gray); margin-top: 0.5rem;">
                    Regarding: <strong><?php echo htmlspecialchars($listing['title']); ?></strong>
                </p>
                <?php endif; ?>
            </div>
            
            <?php if(!$limit_check['is_premium']): ?>
            <div class="alert alert-warning">
                <strong>📊 Messages Remaining Today:</strong> 
                <?php echo $limit_check['messages_left']; ?> of <?php echo $limit_check['limit']; ?>
                <?php if($limit_check['messages_left'] <= 2): ?>
                <br>
                <a href="membership.php" style="color: var(--primary-blue); text-decoration: underline;">
                    Upgrade to premium for unlimited messaging!
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <p style="margin-top: 0.5rem;">Redirecting to conversation...</p>
            </div>
            <?php endif; ?>
            
            <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!$success): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Your Message</label>
                    <textarea name="message" rows="8" required placeholder="Hi, I'm interested in your ad..." style="resize: vertical;"></textarea>
                </div>
                
                <div class="alert alert-info">
                    <strong>💡 Tips for a great message:</strong>
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <li>Be respectful and genuine</li>
                        <li>Mention what interests you about the ad</li>
                        <li>Ask specific questions</li>
                        <li>Keep it appropriate</li>
                    </ul>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn-primary btn-block">
                        Send Message 💬
                    </button>
                    <a href="<?php echo $listing_id ? 'listing.php?id=' . $listing_id : 'choose-location.php'; ?>" 
                       class="btn-secondary btn-block" 
                       style="display: flex; align-items: center; justify-content: center;">
                        Cancel
                    </a>
                </div>
            </form>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">🛡️ Safety Reminder</h3>
            <p style="color: var(--text-gray); line-height: 1.8;">
                Never share personal information like your address, phone number, or financial details in messages. 
                Keep conversations on the platform until you feel comfortable. 
                <a href="safety.php" style="color: var(--primary-blue);">Read our safety tips</a>.
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/footer.php'; ?>