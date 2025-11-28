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

$thread_id = (int)($_GET['thread'] ?? 0);

if(!$thread_id) {
    header('Location: messages-inbox.php');
    exit();
}

$thread = $pm->getThread($thread_id, $_SESSION['user_id']);

if(!$thread) {
    header('Location: messages-inbox.php');
    exit();
}

$other_user = ($thread['starter_id'] == $_SESSION['user_id']) ? 
              ['id' => $thread['recipient_id'], 'username' => $thread['recipient_name']] :
              ['id' => $thread['starter_id'], 'username' => $thread['starter_name']];

include 'views/header.php';
?>

<!-- Tailwind CSS & Bootstrap -->
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<link rel="stylesheet" href="/assets/css/dark-blue-theme.css">

<link rel="stylesheet" href="/assets/css/light-theme.css">

<style>
.message-bubble {
    max-width: 75%;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.quoted-message {
    background: rgba(100, 116, 139, 0.1);
    border-left: 3px solid #64748b;
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.9rem;
}
</style>

<div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 py-6">
    <div class="container mx-auto px-4" style="max-width: 1200px;">
        
        <!-- Header -->
        <div class="card border-0 shadow-lg rounded-4 mb-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <a href="messages-inbox.php" class="btn btn-light rounded-circle" style="width: 40px; height: 40px;">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="h4 fw-bold mb-1"><?php echo htmlspecialchars($thread['subject']); ?></h1>
                            <p class="mb-0 small opacity-90">
                                Conversation with <?php echo htmlspecialchars($other_user['username']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php?id=<?php echo $other_user['id']; ?>">
                                <i class="bi bi-person me-2"></i> View Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteThread(<?php echo $thread_id; ?>)">
                                <i class="bi bi-trash me-2"></i> Delete Thread
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if(isset($_GET['sent'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            Message sent successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Messages -->
        <div class="card border-0 shadow-lg rounded-4 mb-4">
            <div class="card-body p-4" style="max-height: 600px; overflow-y: auto;" id="messagesContainer">
                
                <?php foreach($thread['messages'] as $message): ?>
                    <?php $is_mine = $message['sender_id'] == $_SESSION['user_id']; ?>
                    
                    <div class="d-flex mb-4 <?php echo $is_mine ? 'justify-content-end' : 'justify-content-start'; ?>">
                        <div class="message-bubble">
                            
                            <?php if(!$is_mine): ?>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="rounded-circle bg-gradient-to-br from-blue-500 to-purple-600 d-flex align-items-center justify-content-center text-white fw-bold" 
                                     style="width: 32px; height: 32px; font-size: 0.9rem;">
                                    <?php echo strtoupper(substr($message['sender_name'], 0, 1)); ?>
                                </div>
                                <span class="fw-semibold"><?php echo htmlspecialchars($message['sender_name']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="card border-0 <?php echo $is_mine ? 'bg-primary text-white' : 'bg-light'; ?>">
                                <div class="card-body p-3">
                                    
                                    <?php if($message['quoted_message_id']): ?>
                                    <div class="quoted-message">
                                        <small class="fw-bold d-block mb-1">
                                            <i class="bi bi-reply me-1"></i>
                                            <?php echo htmlspecialchars($message['quoted_sender']); ?>:
                                        </small>
                                        <div><?php echo htmlspecialchars(substr($message['quoted_text'], 0, 150)); ?>...</div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div style="white-space: pre-wrap;">
                                        <?php echo nl2br(htmlspecialchars($message['message_text'])); ?>
                                    </div>
                                    
                                    <?php if(!empty($message['attachments'])): ?>
                                    <div class="mt-3">
                                        <?php foreach($message['attachments'] as $attachment): ?>
                                        <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" 
                                           target="_blank"
                                           class="btn btn-sm <?php echo $is_mine ? 'btn-light' : 'btn-primary'; ?> rounded-pill mb-1">
                                            <i class="bi bi-paperclip me-1"></i>
                                            <?php echo htmlspecialchars($attachment['file_name']); ?>
                                            <small>(<?php echo number_format($attachment['file_size'] / 1024, 1); ?>KB)</small>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="mt-2 d-flex justify-content-between align-items-center">
                                        <small class="opacity-75">
                                            <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>
                                        </small>
                                        <?php if(!$is_mine): ?>
                                        <button class="btn btn-sm btn-link p-0" onclick="quoteMessage(<?php echo $message['id']; ?>, '<?php echo addslashes(htmlspecialchars($message['sender_name'])); ?>', '<?php echo addslashes(htmlspecialchars(substr($message['message_text'], 0, 150))); ?>')">
                                            <i class="bi bi-reply"></i> Quote
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
            </div>
        </div>
        
        <!-- Reply Form -->
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-reply me-2"></i> Reply to this thread
                </h5>
                
                <div id="quotedPreview" class="d-none mb-3">
                    <div class="quoted-message">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="fw-bold d-block mb-1">
                                    <i class="bi bi-reply me-1"></i>
                                    <span id="quotedSender"></span>:
                                </small>
                                <div id="quotedText"></div>
                            </div>
                            <button class="btn btn-sm btn-link p-0" onclick="clearQuote()">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="/api/pm-reply.php" id="replyForm" enctype="multipart/form-data">
                    <input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
                    <input type="hidden" name="quoted_message_id" id="quotedMessageId" value="">
                    
                    <div class="mb-3">
                        <textarea name="message" 
                                  class="form-control form-control-lg" 
                                  rows="6"
                                  placeholder="Type your reply..."
                                  required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-paperclip me-1"></i> Attachment (Optional)
                        </label>
                        <input type="file" 
                               name="attachment" 
                               class="form-control"
                               accept="image/*,.pdf,.doc,.docx,.txt">
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">
                            <i class="bi bi-send me-2"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Scroll to bottom on load
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;
});

// Quote message
function quoteMessage(messageId, sender, text) {
    document.getElementById('quotedMessageId').value = messageId;
    document.getElementById('quotedSender').textContent = sender;
    document.getElementById('quotedText').textContent = text;
    document.getElementById('quotedPreview').classList.remove('d-none');
    
    // Focus on reply textarea
    document.querySelector('textarea[name="message"]').focus();
}

function clearQuote() {
    document.getElementById('quotedMessageId').value = '';
    document.getElementById('quotedPreview').classList.add('d-none');
}

// Handle reply form submission
document.getElementById('replyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending...';
    
    fetch('/api/pm-reply.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to send reply');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-2"></i> Send Reply';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to send reply');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send me-2"></i> Send Reply';
    });
});

function deleteThread(threadId) {
    if(!confirm('Are you sure you want to delete this conversation? This cannot be undone.')) {
        return;
    }
    
    fetch('/api/pm-delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `thread_id=${threadId}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            window.location.href = 'messages-inbox.php';
        } else {
            alert('Failed to delete thread');
        }
    });
}
</script>

<?php include 'views/footer.php'; ?>