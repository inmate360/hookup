<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

// Check if user is admin
if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=/admin/dashboard.php');
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

// Get statistics
// Total users
$query = "SELECT COUNT(*) as count FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$total_users = $stmt->fetch()['count'];

// New users today
$query = "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$new_users_today = $stmt->fetch()['count'];

// Total listings
$query = "SELECT COUNT(*) as count FROM listings";
$stmt = $db->prepare($query);
$stmt->execute();
$total_listings = $stmt->fetch()['count'];

// Active listings
$query = "SELECT COUNT(*) as count FROM listings WHERE status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$active_listings = $stmt->fetch()['count'];

// Pending listings
$query = "SELECT COUNT(*) as count FROM listings WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_listings = $stmt->fetch()['count'];

// Total messages
$query = "SELECT COUNT(*) as count FROM messages";
$stmt = $db->prepare($query);
$stmt->execute();
$total_messages = $stmt->fetch()['count'];

// Premium members - with error handling
try {
    $query = "SELECT COUNT(DISTINCT user_id) as count FROM user_subscriptions 
              WHERE status = 'active'";
    
    // Check if end_date column exists
    $checkQuery = "SHOW COLUMNS FROM user_subscriptions LIKE 'end_date'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute();
    
    if($checkStmt->rowCount() > 0) {
        $query .= " AND end_date > NOW()";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $premium_members = $stmt->fetch()['count'];
} catch(PDOException $e) {
    $premium_members = 0;
    error_log("Premium members query error: " . $e->getMessage());
}

// Pending premium upgrades
try {
    $query = "SELECT COUNT(*) as count FROM user_subscriptions WHERE status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $pending_upgrades = $stmt->fetch()['count'];
} catch(PDOException $e) {
    $pending_upgrades = 0;
    error_log("Pending upgrades query error: " . $e->getMessage());
}

// Recent users
$query = "SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_users = $stmt->fetchAll();

// Recent listings
$query = "SELECT l.*, u.username, c.name as city_name 
          FROM listings l
          LEFT JOIN users u ON l.user_id = u.id
          LEFT JOIN cities c ON l.city_id = c.id
          ORDER BY l.created_at DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_listings = $stmt->fetchAll();

// Reports count
try {
    $query = "SELECT COUNT(*) as count FROM reports WHERE status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $pending_reports = $stmt->fetch()['count'];
} catch(PDOException $e) {
    $pending_reports = 0;
    error_log("Pending reports query error: " . $e->getMessage());
}

include '../views/header.php';
?>

<style>
.admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 20px;
}

.admin-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border-color);
}

.admin-nav {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.admin-nav a {
    padding: 0.75rem 1.5rem;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-white);
    transition: all 0.3s;
}

.admin-nav a:hover, .admin-nav a.active {
    background: rgba(66, 103, 245, 0.1);
    border-color: var(--primary-blue);
    color: var(--primary-blue);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(66, 103, 245, 0.2);
    border-color: var(--primary-blue);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-blue);
    margin-bottom: 0.25rem;
}

.stat-label {
    color: var(--text-gray);
    font-size: 0.9rem;
}

.stat-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: bold;
    margin-top: 0.5rem;
}

.stat-badge.success {
    background: rgba(16, 185, 129, 0.2);
    color: var(--success-green);
}

.stat-badge.warning {
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning-orange);
}

.admin-section {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.admin-section h2 {
    margin-bottom: 1.5rem;
    color: var(--primary-blue);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: rgba(66, 103, 245, 0.1);
    padding: 1rem;
    text-align: left;
    color: var(--text-white);
    font-weight: 600;
    border-bottom: 2px solid var(--border-color);
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-gray);
}

.data-table tr:hover {
    background: rgba(66, 103, 245, 0.05);
}

.action-btn {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85rem;
    display: inline-block;
    margin-right: 0.5rem;
}

.action-btn.view {
    background: rgba(6, 182, 212, 0.2);
    color: var(--info-cyan);
}

.action-btn.edit {
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning-orange);
}

