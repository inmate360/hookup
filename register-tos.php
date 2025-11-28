<?php
session_start();
require_once 'config/database.php';
require_once 'includes/maintenance_check.php';
require_once 'includes/session_security.php';
require_once 'includes/security_headers.php';
require_once 'classes/CSRF.php';

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

// If already logged in, redirect
if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

// Handle TOS acceptance
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check CSRF token
    if(!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please refresh and try again.';
    } elseif(isset($_POST['accept_tos']) && isset($_POST['accept_safety']) && isset($_POST['accept_privacy'])) {
        // Store TOS acceptance in session
        $_SESSION['tos_accepted'] = true;
        $_SESSION['tos_accepted_at'] = date('Y-m-d H:i:s');
        
        // Destroy CSRF token
        CSRF::destroyToken();
        
        // Redirect to registration
        header('Location: register.php');
        exit();
    } else {
        $error = 'You must accept all terms to continue';
    }
}

// Generate CSRF token
$csrf_token = CSRF::getToken();

include 'views/header.php';
?>

<style>
.tos-page {
    padding: 2rem 0;
    min-height: 100vh;
}

.tos-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
}

.tos-header {
    text-align: center;
    margin-bottom: 3rem;
}

.tos-content {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 15px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    max-height: 70vh;
    overflow-y: auto;
}

.tos-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--border-color);
}

.tos-section:last-child {
    border-bottom: none;
}

.tos-section h2 {
    color: var(--primary-blue);
    margin-bottom: 1rem;
    font-size: 1.5rem;
}

.tos-section h3 {
    color: var(--text-white);
    margin-bottom: 0.75rem;
    font-size: 1.2rem;
}

.tos-section p {
    color: var(--text-gray);
    line-height: 1.8;
    margin-bottom: 1rem;
}

.tos-section ul {
    color: var(--text-gray);
    line-height: 2;
    margin-left: 2rem;
}

.warning-box {
    background: rgba(239, 68, 68, 0.1);
    border: 2px solid var(--danger-red);
    border-radius: 12px;
    padding: 2rem;
    margin: 2rem 0;
}

