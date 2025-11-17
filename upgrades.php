<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check admin status
$query = "SELECT is_admin FROM users WHERE id = :user_id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch();

if(!$user || !$user['is_admin']) {
    header('Location: ../index.php');
    exit();
}

$success = '';
$error = '';

// Ensure duration_days column exists
try {
    $db->exec("ALTER TABLE membership_plans ADD COLUMN IF NOT EXISTS duration_days INT DEFAULT 30 AFTER price");
} catch(PDOException $e) {
    // Column might already exist
}

// Handle approval/rejection
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['approve_upgrade'])) {
        $subscription_id = $_POST['subscription_id'];
        
        // Get subscription details
        $query = "SELECT * FROM user_subscriptions WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $subscription_id);
        $stmt->execute();
        $subscription = $stmt->fetch();
        
        if($subscription) {
            // Get plan details
            $query = "SELECT * FROM membership_plans WHERE id = :plan_id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':plan_id', $subscription['plan_id']);
            $stmt->execute();
            $plan = $stmt->fetch();
            
            // Calculate end date (default to 30 days if duration_days not set)
            $duration_days = $plan['duration_days'] ?? 30;
            $start_date = date('Y-m-d H:i:s');
            $end_date = date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));
            
            // Approve subscription
            $query = "UPDATE user_subscriptions 
                      SET status = 'active', start_date = :start_date, end_date = :end_date, approved_by = :admin_id, approved_at = NOW()
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':admin_id', $_SESSION['user_id']);
            $stmt->bindParam(':id', $subscription_id);
            
            if($stmt->execute()) {
                // Send notification to user
                require_once '../classes/SmartNotifications.php';
                $notifications = new SmartNotifications($db);
                $notifications->send(
                    $subscription['user_id'],
                    'system',
                    'Premium Upgrade Approved! 🎉',
                    'Your premium membership has been activated. Enjoy your premium features!',
                    '/membership.php',
                    null,
                    'high'
                );
                
                $success = 'Upgrade approved successfully!';
            } else {
                $error = 'Failed to approve upgrade';
            }
        }
    }
    
    if(isset($_POST['reject_upgrade'])) {
        $subscription_id = $_POST['subscription_id'];
        $rejection_reason = $_POST['rejection_reason'] ?? 'Payment verification failed';
        
        // Get subscription details
        $query = "SELECT * FROM user_subscriptions WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $subscription_id);
        $stmt->execute();
        $subscription = $stmt->fetch();
        
        // Reject subscription
        $query = "UPDATE user_subscriptions 
                  SET status = 'rejected', rejection_reason = :reason, approved_by = :admin_id, approved_at = NOW()
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':reason', $rejection_reason);
        $stmt->bindParam(':admin_id', $_SESSION['user_id']);
        $stmt->bindParam(':id', $subscription_id);
        
        if($stmt->execute()) {
            // Send notification to user
            require_once '../classes/SmartNotifications.php';
            $notifications = new SmartNotifications($db);
            $notifications->send(
                $subscription['user_id'],
                'system',
                'Premium Upgrade Status',
                'Your premium upgrade request has been reviewed. Reason: ' . $rejection_reason,
                '/membership.php',
                null,
                'high'
            );
            
            $success = 'Upgrade rejected';
        } else {
            $error = 'Failed to reject upgrade';
        }
    }
}

// Get pending upgrades
try {
    $query = "SELECT us.*, u.username, u.email, mp.name as plan_name, mp.price
              FROM user_subscriptions us
              LEFT JOIN users u ON us.user_id = u.id
              LEFT JOIN membership_plans mp ON us.plan_id = mp.id
              WHERE us.status = 'pending'
              ORDER BY us.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $pending_upgrades = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching pending upgrades: " . $e->getMessage());
    $pending_upgrades = [];
}

// Get recent approved/rejected upgrades
try {
    $query = "SELECT us.*, u.username, u.email, mp.name as plan_name, mp.price, a.username as admin_name
              FROM user_subscriptions us
              LEFT JOIN users u ON us.user_id = u.id
              LEFT JOIN membership_plans mp ON us.plan_id = mp.id
              LEFT JOIN users a ON us.approved_by = a.id
              WHERE us.status IN ('active', 'rejected')
              ORDER BY us.approved_at DESC
              LIMIT 20";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_upgrades = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching recent upgrades: " . $e->getMessage());
    $recent_upgrades = [];
}

