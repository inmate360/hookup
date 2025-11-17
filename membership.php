<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get membership plans
$query = "SELECT * FROM membership_plans WHERE is_active = TRUE ORDER BY price ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$plans = $stmt->fetchAll();

// Check current subscription if logged in
$current_plan = null;
if(isset($_SESSION['user_id'])) {
    $query = "SELECT us.*, mp.name as plan_name, mp.features 
              FROM user_subscriptions us
              LEFT JOIN membership_plans mp ON us.plan_id = mp.id
              WHERE us.user_id = :user_id AND us.status = 'active'
              ORDER BY us.created_at DESC
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $current_plan = $stmt->fetch();
}

include 'views/header.php';
?>

<div class="page-content">
    <div class="container">
        <div style="text-align: center; margin-bottom: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">💎</div>
            <h1>Upgrade to Premium</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem; max-width: 600px; margin: 1rem auto;">
                Get more visibility, advanced features, and stand out from the crowd
            </p>
        </div>

        <?php if($current_plan): ?>
        <div class="alert alert-success" style="max-width: 600px; margin: 0 auto 2rem;">
            <strong>🎉 You're currently on the <?php echo htmlspecialchars($current_plan['plan_name']); ?> plan!</strong>
            <p style="margin-top: 0.5rem;">
                Expires: <?php echo date('M j, Y', strtotime($current_plan['end_date'])); ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="pricing-grid">
            <?php foreach($plans as $index => $plan): ?>
            <div class="pricing-card <?php echo $index == 1 ? 'featured' : ''; ?>">
                <?php if($index == 1): ?>
                <div class="popular-badge">Most Popular</div>
                <?php endif; ?>
                
                <div class="pricing-header">
                    <div class="plan-icon">
                        <?php 
                        $icons = ['🆓', '⭐', '👑', '💎'];
                        echo $icons[$plan['id']] ?? '📦';
                        ?>
                    </div>
                    <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
                    <div class="price">
                        <span class="currency">$</span>
                        <span class="amount"><?php echo number_format($plan['price'], 2); ?></span>
                        <span class="period">/<?php echo $plan['duration_days']; ?> days</span>
                    </div>
                </div>

                <div class="pricing-features">
                    <?php 
                    $features = json_decode($plan['features'], true);
                    if($features):
                        foreach($features as $feature):
                    ?>
                    <div class="feature-item">
                        <span class="check">✓</span>
                        <span><?php echo htmlspecialchars($feature); ?></span>
                    </div>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>

                <div class="pricing-footer">
                    <?php if($plan['id'] == 1): ?>
                    <a href="register.php" class="btn-secondary btn-block">Get Started</a>
                    <?php elseif($current_plan && $current_plan['plan_id'] == $plan['id']): ?>
                    <button class="btn-success btn-block" disabled>Current Plan</button>
                    <?php else: ?>
                    <a href="checkout.php?plan=<?php echo $plan['id']; ?>" class="btn-primary btn-block">
                        Upgrade Now
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card" style="max-width: 800px; margin: 3rem auto 0;">
            <h2 style="text-align: center; margin-bottom: 2rem;">Why Go Premium?</h2>
            
            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon">🚀</div>
                    <h3>Featured Ads</h3>
                    <p>Your ads appear at the top of search results</p>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">🕶️</div>
                    <h3>Incognito Mode</h3>
                    <p>Browse profiles anonymously without leaving traces</p>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">💬</div>
                    <h3>Unlimited Messages</h3>
                    <p>Send unlimited messages to other users</p>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">🔍</div>
                    <h3>Advanced Filters</h3>
                    <p>Use powerful search filters to find exactly what you want</p>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">📊</div>
                    <h3>Detailed Analytics</h3>
                    <p>See who viewed your ads and profile</p>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">⚡</div>
                    <h3>Priority Support</h3>
                    <p>Get faster response from our support team</p>
                </div>
            </div>
        </div>

        <div class="card" style="max-width: 600px; margin: 2rem auto 0; text-align: center;">
            <h3 style="margin-bottom: 1rem;">Have Questions?</h3>
            <p style="color: var(--text-gray); margin-bottom: 1.5rem;">
                Contact our support team for help with membership plans
            </p>
            <a href="contact.php" class="btn-secondary">Contact Support</a>
        </div>
    </div>
</div>

<style>
.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin: 0 auto;
    max-width: 1200px;
}

.pricing-card {
    background: var(--card-bg);
    border-radius: 12px;
    border: 2px solid var(--border-color);
    padding: 2rem;
    position: relative;
    transition: all 0.3s;
}

.pricing-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 40px rgba(66, 103, 245, 0.3);
}

.pricing-card.featured {
    border-color: var(--primary-blue);
    box-shadow: 0 0 30px rgba(66, 103, 245, 0.2);
}

.popular-badge {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: bold;
    box-shadow: 0 4px 15px rgba(66, 103, 245, 0.4);
}

.pricing-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--border-color);
}

.plan-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.pricing-header h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--text-white);
}

.price {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 0.3rem;
}

.currency {
    font-size: 1.5rem;
    color: var(--primary-blue);
}

.amount {
    font-size: 3rem;
    font-weight: bold;
    color: var(--primary-blue);
}

.period {
    font-size: 1rem;
    color: var(--text-gray);
}

.pricing-features {
    margin-bottom: 2rem;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding: 0.5rem;
}

.feature-item .check {
    color: var(--success-green);
    font-weight: bold;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.feature-item span:last-child {
    color: var(--text-gray);
    font-size: 0.95rem;
}

.pricing-footer {
    margin-top: auto;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.benefit-item {
    text-align: center;
    padding: 1.5rem;
    background: rgba(66, 103, 245, 0.05);
    border-radius: 12px;
    transition: all 0.3s;
}

.benefit-item:hover {
    background: rgba(66, 103, 245, 0.1);
    transform: translateY(-3px);
}

.benefit-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.benefit-item h3 {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    color: var(--primary-blue);
}

.benefit-item p {
    color: var(--text-gray);
    font-size: 0.9rem;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .pricing-grid {
        grid-template-columns: 1fr;
    }
    
    .benefits-grid {
        grid-template-columns: 1fr;
    }
    
    .amount {
        font-size: 2.5rem;
    }
}
</style>

<?php include 'views/footer.php'; ?>