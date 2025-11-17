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

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['create_announcement'])) {
        $query = "INSERT INTO site_announcements (title, message, type, show_on_homepage, show_on_all_pages, priority, start_date, end_date, created_by)
                  VALUES (:title, :message, :type, :homepage, :all_pages, :priority, :start_date, :end_date, :created_by)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $_POST['title']);
        $stmt->bindParam(':message', $_POST['message']);
        $stmt->bindParam(':type', $_POST['type']);
        $stmt->bindParam(':homepage', $_POST['show_on_homepage'], PDO::PARAM_BOOL);
        $stmt->bindParam(':all_pages', $_POST['show_on_all_pages'], PDO::PARAM_BOOL);
        $stmt->bindParam(':priority', $_POST['priority']);
        $start = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $stmt->bindParam(':start_date', $start);
        $stmt->bindParam(':end_date', $end);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);
        
        if($stmt->execute()) {
            $success = 'Announcement created successfully!';
        } else {
            $error = 'Failed to create announcement';
        }
    }
    
    if(isset($_POST['toggle_status'])) {
        $query = "UPDATE site_announcements SET is_active = NOT is_active WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_POST['announcement_id']);
        $stmt->execute();
        $success = 'Announcement status updated!';
    }
    
    if(isset($_POST['delete_announcement'])) {
        $query = "DELETE FROM site_announcements WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_POST['announcement_id']);
        $stmt->execute();
        $success = 'Announcement deleted!';
    }
}

// Get all announcements
$query = "SELECT sa.*, u.username 
          FROM site_announcements sa
          LEFT JOIN users u ON sa.created_by = u.id
          ORDER BY sa.priority DESC, sa.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$announcements = $stmt->fetchAll();

include '../views/header.php';
?>

<link rel="stylesheet" href="../assets/css/dark-blue-theme.css">

<div class="admin-container">
    <div class="admin-header">
        <h1>📢 Announcements Management</h1>
        <p style="color: var(--text-gray);">Create and manage site-wide announcements</p>
    </div>

    <div class="admin-nav">
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="users.php">👥 Users</a>
        <a href="listings.php">📝 Listings</a>
        <a href="upgrades.php">💎 Upgrades</a>
        <a href="reports.php">🚨 Reports</a>
        <a href="announcements.php" class="active">📢 Announcements</a>
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
        <h2>Create New Announcement</h2>
        <form method="POST" action="announcements.php">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required>
            </div>
            
            <div class="form-group">
                <label>Message</label>
                <textarea name="message" rows="4" required></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" required>
                        <option value="info">Info (Blue)</option>
                        <option value="success">Success (Green)</option>
                        <option value="warning">Warning (Orange)</option>
                        <option value="danger">Danger (Red)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Priority (higher = shows first)</label>
                    <input type="number" name="priority" value="0" min="0" max="100">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label>Start Date (optional)</label>
                    <input type="datetime-local" name="start_date">
                </div>
                
                <div class="form-group">
                    <label>End Date (optional)</label>
                    <input type="datetime-local" name="end_date">
                </div>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-white);">
                    <input type="checkbox" name="show_on_homepage" value="1" checked>
                    Show on homepage
                </label>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-white);">
                    <input type="checkbox" name="show_on_all_pages" value="1">
                    Show on all pages
                </label>
            </div>
            
            <button type="submit" name="create_announcement" class="btn-primary">Create Announcement</button>
        </form>
    </div>

    <div class="admin-section">
        <h2>All Announcements</h2>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Homepage</th>
                        <th>All Pages</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($announcements as $announcement): ?>
                    <tr>
                        <td><?php echo $announcement['id']; ?></td>
                        <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                        <td>
                            <span style="padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; background: rgba(66, 103, 245, 0.2); color: var(--info-cyan);">
                                <?php echo ucfirst($announcement['type']); ?>
                            </span>
                        </td>
                        <td><?php echo $announcement['priority']; ?></td>
                        <td><?php echo $announcement['show_on_homepage'] ? '✓' : '✗'; ?></td>
                        <td><?php echo $announcement['show_on_all_pages'] ? '✓' : '✗'; ?></td>
                        <td>
                            <span style="color: <?php echo $announcement['is_active'] ? 'var(--success-green)' : 'var(--danger-red)'; ?>">
                                <?php echo $announcement['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($announcement['username']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle status?');">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                <button type="submit" name="toggle_status" class="action-btn edit">Toggle</button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this announcement?');">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                <button type="submit" name="delete_announcement" class="action-btn delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?>