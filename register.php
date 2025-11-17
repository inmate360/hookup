<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $user->email = $_POST['email'];
    $user->username = $_POST['username'];
    $user->password = $_POST['password'];
    $user->phone = $_POST['phone'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if($user->password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif(strlen($user->password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif($user->emailExists()) {
        $error = 'Email already registered';
    } else {
        if($user->register()) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['email'] = $user->email;
            
            header('Location: choose-location.php');
            exit();
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}

include 'views/header.php';
?>

<div class="page-content">
    <div class="container-narrow">
        <div class="card" style="max-width: 500px; margin: 3rem auto;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">🎉</div>
                <h2>Join Turnpage</h2>
                <p style="color: var(--text-gray); margin-top: 0.5rem;">Create your free account and start posting!</p>
            </div>
            
            <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="register.php">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Phone (optional)</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Password (min 6 characters)</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                
                <div class="alert alert-info">
                    By signing up, you agree to our <a href="terms.php" style="color: var(--primary-blue);">Terms of Service</a> and <a href="privacy.php" style="color: var(--primary-blue);">Privacy Policy</a>.
                </div>
                
                <button type="submit" class="btn-primary btn-block">Create Account</button>
            </form>
            
            <p style="text-align: center; margin-top: 1.5rem; color: var(--text-gray);">
                Already have an account? <a href="login.php" style="color: var(--primary-blue);">Login here</a>
            </p>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>