.action-btn.delete {
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger-red);
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .admin-nav {
        flex-direction: column;
    }
    
    .data-table {
        font-size: 0.85rem;
    }
    
    .data-table th, .data-table td {
        padding: 0.75rem 0.5rem;
    }
}
</style>

<div class="admin-container">
    <div class="admin-header">
        <h1>🛡️ Administrator Dashboard</h1>
        <p style="color: var(--text-gray);">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
    </div>

    <div class="admin-nav">
        <a href="dashboard.php" class="active">📊 Dashboard</a>
        <a href="users.php">👥 Users</a>
        <a href="listings.php">📝 Listings</a>
        <a href="upgrades.php">💎 Upgrades (<?php echo $pending_upgrades; ?>)</a>
        <a href="reports.php">🚨 Reports (<?php echo $pending_reports; ?>)</a>
        <a href="announcements.php">📢 Announcements</a>
        <a href="categories.php">📁 Categories</a>
        <a href="settings.php">⚙️ Settings</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?php echo number_format($total_users); ?></div>
            <div class="stat-label">Total Users</div>
            <div class="stat-badge success">+<?php echo $new_users_today; ?> today</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-value"><?php echo number_format($total_listings); ?></div>
            <div class="stat-label">Total Listings</div>
            <div class="stat-badge success"><?php echo $active_listings; ?> active</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-value"><?php echo number_format($pending_listings); ?></div>
            <div class="stat-label">Pending Approval</div>
            <?php if($pending_listings > 0): ?>
            <div class="stat-badge warning">Needs attention</div>
            <?php endif; ?>
        </div>

        <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-value"><?php echo number_format($total_messages); ?></div>
            <div class="stat-label">Total Messages</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">💎</div>
            <div class="stat-value"><?php echo number_format($premium_members); ?></div>
            <div class="stat-label">Premium Members</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔄</div>
            <div class="stat-value"><?php echo number_format($pending_upgrades); ?></div>
            <div class="stat-label">Pending Upgrades</div>
            <?php if($pending_upgrades > 0): ?>
            <div class="stat-badge warning">Review needed</div>
            <?php endif; ?>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🚨</div>
            <div class="stat-value"><?php echo number_format($pending_reports); ?></div>
            <div class="stat-label">Pending Reports</div>
            <?php if($pending_reports > 0): ?>
            <div class="stat-badge warning">Action required</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-section">
        <h2>Recent Users</h2>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_users as $user_item): ?>
                    <tr>
                        <td><?php echo $user_item['id']; ?></td>
                        <td><?php echo htmlspecialchars($user_item['username']); ?></td>
                        <td><?php echo htmlspecialchars($user_item['email']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($user_item['created_at'])); ?></td>
                        <td>
                            <a href="../profile.php?id=<?php echo $user_item['id']; ?>" class="action-btn view">View</a>
                            <a href="user-edit.php?id=<?php echo $user_item['id']; ?>" class="action-btn edit">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="users.php" class="btn-primary" style="margin-top: 1rem;">View All Users</a>
    </div>

    <div class="admin-section">
        <h2>Recent Listings</h2>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>User</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_listings as $listing): ?>
                    <tr>
                        <td><?php echo $listing['id']; ?></td>
                        <td><?php echo htmlspecialchars(substr($listing['title'], 0, 40)); ?>...</td>
                        <td><?php echo htmlspecialchars($listing['username']); ?></td>
                        <td><?php echo htmlspecialchars($listing['city_name']); ?></td>
                        <td>
                            <span style="color: <?php echo $listing['status'] == 'active' ? 'var(--success-green)' : 'var(--warning-orange)'; ?>">
                                <?php echo ucfirst($listing['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($listing['created_at'])); ?></td>
                        <td>
                            <a href="../listing.php?id=<?php echo $listing['id']; ?>" class="action-btn view">View</a>
                            <a href="listing-edit.php?id=<?php echo $listing['id']; ?>" class="action-btn edit">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="listings.php" class="btn-primary" style="margin-top: 1rem;">View All Listings</a>
    </div>
</div>

<?php include '../views/footer.php'; ?>