.warning-box h3 {
    color: var(--danger-red);
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.info-box {
    background: rgba(66, 103, 245, 0.1);
    border: 2px solid var(--primary-blue);
    border-radius: 12px;
    padding: 1.5rem;
    margin: 1.5rem 0;
}

.acceptance-box {
    background: var(--card-bg);
    border: 2px solid var(--primary-blue);
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
}

.checkbox-label {
    display: flex;
    align-items: start;
    gap: 1rem;
    margin: 1.5rem 0;
    text-align: left;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin-top: 0.3rem;
    width: 20px;
    height: 20px;
    cursor: pointer;
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .tos-content {
        padding: 1.5rem;
        max-height: 60vh;
    }
    
    .tos-section h2 {
        font-size: 1.3rem;
    }
}
</style>

<div class="tos-page">
    <div class="tos-container">
        <div class="tos-header">
            <img src="/logo.png" alt="Turnpage" style="width: 80px; height: 80px; margin-bottom: 1rem;">
            <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">Terms of Service</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">
                Please read carefully before creating your account
            </p>
        </div>

        <?php if($error): ?>
        <div class="alert alert-error" style="margin-bottom: 2rem;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="tos-content">
            <div class="warning-box">
                <h3>🚨 IMPORTANT: NOT AN ESCORT WEBSITE</h3>
                <p style="color: var(--text-white); font-size: 1.1rem; line-height: 1.8; margin-bottom: 1rem;">
                    <strong>Turnpage is a personal classifieds platform for adults seeking consensual connections.</strong>
                </p>
                <ul style="color: var(--text-white); line-height: 2; margin-left: 2rem;">
                    <li><strong>This is NOT an escort service website</strong></li>
                    <li><strong>Solicitation of prostitution is STRICTLY PROHIBITED</strong></li>
                    <li><strong>All activities must be legal and consensual</strong></li>
                    <li><strong>No commercial sex work or monetary exchange for services</strong></li>
                    <li><strong>Violation will result in immediate account termination and potential legal action</strong></li>
                </ul>
            </div>

            <div class="tos-section">
                <h2>1. Agreement to Terms</h2>
                <p>
                    By accessing and using Turnpage ("the Platform", "we", "us", or "our"), you agree to be bound by these Terms of Service. 
                    If you do not agree to these terms, you may not use our services.
                </p>
            </div>

            <div class="tos-section">
                <h2>2. Eligibility and Account Registration</h2>
                <p>
                    You must be at least <strong>18 years of age</strong> to use this platform. By registering, you represent and warrant that you are of legal age.
                </p>
            </div>

            <div class="tos-section">
                <h2>3. Prohibited Activities</h2>
                <div class="warning-box" style="margin: 1rem 0;">
                    <h3>🚫 Absolutely Prohibited</h3>
                    <ul style="color: var(--text-white); line-height: 2;">
                        <li><strong>Prostitution, escort services, or any form of commercial sex work</strong></li>
                        <li><strong>Solicitation of illegal services</strong></li>
                        <li><strong>Human trafficking or exploitation</strong></li>
                        <li><strong>Involvement of minors in any capacity</strong></li>
                    </ul>
                </div>
            </div>

            <div class="tos-section">
                <h2>4. Safety Guidelines</h2>
                <div class="info-box">
                    <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">🛡️ Essential Safety Rules</h3>
                    <ul style="color: var(--text-gray); line-height: 2; margin: 0;">
                        <li>Always meet in public places for first meetings</li>
                        <li>Tell a friend or family member where you're going</li>
                        <li>Trust your instincts - if something feels wrong, leave</li>
                        <li>Never send money to someone you haven't met in person</li>
                    </ul>
                </div>
            </div>

            <div class="tos-section" style="border: none;">
                <p style="color: var(--text-gray); font-size: 0.9rem; font-style: italic;">
                    Last Updated: January 17, 2025<br>
                    Version: 1.0
                </p>
            </div>
        </div>

        <div class="acceptance-box">
            <h2 style="color: var(--primary-blue); margin-bottom: 1.5rem;">Accept Terms to Continue</h2>
            
            <form method="POST" action="register-tos.php" id="tosForm">
                <?php echo CSRF::getHiddenInput(); ?>
                
                <label class="checkbox-label">
                    <input type="checkbox" name="accept_tos" id="acceptTos" required>
                    <span style="color: var(--text-white); line-height: 1.6;">
                        I certify that I am at least 18 years old, have read and understand these Terms of Service, 
                        and agree to abide by them. I understand that Turnpage is <strong>NOT an escort website</strong> 
                        and that solicitation of prostitution or illegal services is strictly prohibited.
                    </span>
                </label>

                <label class="checkbox-label">
                    <input type="checkbox" name="accept_safety" id="acceptSafety" required>
                    <span style="color: var(--text-white); line-height: 1.6;">
                        I understand the safety guidelines and agree to practice safe interactions, including meeting in 
                        public places and never sending money to people I haven't met in person.
                    </span>
                </label>

                <label class="checkbox-label">
                    <input type="checkbox" name="accept_privacy" id="acceptPrivacy" required>
                    <span style="color: var(--text-white); line-height: 1.6;">
                        I have read and agree to the <a href="privacy.php" target="_blank" style="color: var(--primary-blue);">Privacy Policy</a> 
                        and consent to the collection and use of my data as described.
                    </span>
                </label>

                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn-primary" style="font-size: 1.1rem; padding: 1rem 3rem;" id="continueBtn" disabled>
                        I Accept - Continue to Registration →
                    </button>
                </div>

                <div style="margin-top: 1.5rem;">
                    <a href="index.php" class="btn-secondary">
                        I Do Not Accept - Go Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Enable continue button only when all checkboxes are checked
const checkboxes = document.querySelectorAll('input[type="checkbox"]');
const continueBtn = document.getElementById('continueBtn');

checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        continueBtn.disabled = !allChecked;
    });
});
</script>

<?php include 'views/footer.php'; ?>