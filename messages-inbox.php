<?php
session_start();
require_once 'config/database.php';
require_once 'classes/PrivateMessaging.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$pm = new PrivateMessaging($db);

$page = (int)($_GET['page'] ?? 1);
$folder_id = isset($_GET['folder']) ? (int)$_GET['folder'] : null;

$threads = $pm->getInbox($_SESSION['user_id'], $page, 20, $folder_id);
$unread_count = $pm->getUnreadCount($_SESSION['user_id']);

include 'views/header.php';
?>

<link rel="stylesheet" href="/assets/css/dark-blue-theme.css">
<link rel="stylesheet" href="/assets/css/light-theme.css">

<style>
.messages-container {
    padding: 2rem 0;
}

.messages-header {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.messages-grid {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
    align-items: start;
}

.messages-sidebar {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 15px;
    padding: 1.5rem;
    position: sticky;
    top: 80px;
}

.sidebar-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-radius: 10px;
    color: var(--text-white);
    text-decoration: none;
    transition: all 0.3s;
    margin-bottom: 0.5rem;
}

.sidebar-item:hover {
    background: rgba(66, 103, 245, 0.1);
    color: var(--primary-blue);
}

.sidebar-item.active {
    background: rgba(66, 103, 245, 0.15);
    color: var(--primary-blue);
}

.thread-list {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 15px;
    overflow: hidden;
}

.thread-item {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    border-bottom: 2px solid var(--border-color);
    text-decoration: none;
    transition: all 0.3s;
    position: relative;
}

.thread-item:last-child {
    border-bottom: none;
}

.thread-item:hover {
    background: rgba(66, 103, 245, 0.05);
}

.thread-item.unread {
    background: rgba(66, 103, 245, 0.08);
    border-left: 4px solid var(--primary-blue);
}

.thread-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
    position: relative;
}

.online-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 14px;
    height: 14px;
    background: var(--success-green);
    border: 3px solid var(--card-bg);
    border-radius: 50%;
}

.thread-content {
    flex: 1;
    min-width: 0;
}

.thread-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 0.5rem;
}

.thread-username {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--text-white);
}

.thread-time {
    font-size: 0.85rem;
    color: var(--text-gray);
}

.thread-subject {
    font-weight: 600;
    color: var(--primary-blue);
    margin-bottom: 0.25rem;
}

.thread-preview {
    color: var(--text-gray);
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.unread-badge {
    background: var(--primary-blue);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 700;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

@media (max-width: 768px) {
    .messages-grid {
        grid-template-columns: 1fr;
    }
    
    .messages-sidebar {
        position: static;
    }
}
</style>

<div class="page-content">
    <div class="container">
        <div class="messages-container">
            
            <!-- Header -->
            <div class="messages-header">
                <div>
                    <h1 style="margin: 0 0 0.5rem;">💬 Private Messages</h1>
                    <p style="color: var(--text-gray); margin: 0;">
                        <?php echo $unread_count; ?> unread message<?php echo $unread_count != 1 ? 's' : ''; ?>
                    </p>
                </div>
                <a href="messages-compose.php" class="btn-primary">
                    ✏️ Compose New
                </a>
            </div>
            
            <div class="messages-grid">
                <!-- Sidebar -->
                <div class="messages-sidebar">
                    <a href="messages-inbox.php" class="sidebar-item <?php echo !$folder_id ? 'active' : ''; ?>">
                        <span>📥 Inbox</span>
                        <?php if($unread_count > 0): ?>
                        <span class="unread-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="messages-sent.php" class="sidebar-item">
                        <span>📤 Sent</span>
                    </a>
                    <a href="messages-drafts.php" class="sidebar-item">
                        <span>📝 Drafts</span>
                    </a>
                    <a href="messages-search.php" class="sidebar-item">
                        <span>🔍 Search</span>
                    </a>
                </div>
                
                <!-- Thread List -->
                <div>
                    <?php if(empty($threads)): ?>
                    <div class="thread-list">
                        <div class="empty-state">
                            <div style="font-size: 5rem; margin-bottom: 1rem; opacity: 0.5;">📭</div>
                            <h3>No Messages</h3>
                            <p style="color: var(--text-gray); margin: 1rem 0;">Your inbox is empty. Start a conversation!</p>
                            <a href="messages-compose.php" class="btn-primary">
                                ✏️ Compose Message
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="thread-list">
                        <?php foreach($threads as $thread): ?>
                        <a href="messages-view.php?thread=<?php echo $thread['id']; ?>" 
                           class="thread-item <?php echo $thread['unread_count'] > 0 ? 'unread' : ''; ?>">
                            <div class="thread-avatar">
                                <?php echo strtoupper(substr($thread['other_user'], 0, 1)); ?>
                                <?php if($thread['is_online']): ?>
                                <span class="online-indicator"></span>
                                <?php endif; ?>
                            </div>
                            <div class="thread-content">
                                <div class="thread-header">
                                    <span class="thread-username">
                                        <?php echo htmlspecialchars($thread['other_user']); ?>
                                    </span>
                                    <span class="thread-time">
                                        <?php echo date('M j, g:i A', strtotime($thread['last_message_time'])); ?>
                                    </span>
                                </div>
                                <div class="thread-subject">
                                    <?php echo htmlspecialchars($thread['subject']); ?>
                                </div>
                                <div class="thread-preview">
                                    <?php echo htmlspecialchars(substr($thread['last_message'], 0, 100)); ?>...
                                </div>
                            </div>
                            <?php if($thread['unread_count'] > 0): ?>
                            <span class="unread-badge">
                                <?php echo $thread['unread_count']; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>