include '../views/header.php';
?>

<link rel="stylesheet" href="../assets/css/dark-blue-theme.css">

<div class="admin-container">
    <div class="admin-header">
        <h1>💎 Premium Upgrade Management</h1>
        <p style="color: var(--text-gray);">Review and approve premium membership purchases</p>
    </div>

    <div class="admin-nav">
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="users.php">👥 Users</a>
        <a href="listings.php">📝 Listings</a>
        <a href="upgrades.php" class="active">💎 Upgrades</a>
        <a href="reports.php">🚨 Reports</a>
        <a href="announcements.php">📢 Announcements</a>
        <a href="categories.php">📁 Categories</a>
        <a href="settings.php">⚙️ Settings</a>
    </div>

    <?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="admin-section">
        <h2>⏳ Pending Upgrades (<?php echo count($pending_upgrades); ?>)</h2>
        
        <?php if(count($pending_upgrades) > 0): ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Payment Method</th>
                        <th>Transaction ID</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pending_upgrades as $upgrade): ?>
                    <tr>
                        <td><?php echo $upgrade['id']; ?></td>
                        <td>
                            <a href="../profile.php?id=<?php echo $upgrade['user_id']; ?>" style="color: var(--primary-blue);">
                                <?php echo htmlspecialchars($upgrade['username']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($upgrade['email']); ?></td>
                        <td>
                            <strong style="color: var(--primary-blue);">
                                <?php echo htmlspecialchars($upgrade['plan_name']); ?>
                            </strong>
                        </td>
                        <td>
                            <strong style="color: var(--success-green);">
                                $<?php echo number_format($upgrade['price'], 2); ?>
                            </strong>
                        </td>
                        <td><?php echo htmlspecialchars($upgrade['payment_method'] ?? 'N/A'); ?></td>
                        <td>
                            <code style="background: rgba(66, 103, 245, 0.1); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">
                                <?php echo htmlspecialchars(substr($upgrade['transaction_id'] ?? 'N/A', 0, 20)); ?>
                            </code>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($upgrade['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="subscription_id" value="<?php echo $upgrade['id']; ?>">
                                <button type="submit" name="approve_upgrade" class="btn-success btn-small" onclick="return confirm('Approve this upgrade?');">
                                    ✓ Approve
                                </button>
                            </form>
                            <button class="btn-danger btn-small" onclick="showRejectModal(<?php echo $upgrade['id']; ?>)">
                                ✗ Reject
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 3rem; background: rgba(66, 103, 245, 0.05); border-radius: 10px;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">✓</div>
            <h3>No Pending Upgrades</h3>
            <p style="color: var(--text-gray);">All upgrade requests have been processed</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="admin-section">
        <h2>Recent Actions</h2>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Approved By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_upgrades as $upgrade): ?>
                    <tr>
                        <td><?php echo $upgrade['id']; ?></td>
                        <td><?php echo htmlspecialchars($upgrade['username']); ?></td>
                        <td><?php echo htmlspecialchars($upgrade['plan_name']); ?></td>
                        <td>$<?php echo number_format($upgrade['price'], 2); ?></td>
                        <td>
                            <span style="color: <?php echo $upgrade['status'] == 'active' ? 'var(--success-green)' : 'var(--danger-red)'; ?>">
                                <?php echo ucfirst($upgrade['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($upgrade['admin_name'] ?? 'System'); ?></td>
                        <td><?php echo date('M j, Y', strtotime($upgrade['approved_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
    <div class="card" style="max-width: 500px; width: 90%;">
        <h3 style="margin-bottom: 1rem;">Reject Upgrade Request</h3>
        <form method="POST" id="rejectForm">
            <input type="hidden" name="subscription_id" id="rejectSubscriptionId">
            <div class="form-group">
                <label>Rejection Reason</label>
                <textarea name="rejection_reason" rows="4" required placeholder="e.g., Payment verification failed, Invalid payment method, etc."></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <button type="button" class="btn-secondary btn-block" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" name="reject_upgrade" class="btn-danger btn-block">Reject</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal(subscriptionId) {
    document.getElementById('rejectSubscriptionId').value = subscriptionId;
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if(e.target === this) {
        closeRejectModal();
    }
});
</script>

<?php include '../views/footer.php'; ?>