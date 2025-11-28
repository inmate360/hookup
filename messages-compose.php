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

$error = '';
$success = '';
$recipient_id = isset($_GET['to']) ? (int)$_GET['to'] : null;
$recipient_name = '';

// Get recipient info if specified
if($recipient_id) {
    $query = "SELECT username FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $recipient_id);
    $stmt->execute();
    $recipient = $stmt->fetch();
    $recipient_name = $recipient['username'] ?? '';
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $recipient_id = (int)$_POST['recipient_id'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if(empty($recipient_id) || empty($subject) || empty($message)) {
        $error = 'All fields are required';
    } else {
        $attachment = !empty($_FILES['attachment']['tmp_name']) ? $_FILES['attachment'] : null;
        $result = $pm->createThread($_SESSION['user_id'], $recipient_id, $subject, $message, $attachment);
        
        if($result['success']) {
            header('Location: messages-view.php?thread=' . $result['thread_id'] . '&sent=1');
            exit();
        } else {
            $error = $result['error'];
        }
    }
}

include 'views/header.php';
?>

<link rel="stylesheet" href="/assets/css/dark-blue-theme.css">
<link rel="stylesheet" href="/assets/css/light-theme.css">

<style>
.compose-container {
    padding: 2rem 0;
}

.compose-header {
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

.compose-form {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 15px;
    padding: 2rem;
}

.user-search-result {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-search-result:hover {
    background: rgba(66, 103, 245, 0.1);
}

.search-results {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    right: 0;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 10px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}
</style>

<div class="page-content">
    <div class="container-narrow">
        <div class="compose-container">
            
            <!-- Header -->
            <div class="compose-header">
                <div>
                    <h1 style="margin: 0 0 0.5rem;">✏️ Compose Message</h1>
                    <p style="color: var(--text-gray); margin: 0;">Send a private message</p>
                </div>
                <a href="messages-inbox.php" class="btn-secondary">
                    ← Back to Inbox
                </a>
            </div>
            
            <?php if($error): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <!-- Compose Form -->
            <div class="compose-form">
                <form method="POST" enctype="multipart/form-data" id="composeForm">
                    
                    <!-- Recipient Selection -->
                    <div class="form-group">
                        <label>To:</label>
                        <div style="position: relative;">
                            <input type="text" 
                                   class="form-control" 
                                   id="recipientSearch"
                                   placeholder="Search for a user..."
                                   value="<?php echo htmlspecialchars($recipient_name); ?>"
                                   autocomplete="off">
                            <input type="hidden" name="recipient_id" id="recipientId" value="<?php echo $recipient_id ?: ''; ?>">
                            
                            <div id="searchResults" class="search-results" style="display: none;">
                                <!-- Search results will appear here -->
                            </div>
                        </div>
                        <small style="color: var(--text-gray);">Start typing to search for users</small>
                    </div>
                    
                    <!-- Subject -->
                    <div class="form-group">
                        <label>Subject:</label>
                        <input type="text" 
                               name="subject" 
                               class="form-control" 
                               placeholder="Enter message subject..."
                               required
                               maxlength="255">
                    </div>
                    
                    <!-- Message -->
                    <div class="form-group">
                        <label>Message:</label>
                        <textarea name="message" 
                                  class="form-control" 
                                  rows="10"
                                  placeholder="Type your message here..."
                                  required></textarea>
                        <small style="color: var(--text-gray);">Be respectful and follow community guidelines</small>
                    </div>
                    
                    <!-- Attachment -->
                    <div class="form-group">
                        <label>Attachment (Optional):</label>
                        <input type="file" 
                               name="attachment" 
                               class="form-control"
                               accept="image/*,.pdf,.doc,.docx,.txt">
                        <small style="color: var(--text-gray);">Max file size: 5MB. Allowed: Images, PDF, DOC, TXT</small>
                    </div>
                    
                    <!-- Actions -->
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" onclick="saveDraft()" class="btn-secondary">
                            💾 Save Draft
                        </button>
                        <button type="submit" class="btn-primary">
                            📤 Send Message
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</div>

<script>
let searchTimeout;
const recipientSearch = document.getElementById('recipientSearch');
const recipientId = document.getElementById('recipientId');
const searchResults = document.getElementById('searchResults');

recipientSearch.addEventListener('input', function() {
    const query = this.value.trim();
    
    clearTimeout(searchTimeout);
    
    if(query.length < 2) {
        searchResults.style.display = 'none';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        searchUsers(query);
    }, 300);
});

function searchUsers(query) {
    fetch(`/api/search-users.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if(data.success && data.users.length > 0) {
                displaySearchResults(data.users);
            } else {
                searchResults.innerHTML = '<div class="user-search-result">No users found</div>';
                searchResults.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Search error:', error);
        });
}

function displaySearchResults(users) {
    searchResults.innerHTML = '';
    
    users.forEach(user => {
        const item = document.createElement('div');
        item.className = 'user-search-result';
        item.innerHTML = `
            <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">
                ${user.username.charAt(0).toUpperCase()}
            </div>
            <div>
                <div style="font-weight: 600; color: var(--text-white);">${escapeHtml(user.username)}</div>
                ${user.is_online ? '<small style="color: var(--success-green);">● Online</small>' : ''}
            </div>
        `;
        item.onclick = () => selectUser(user);
        searchResults.appendChild(item);
    });
    
    searchResults.style.display = 'block';
}

function selectUser(user) {
    recipientSearch.value = user.username;
    recipientId.value = user.id;
    searchResults.style.display = 'none';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Hide search results when clicking outside
document.addEventListener('click', function(e) {
    if(!recipientSearch.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.style.display = 'none';
    }
});

function saveDraft() {
    const formData = new FormData(document.getElementById('composeForm'));
    formData.append('action', 'save_draft');
    
    fetch('/api/pm-drafts.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Draft saved successfully!');
        } else {
            alert('Failed to save draft');
        }
    });
}
</script>

<?php include 'views/footer.php'; ?>