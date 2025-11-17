<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Category.php';
require_once 'classes/Listing.php';
require_once 'classes/Subscription.php';
require_once 'classes/Location.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$category = new Category($db);
$listing = new Listing($db);
$subscription = new Subscription($db);
$location = new Location($db);

$categories = $category->getAll();
$states = $location->getAllStates();

// Check if user can post
$can_post = $subscription->canUserPost($_SESSION['user_id']);

// Get city from URL or session
$selected_city = null;
if(isset($_GET['city'])) {
    $selected_city = $location->getCityBySlug($_GET['city']);
} elseif(isset($_SESSION['current_city_id'])) {
    $selected_city = $location->getCityById($_SESSION['current_city_id']);
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if user can post
    if(!$can_post['can_post']) {
        $error = $can_post['reason'];
    } else {
        $listing->user_id = $_SESSION['user_id'];
        $listing->category_id = $_POST['category_id'];
        $listing->title = $_POST['title'];
        $listing->description = $_POST['description'];
        $listing->location = $_POST['location'];
        $listing->age = !empty($_POST['age']) ? $_POST['age'] : null;
        $listing->gender = $_POST['gender'];
        $listing->seeking = $_POST['seeking'];
        
        // Set city_id
        $city_id = !empty($_POST['city_id']) ? $_POST['city_id'] : ($_SESSION['current_city_id'] ?? null);
        
        if($listing->create()) {
            // Update city_id
            if($city_id) {
                $query = "UPDATE listings SET city_id = :city_id WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':city_id', $city_id);
                $stmt->bindParam(':id', $listing->id);
                $stmt->execute();
                
                // Update city post count
                $location->updatePostCount($city_id);
            }
            
            $_SESSION['success'] = 'Listing created successfully! You can now add images.';
            header('Location: manage-images.php?listing_id=' . $listing->id);
            exit();
        } else {
            $error = 'Failed to create listing';
        }
    }
}

// Pre-select category from URL
$selected_category = isset($_GET['category']) ? $_GET['category'] : null;
$selected_category_id = null;
if($selected_category) {
    $cat = $category->getBySlug($selected_category);
    $selected_category_id = $cat ? $cat['id'] : null;
}

include 'views/header.php';
?>

<div class="page-content">
    <div class="container-narrow">
        <div class="card">
            <h2>Create New Listing</h2>
            
            <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!$can_post['can_post']): ?>
            <div class="alert alert-warning">
                <?php echo $can_post['reason']; ?><br>
                <a href="membership.php" style="color: var(--primary-purple); font-weight: bold;">Upgrade your membership</a>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                You have <?php echo $can_post['remaining']; ?> listing slot(s) remaining.
            </div>
            <?php endif; ?>
            
            <form method="POST" action="create-listing.php">
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $selected_category_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" required maxlength="255" placeholder="e.g., Looking for a fun date tonight">
                </div>
                
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" required rows="6" placeholder="Tell us more about what you're looking for..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Location *</label>
                    <input type="text" name="location" required placeholder="e.g., Los Angeles, CA" value="<?php echo $selected_city ? htmlspecialchars($selected_city['name'] . ', ' . $selected_city['state_abbr']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>State *</label>
                    <select name="state_id" id="stateSelect" required>
                        <option value="">Select State</option>
                        <?php foreach($states as $state): ?>
                        <option value="<?php echo $state['id']; ?>" <?php echo ($selected_city && $selected_city['state_abbr'] == $state['abbreviation']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($state['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>City *</label>
                    <select name="city_id" id="citySelect" required>
                        <option value="">Select State First</option>
                        <?php if($selected_city): ?>
                        <option value="<?php echo $selected_city['id']; ?>" selected><?php echo htmlspecialchars($selected_city['name']); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label>Your Age</label>
                        <input type="number" name="age" min="18" max="99" placeholder="18+">
                    </div>
                    
                    <div class="form-group">
                        <label>Your Gender</label>
                        <select name="gender">
                            <option value="">Select...</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="couple">Couple</option>
                            <option value="trans">Trans</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Seeking</label>
                        <select name="seeking">
                            <option value="any">Anyone</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="couple">Couple</option>
                            <option value="trans">Trans</option>
                        </select>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <strong>Note:</strong> Your listing will be active for 30 days and will automatically expire. You can add images after creating the listing.
                </div>
                
                <button type="submit" class="btn-primary btn-block" <?php echo !$can_post['can_post'] ? 'disabled' : ''; ?>>
                    Post Listing
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-trigger city fetch if state is pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const stateSelect = document.getElementById('stateSelect');
    if(stateSelect.value) {
        stateSelect.dispatchEvent(new Event('change'));
    }
});

document.getElementById('stateSelect').addEventListener('change', function() {
    const stateId = this.value;
    const citySelect = document.getElementById('citySelect');
    
    if(!stateId) {
        citySelect.innerHTML = '<option value="">Select State First</option>';
        return;
    }
    
    fetch('get-cities.php?state_id=' + stateId)
        .then(response => response.json())
        .then(cities => {
            const currentCityId = citySelect.querySelector('option[selected]')?.value;
            citySelect.innerHTML = '<option value="">Select City</option>';
            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                if(currentCityId && city.id == currentCityId) {
                    option.selected = true;
                }
                citySelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error fetching cities:', error);
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
        });
});
</script>

<?php include 'views/footer.php'; ?>