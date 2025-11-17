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
    $user->password = $_POST['password'];
    
    if($user->login()) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['email'] = $user->email;
        
        // Redirect to intended page or home
        $redirect = $_GET['redirect'] ?? 'choose-location.php';
        header('Location: ' . $redirect);
        exit();
    } else {
        $error = 'Invalid email or password';
    }
}

include 'views/header.php';
?>

<div class="page-content">
    <div class="container-narrow">
        <div class="card" style="max-width: 450px; margin: 3rem auto;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">🔐</div>
                <h2>Login to Turnpage</h2>
                <p style="color: var(--text-gray); margin-top: 0.5rem;">Post and browse local hookup ads.</p>
            </div>
            
            <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary btn-block">Login</button>
            </form>
            
            <p style="text-align: center; margin-top: 1.5rem; color: var(--text-gray);">
                Don't have an account? <a href="register.php" style="color: var(--primary-blue);">Register here</a>
            </p>
            
            <p style="text-align: center; margin-top: 1rem; color: var(--text-gray);">
                <a href="forgot-password.php" style="color: var(--primary-blue);">Forgot your password?</a>
            </p>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>