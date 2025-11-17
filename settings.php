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

// Ensure site_settings table exists
try {
    $query = "CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_key (setting_key)
    )";
    $db->exec($query);
} catch(PDOException $e) {
    error_log("Error creating site_settings table: " . $e->getMessage());
}

// Get current settings
$current_settings = [];
try {
    $query = "SELECT setting_key, setting_value FROM site_settings";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $current_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch(PDOException $e) {
    error_log("Error fetching settings: " . $e->getMessage());
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['save_maintenance'])) {
        $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';
        $maintenance_title = $_POST['maintenance_title'];
        $maintenance_message = $_POST['maintenance_message'];
        $allow_admin_access = isset($_POST['allow_admin_access']) ? '1' : '0';
        
        try {
            // Delete old settings first
            $db->exec("DELETE FROM site_settings WHERE setting_key IN ('maintenance_mode', 'maintenance_title', 'maintenance_message', 'allow_admin_access')");
            
            // Insert new settings
            $settings_to_insert = [
                'maintenance_mode' => $maintenance_mode,
                'maintenance_title' => $maintenance_title,
                'maintenance_message' => $maintenance_message,
                'allow_admin_access' => $allow_admin_access
            ];
            
            foreach($settings_to_insert as $key => $value) {
                $query = "INSERT INTO site_settings (setting_key, setting_value, updated_by, updated_at) 
                          VALUES (:key, :value, :user_id, NOW())";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':key', $key);
                $stmt->bindParam(':value', $value);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
            }
            
            $success = 'Maintenance settings saved successfully!';
            
            // Refresh settings
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM site_settings");
            $stmt->execute();
            $current_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
        } catch(PDOException $e) {
            error_log("Error saving maintenance settings: " . $e->getMessage());
            $error = 'Failed to save settings: ' . $e->getMessage();
        }
    }
    
    if(isset($_POST['save_coming_soon'])) {
        $coming_soon_mode = isset($_POST['coming_soon_mode']) ? '1' : '0';
        $coming_soon_message = $_POST['coming_soon_message'];
        $coming_soon_launch_date = $_POST['coming_soon_launch_date'] ?? null;
        
        try {
            // Delete old settings first
            $db->exec("DELETE FROM site_settings WHERE setting_key IN ('coming_soon_mode', 'coming_soon_message', 'coming_soon_launch_date')");
            
            // Insert new settings
            $settings_to_insert = [
                'coming_soon_mode' => $coming_soon_mode,
                'coming_soon_message' => $coming_soon_message,
                'coming_soon_launch_date' => $coming_soon_launch_date
            ];
            
            foreach($settings_to_insert as $key => $value) {
                $query = "INSERT INTO site_settings (setting_key, setting_value, updated_by, updated_at) 
                          VALUES (:key, :value, :user_id, NOW())";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':key', $key);
                $stmt->bindParam(':value', $value);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
            }
            
            $success = 'Coming soon settings saved successfully!';
            
            // Refresh settings
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM site_settings");
            $stmt->execute();
            $current_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
        } catch(PDOException $e) {
            error_log("Error saving coming soon settings: " . $e->getMessage());
            $error = 'Failed to save settings: ' . $e->getMessage();
        }
    }
    
    // Test mode - immediately check if maintenance works
    if(isset($_POST['test_maintenance'])) {
        $query = "SELECT setting_value FROM site_settings WHERE setting_key = 'maintenance_mode' LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if($result) {
            $success = 'Maintenance mode is currently: ' . ($result['setting_value'] == '1' ? 'ENABLED' : 'DISABLED');
        } else {
            $error = 'No maintenance mode setting found in database';
        }
    }
}

include '../views/header.php';
?>

<link rel="stylesheet" href="../assets/css/dark-blue-theme.css">

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

.stat-card {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: var(--text-gray);
    font-size: 0.9rem;
}

.debug-info {
    background: rgba(0, 0, 0, 0.3);
    padding: 1rem;
    border-radius: 8px;
    font-family: monospace;
    font-size: 0.85rem;
    margin: 1rem 0;
}
</style>

