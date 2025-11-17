<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Message.php';
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

$message = new Message($db);

// Get all conversations for this user
try {
    $query = "SELECT 
                c.id as conversation_id,
                c.created_at,
                u.id as other_user_id,
                u.username as other_username,
                u.is_online,
                u.photo as other_user_photo,
                (SELECT message_text FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
                (SELECT sender_id FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_sender_id,
                (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND receiver_id = :user_id AND is_read = FALSE) as unread_count
              FROM conversations c
              LEFT JOIN users u ON (
                CASE 
                  WHEN c.user1_id = :user_id2 THEN c.user2_id
                  ELSE c.user1_id
                END = u.id
              )
              WHERE c.user1_id = :user_id3 OR c.user2_id = :user_id4
              ORDER BY last_message_time DESC, c.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':user_id2', $_SESSION['user_id']);
    $stmt->bindParam(':user_id3', $_SESSION['user_id']);
    $stmt->bindParam(':user_id4', $_SESSION['user_id']);
    $stmt->execute();
    $conversations = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching conversations: " . $e->getMessage());
    $conversations = [];
}

include 'views/header.php';
?>

<style>
.messages-page {
    padding: 1rem 0 2rem;
    min-height: calc(100vh - 150px);
}

.conversation-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.conversation-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
    position: relative;
}

.conversation-item:hover {
    border-color: var(--primary-blue);
    background: rgba(66, 103, 245, 0.05);
    transform: translateX(5px);
}

.conversation-item.unread {
    background: rgba(66, 103, 245, 0.1);
    border-color: var(--primary-blue);
}

.conversation-avatar {
    position: relative;
    flex-shrink: 0;
}

.conversation-avatar img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-color);
}

.conversation-avatar-placeholder {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.online-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    background: var(--success-green);
    border: 2px solid var(--card-bg);
    border-radius: 50%;
    box-shadow: 0 0 10px var(--success-green);
}

.conversation-content {
    flex: 1;
    min-width: 0;
}

.conversation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.3rem;
}

.conversation-name {
    font-weight: 600;
    color: var(--text-white);
    font-size: 1rem;
}

.conversation-time {
    font-size: 0.8rem;
    color: var(--text-gray);
    white-space: nowrap;
}

.conversation-message {
    color: var(--text-gray);
    font-size: 0.9rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.conversation-item.unread .conversation-message {
    color: var(--text-white);
    font-weight: 500;
}

.unread-badge {
    position: absolute;
    top: 50%;
    right: 1rem;
    transform: translateY(-50%);
    background: var(--primary-blue);
    color: white;
    font-size: 0.75rem;
    font-weight: bold;
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    min-width: 24px;
    text-align: center;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

@media (max-width: 768px) {
    .messages-page {
        padding: 0.5rem 0 2rem;
    }
    
    .conversation-item {
        padding: 0.75rem;
    }
    
    .conversation-avatar img,
    .conversation-avatar-placeholder {
        width: 50px;
        height: 50px;
    }
    
    .conversation-name {
        font-size: 0.95rem;
    }
    
    .conversation-message {
        font-size: 0.85rem;
    }
    
    .unread-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
        right: 0.75rem;
    }
}
</style>

<div class="messages-page">
    <div class="container-narrow">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h1 style="font-size: 1.8rem;">💬 Messages</h1>
            <?php if(count($conversations) > 0): ?>
            <span style="color: var(--text-gray); font-size: 0.9rem;">
                <?php echo count($conversations); ?> conversation<?php echo count($conversations) != 1 ? 's' : ''; ?>
            </span>
            <?php endif; ?>
        </div>
        
        <?php if(count($conversations) > 0): ?>
        <div class="conversation-list">
            <?php foreach($conversations as $conv): ?>
            <?php if($conv['other_user_id']): // Only show if other user exists ?>
            <a href="conversation.php?id=<?php echo $conv['conversation_id']; ?>" 
               class="conversation-item <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>">
                <div class="conversation-avatar">
                    <?php if($conv['other_user_photo']): ?>
                    <img src="<?php echo htmlspecialchars($conv['other_user_photo']); ?>" 
                         alt="<?php echo htmlspecialchars($conv['other_username']); ?>">
                    <?php else: ?>
                    <div class="conversation-avatar-placeholder">👤</div>
                    <?php endif; ?>
                    <?php if($conv['is_online']): ?>
                    <div class="online-indicator"></div>
                    <?php endif; ?>
                </div>
                
                <div class="conversation-content">
                    <div class="conversation-header">
                        <span class="conversation-name">
                            <?php echo htmlspecialchars($conv['other_username']); ?>
                        </span>
                        <?php if($conv['last_message_time']): ?>
                        <span class="conversation-time">
                            <?php 
                            $time_diff = time() - strtotime($conv['last_message_time']);
                            if($time_diff < 60) {
                                echo 'Just now';
                            } elseif($time_diff < 3600) {
                                echo floor($time_diff / 60) . 'm ago';
                            } elseif($time_diff < 86400) {
                                echo floor($time_diff / 3600) . 'h ago';
                            } elseif($time_diff < 604800) {
                                echo floor($time_diff / 86400) . 'd ago';
                            } else {
                                echo date('M j', strtotime($conv['last_message_time']));
                            }
                            ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php if($conv['last_message']): ?>
                    <div class="conversation-message">
                        <?php if($conv['last_sender_id'] == $_SESSION['user_id']): ?>
                        <span style="color: var(--primary-blue);">You: </span>
                        <?php endif; ?>
                        <?php echo htmlspecialchars(substr($conv['last_message'], 0, 100)); ?>
                        <?php if(strlen($conv['last_message']) > 100): ?>...<?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="conversation-message" style="color: var(--text-gray); font-style: italic;">
                        Start a conversation...
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if($conv['unread_count'] > 0): ?>
                <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card empty-state">
            <div style="font-size: 5rem; margin-bottom: 1rem;">💬</div>
            <h2 style="margin-bottom: 1rem;">No Messages Yet</h2>
            <p style="color: var(--text-gray); margin-bottom: 2rem; max-width: 400px; margin-left: auto; margin-right: auto;">
                Start browsing ads and send messages to connect with people in your area!
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