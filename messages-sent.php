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
$threads = $pm->getSentMessages($_SESSION['user_id'], $page, 20);

include 'views/header.php';
?>

<!-- Tailwind CSS & Bootstrap -->
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="/assets/css/dark-blue-theme.css">
<link rel="stylesheet" href="/assets/css/light-theme.css">

<div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 py-6">
    <div class="container mx-auto px-4" style="max-width: 1400px;">
        
        <!-- Header -->
        <div class="card border-0 shadow-lg rounded-4 mb-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h1 class="h2 fw-bold mb-1">
                            <i class="bi bi-send me-2"></i> Sent Messages
                        </h1>
                        <p class="mb-0 opacity-90">Messages you've sent</p>
                    </div>
                    <a href="messages-compose.php" class="btn btn-light btn-lg rounded-pill px-4">
                        <i class="bi bi-pencil-square me-2"></i> Compose New
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-3">
                        <div class="list-group list-group-flush">
                            <a href="messages-inbox.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-inbox me-2"></i> Inbox
                            </a>
                            <a href="messages-sent.php" class="list-group-item list-group-item-action active">
                                <i class="bi bi-send me-2"></i> Sent
                            </a>
                            <a href="messages-drafts.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-file-earmark-text me-2"></i> Drafts
                            </a>
                            <a href="messages-search.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-search me-2"></i> Search
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Message List -->
            <div class="col-lg-9">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4">
                        
                        <?php if(empty($threads)): ?>
                        <div class="text-center py-5">
                            <div class="display-1 mb-3 opacity-50">📤</div>
                            <h3 class="h4 mb-2">No Sent Messages</h3>
                            <p class="text-muted">You haven't sent any messages yet.</p>
                            <a href="messages-compose.php" class="btn btn-primary rounded-pill mt-3">
                                <i class="bi bi-pencil me-2"></i> Compose Message
                            </a>
                        </div>
                        <?php else: ?>
                        
                        <div class="list-group list-group-flush">
                            <?php foreach($threads as $thread): ?>
                            <a href="messages-view.php?thread=<?php echo $thread['id']; ?>" 
                               class="list-group-item list-group-item-action p-3">
                                <div class="d-flex w-100 justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div class="rounded-circle bg-gradient-to-br from-blue-500 to-purple-600 d-flex align-items-center justify-content-center text-white fw-bold" 
                                                 style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($thread['recipient_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">
                                                    To: <?php echo htmlspecialchars($thread['recipient_name']); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y g:i A', strtotime($thread['updated_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <p class="mb-1 fw-semibold">
                                            <?php echo htmlspecialchars($thread['subject']); ?>
                                        </p>
                                        <p class="mb-0 text-muted small text-truncate" style="max-width: 600px;">
                                            <?php echo htmlspecialchars(substr($thread['last_message'], 0, 100)); ?>...
                                        </p>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'views/footer.php'; ?>