<div class="admin-container">
    <div class="admin-header">
        <h1>⚙️ Site Settings</h1>
        <p style="color: var(--text-gray);">Configure site-wide settings and maintenance mode</p>
    </div>

    <div class="admin-nav">
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="users.php">👥 Users</a>
        <a href="listings.php">📝 Listings</a>
        <a href="upgrades.php">💎 Upgrades</a>
        <a href="reports.php">🚨 Reports</a>
        <a href="announcements.php">📢 Announcements</a>
        <a href="categories.php">📁 Categories</a>
        <a href="settings.php" class="active">⚙️ Settings</a>
    </div>

    <?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Debug Info -->
    <div class="admin-section">
        <h2>🔍 Current Database Values</h2>
        <div class="debug-info">
            <?php
            $query = "SELECT * FROM site_settings WHERE setting_key IN ('maintenance_mode', 'coming_soon_mode', 'allow_admin_access')";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $debug_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if(count($debug_settings) > 0) {
                foreach($debug_settings as $setting) {
                    echo "<strong>{$setting['setting_key']}:</strong> {$setting['setting_value']}<br>";
                    echo "Updated: {$setting['updated_at']}<br><br>";
                }
            } else {
                echo "No settings found in database!";
            }
            ?>
        </div>
        
        <form method="POST" style="margin-top: 1rem;">
            <button type="submit" name="test_maintenance" class="btn-secondary">
                Test Maintenance Check
            </button>
        </form>
    </div>

    <!-- Maintenance Mode -->
    <div class="admin-section">
        <h2>🔧 Maintenance Mode</h2>
        <div class="alert alert-warning">
            <strong>⚠️ Warning:</strong> When maintenance mode is enabled, the site will be inaccessible to all users (except admins if admin access is enabled).
        </div>
        
        <form method="POST" action="settings.php">
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-white); font-size: 1.1rem;">
                    <input type="checkbox" name="maintenance_mode" value="1" 
                           <?php echo ($current_settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>
                           style="width: 24px; height: 24px;">
                    <strong>Enable Maintenance Mode</strong>
                </label>
                <p style="color: var(--text-gray); margin-top: 0.5rem; font-size: 0.9rem;">
                    Turn this on to show a maintenance page to all visitors
                </p>
            </div>
            
            <div class="form-group">
                <label>Maintenance Page Title</label>
                <input type="text" name="maintenance_title" value="<?php echo htmlspecialchars($current_settings['maintenance_title'] ?? 'Site Maintenance'); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Maintenance Message</label>
                <textarea name="maintenance_message" rows="5" required><?php echo htmlspecialchars($current_settings['maintenance_message'] ?? 'We are currently performing scheduled maintenance. Please check back soon!'); ?></textarea>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-white);">
                    <input type="checkbox" name="allow_admin_access" value="1" 
                           <?php echo ($current_settings['allow_admin_access'] ?? '1') == '1' ? 'checked' : ''; ?>>
                    Allow admin access during maintenance
                </label>
                <p style="color: var(--text-gray); margin-top: 0.5rem; font-size: 0.9rem;">
                    Admins can access the site normally when this is enabled
                </p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: center;">
                <button type="submit" name="save_maintenance" class="btn-primary">
                    💾 Save Maintenance Settings
                </button>
                <a href="../maintenance.php" target="_blank" class="btn-secondary">
                    Preview Page
                </a>
            </div>
        </form>
    </div>

    <!-- Coming Soon Mode -->
    <div class="admin-section">
        <h2>🚀 Coming Soon Mode</h2>
        <div class="alert alert-info">
            <strong>ℹ️ Info:</strong> Coming soon mode shows a launch countdown page. Use this before your official launch.
        </div>
        
        <form method="POST" action="settings.php">
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-white); font-size: 1.1rem;">
                    <input type="checkbox" name="coming_soon_mode" value="1" 
                           <?php echo ($current_settings['coming_soon_mode'] ?? '0') == '1' ? 'checked' : ''; ?>
                           style="width: 24px; height: 24px;">
                    <strong>Enable Coming Soon Mode</strong>
                </label>
                <p style="color: var(--text-gray); margin-top: 0.5rem; font-size: 0.9rem;">
                    Show a "coming soon" page with countdown timer
                </p>
            </div>
            
            <div class="form-group">
                <label>Coming Soon Message</label>
                <textarea name="coming_soon_message" rows="5" required><?php echo htmlspecialchars($current_settings['coming_soon_message'] ?? 'Turnpage is coming soon! We are working hard to bring you the best local hookup classifieds experience.'); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Launch Date & Time (optional)</label>
                <input type="datetime-local" name="coming_soon_launch_date" value="<?php echo $current_settings['coming_soon_launch_date'] ?? ''; ?>">
                <p style="color: var(--text-gray); margin-top: 0.5rem; font-size: 0.9rem;">
                    Set a launch date to show a countdown timer
                </p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: center;">
                <button type="submit" name="save_coming_soon" class="btn-success">
                    💾 Save Coming Soon Settings
                </button>
                <a href="../maintenance.php" target="_blank" class="btn-secondary">
                    Preview Page
                </a>
            </div>
        </form>
    </div>

    <!-- Current Status -->
    <div class="admin-section">
        <h2>📊 Current Status</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <div class="stat-card">
                <div class="stat-icon">🔧</div>
                <div class="stat-value" style="color: <?php echo ($current_settings['maintenance_mode'] ?? '0') == '1' ? 'var(--danger-red)' : 'var(--success-green)'; ?>">
                    <?php echo ($current_settings['maintenance_mode'] ?? '0') == '1' ? 'ON' : 'OFF'; ?>
                </div>
                <div class="stat-label">Maintenance Mode</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🚀</div>
                <div class="stat-value" style="color: <?php echo ($current_settings['coming_soon_mode'] ?? '0') == '1' ? 'var(--warning-orange)' : 'var(--success-green)'; ?>">
                    <?php echo ($current_settings['coming_soon_mode'] ?? '0') == '1' ? 'ON' : 'OFF'; ?>
                </div>
                <div class="stat-label">Coming Soon Mode</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🛡️</div>
                <div class="stat-value" style="color: var(--info-cyan);">
                    <?php echo ($current_settings['allow_admin_access'] ?? '1') == '1' ? 'YES' : 'NO'; ?>
                </div>
                <div class="stat-label">Admin Access</div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?>