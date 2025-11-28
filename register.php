<?php
session_start();
require_once 'config/database.php';
require_once 'includes/maintenance_check.php';
require_once 'includes/session_security.php';
require_once 'includes/security_headers.php';
require_once 'classes/CSRF.php';
require_once 'classes/RateLimiter.php';
require_once 'classes/InputSanitizer.php';
require_once 'classes/EmailVerification.php';
require_once 'classes/SpamProtection.php';

// Set security headers
SecurityHeaders::set();

// Initialize secure session
SessionSecurity::init();

$database = new Database();
$db = $database->getConnection();

// Check for maintenance mode
if(checkMaintenanceMode($db)) {
    header('Location: maintenance.php');
    exit();
}

// Check if user already logged in
if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if TOS was accepted
if(!isset($_SESSION['tos_accepted']) || !$_SESSION['tos_accepted']) {
    header('Location: register-tos.php');
    exit();
}

// Initialize security classes
$rateLimiter = new RateLimiter($db);
$emailVerification = new EmailVerification($db);
$spamProtection = new SpamProtection($db);

$error = '';
$success = '';

// Check if IP is blocked
$ipCheck = $spamProtection->isBlocked();
if($ipCheck) {
    $error = 'Access denied. ' . ($ipCheck['reason'] ?? 'Please contact support.');
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && !$ipCheck) {
    // Check CSRF token
    if(!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Check rate limit
        $identifier = RateLimiter::getIdentifier();
        $rateCheck = $rateLimiter->checkLimit($identifier, 'register', 3, 3600); // 3 attempts per hour
        
        if(!$rateCheck['allowed']) {
            $minutes = ceil($rateCheck['retry_after'] / 60);
            $error = "Too many registration attempts. Please try again in {$minutes} minutes.";
        } else {
            // Sanitize inputs
            $username = InputSanitizer::cleanUsername($_POST['username']);
            $email = InputSanitizer::cleanEmail($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $age_verification = isset($_POST['age_verification']);
            
            // Validation
            if(empty($username) || empty($email) || empty($password)) {
                $error = 'All fields are required';
            } elseif($username === false) {
                $error = 'Invalid username. Only letters, numbers, underscore and hyphen allowed.';
            } elseif($email === false) {
                $error = 'Invalid email address';
            } elseif(!$age_verification) {
                $error = 'You must confirm you are 18 years or older';
            } elseif(strlen($username) < 3 || strlen($username) > 30) {
                $error = 'Username must be between 3 and 30 characters';
            } elseif(strlen($password) < 8) {
                $error = 'Password must be at least 8 characters';
            } elseif(!preg_match('/[A-Z]/', $password)) {
                $error = 'Password must contain at least one uppercase letter';
            } elseif(!preg_match('/[a-z]/', $password)) {
                $error = 'Password must contain at least one lowercase letter';
            } elseif(!preg_match('/[0-9]/', $password)) {
                $error = 'Password must contain at least one number';
            } elseif($password !== $confirm_password) {
                $error = 'Passwords do not match';
            } elseif(InputSanitizer::detectXSS($username . $email)) {
                $error = 'Invalid characters detected';
                $spamProtection->blockIP(RateLimiter::getClientIP(), 'XSS attempt', 86400);
            } else {
                // Check if username already exists
                $query = "SELECT id FROM users WHERE username = :username LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    $error = 'Username already taken';
                } else {
                    // Check if email already exists
                    $query = "SELECT id FROM users WHERE email = :email LIMIT 1";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if($stmt->rowCount() > 0) {
                        $error = 'Email already registered';
                    } else {
                        // Create account
                        $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
                        $tos_accepted_at = $_SESSION['tos_accepted_at'] ?? date('Y-m-d H:i:s');
                        $ip = RateLimiter::getClientIP();
                        
                        $query = "INSERT INTO users (username, email, password, tos_accepted_at, registration_ip, created_at) 
                                  VALUES (:username, :email, :password, :tos_accepted_at, :ip, NOW())";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':username', $username);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':password', $hashed_password);
                        $stmt->bindParam(':tos_accepted_at', $tos_accepted_at);
                        $stmt->bindParam(':ip', $ip);
                        
                        if($stmt->execute()) {
                            $user_id = $db->lastInsertId();
                            
                            // Send email verification
                            $emailVerification->sendVerification($user_id, $email);
                            
                            // Log in the user
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['username'] = $username;
                            $_SESSION['email'] = $email;
                            $_SESSION['email_verified'] = false;
                            
                            // Clear TOS session data
                            unset($_SESSION['tos_accepted']);
                            unset($_SESSION['tos_accepted_at']);
                            
                            // Destroy CSRF token
                            CSRF::destroyToken();
                            
                            // Redirect to email verification notice
                            header('Location: verify-email-notice.php');
                            exit();
                        } else {
                            $error = 'Registration failed. Please try again.';
                        }
                    }
                }
            }
        }
    }
}

// Generate new CSRF token
$csrf_token = CSRF::getToken();

include 'views/header.php';
?>

<div class="page-content">
    <div class="container-narrow">
        <div class="card" style="max-width: 500px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <img src="/logo.png" alt="Turnpage" style="width: 80px; height: 80px; margin-bottom: 1rem;">
                <h2 style="color: var(--primary-blue);">Create Your Account</h2>
                <p style="color: var(--text-gray); margin-top: 0.5rem;">
                    Join thousands of people connecting locally
                </p>
            </div>

            <?php if($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="alert alert-info" style="margin-bottom: 2rem;">
                <strong>✓ Terms Accepted</strong><br>
                Thank you for reviewing and accepting our Terms of Service.
            </div>

            <form method="POST" action="register.php">
                <?php echo CSRF::getHiddenInput(); ?>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required minlength="3" maxlength="30"
                           pattern="[a-zA-Z0-9_-]+"
                           placeholder="Choose a unique username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    <small style="color: var(--text-gray);">3-30 characters. Letters, numbers, underscore, and hyphen only.</small>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required 
                           placeholder="your@email.com"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required minlength="8" 
                           placeholder="At least 8 characters"
                           id="password">
                    <small style="color: var(--text-gray);">Must contain uppercase, lowercase, and number.</small>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required 
                           placeholder="Re-enter your password">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-white);">
                        <input type="checkbox" name="age_verification" required>
                        I certify that I am 18 years of age or older
                    </label>
                </div>

                <div class="alert alert-warning" style="font-size: 0.9rem;">
                    <strong>🔒 Security Notice:</strong> Your password will be encrypted and your email will require verification.
                </div>

                <button type="submit" class="btn-primary btn-block">
                    Create Account
                </button>

                <p style="text-align: center; margin-top: 1.5rem; color: var(--text-gray);">
                    Already have an account? 
                    <a href="login.php" style="color: var(--primary-blue);">Login here</a>
                </p>
            </form>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>