<?php
session_start();
require_once 'config/database.php';
require_once 'classes/UserProfile.php';
require_once 'classes/Location.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$userProfile = new UserProfile($db);
$location = new Location($db);

$profile = $userProfile->getProfile($_SESSION['user_id']);
$userLocation = $userProfile->getUserLocation($_SESSION['user_id']);
$states = $location->getAllStates();

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'bio' => $_POST['bio'] ?? '',
        'height' => !empty($_POST['height']) ? $_POST['height'] : null,
        'body_type' => $_POST['body_type'] ?? null,
        'ethnicity' => $_POST['ethnicity'] ?? '',
        'relationship_status' => $_POST['relationship_status'] ?? null,
        'looking_for' => isset($_POST['looking_for']) ? $_POST['looking_for'] : [],
        'interests' => isset($_POST['interests']) ? explode(',', $_POST['interests']) : [],
        'occupation' => $_POST['occupation'] ?? '',
        'education' => $_POST['education'] ?? null,
        'smoking' => $_POST['smoking'] ?? null,
        'drinking' => $_POST['drinking'] ?? null,
        'has_kids' => isset($_POST['has_kids']) ? (bool)$_POST['has_kids'] : false,
        'wants_kids' => $_POST['wants_kids'] ?? null,
        'languages' => isset($_POST['languages']) ? explode(',', $_POST['languages']) : [],
        'display_distance' => isset($_POST['display_distance']) ? true : false,
        'show_age' => isset($_POST['show_age']) ? true : false,
        'show_online_status' => isset($_POST['show_online_status']) ? true : false
    ];
    
    if($userProfile->saveProfile($_SESSION['user_id'], $data)) {
        // Update location if provided
        if(!empty($_POST['city_id'])) {
            // Get city coordinates (you would normally geocode the city)
            $city = $location->getCityById($_POST['city_id']);
            if($city) {
                // For demo, use approximate city center coordinates
                // In production, use a geocoding service
                $userProfile->saveLocation(
                    $_SESSION['user_id'],
                    $_POST['city_id'],
                    null, // latitude - would be geocoded
                    null, // longitude - would be geocoded
                    $_POST['postal_code'] ?? null,
                    $_POST['max_distance'] ?? 50
                );
            }
        }
        
        $success = 'Profile updated successfully!';
        $profile = $userProfile->getProfile($_SESSION['user_id']);
    } else {
        $error = 'Failed to update profile';
    }
}

include 'views/header.php';
?>

<div class="page-content">
    <div class="container-narrow">
        <div class="card">
            <h2>Edit Profile</h2>
            
            <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="edit-profile.php">
                <div class="form-group">
                    <label>Bio / About Me</label>
                    <textarea name="bio" rows="6" placeholder="Tell people about yourself..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                </div>
                
                <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Physical Attributes</h3>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div class="form-group">
                        <label>Height (cm)</label>
                        <input type="number" name="height" min="100" max="250" value="<?php echo $profile['height'] ?? ''; ?>" placeholder="e.g., 175">
                        <small style="color: var(--text-gray);">Optional</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Body Type</label>
                        <select name="body_type">
                            <option value="">Select...</option>
                            <option value="slim" <?php echo ($profile['body_type'] ?? '') == 'slim' ? 'selected' : ''; ?>>Slim</option>
                            <option value="athletic" <?php echo ($profile['body_type'] ?? '') == 'athletic' ? 'selected' : ''; ?>>Athletic</option>
                            <option value="average" <?php echo ($profile['body_type'] ?? '') == 'average' ? 'selected' : ''; ?>>Average</option>
                            <option value="curvy" <?php echo ($profile['body_type'] ?? '') == 'curvy' ? 'selected' : ''; ?>>Curvy</option>
                            <option value="muscular" <?php echo ($profile['body_type'] ?? '') == 'muscular' ? 'selected' : ''; ?>>Muscular</option>
                            <option value="heavyset" <?php echo ($profile['body_type'] ?? '') == 'heavyset' ? 'selected' : ''; ?>>Heavyset</option>
                            <option value="other" <?php echo ($profile['body_type'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Ethnicity</label>
                    <input type="text" name="ethnicity" value="<?php echo htmlspecialchars($profile['ethnicity'] ?? ''); ?>" placeholder="e.g., Caucasian, Asian, Hispanic, etc.">
                </div>
                
                <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Lifestyle</h3>
                
                <div class="form-group">
                    <label>Relationship Status</label>
                    <select name="relationship_status">
                        <option value="">Select...</option>
                        <option value="single" <?php echo ($profile['relationship_status'] ?? '') == 'single' ? 'selected' : ''; ?>>Single</option>
                        <option value="married" <?php echo ($profile['relationship_status'] ?? '') == 'married' ? 'selected' : ''; ?>>Married</option>
                        <option value="divorced" <?php echo ($profile['relationship_status'] ?? '') == 'divorced' ? 'selected' : ''; ?>>Divorced</option>
                        <option value="widowed" <?php echo ($profile['relationship_status'] ?? '') == 'widowed' ? 'selected' : ''; ?>>Widowed</option>
                        <option value="separated" <?php echo ($profile['relationship_status'] ?? '') == 'separated' ? 'selected' : ''; ?>>Separated</option>
                        <option value="in_relationship" <?php echo ($profile['relationship_status'] ?? '') == 'in_relationship' ? 'selected' : ''; ?>>In a Relationship</option>
                        <option value="complicated" <?php echo ($profile['relationship_status'] ?? '') == 'complicated' ? 'selected' : ''; ?>>It's Complicated</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Looking For (select multiple)</label>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;">
                        <?php 
                        $looking_for_options = ['Friendship', 'Dating', 'Relationship', 'Casual', 'Activity Partner', 'Long-term'];
                        $current_looking = $profile['looking_for'] ?? [];
                        foreach($looking_for_options as $option): 
                        ?>
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-white);">
                            <input type="checkbox" name="looking_for[]" value="<?php echo $option; ?>" <?php echo in_array($option, $current_looking) ? 'checked' : ''; ?>>
                            <?php echo $option; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Interests (comma-separated)</label>
                    <input type="text" name="interests" value="<?php echo htmlspecialchars(implode(', ', $profile['interests'] ?? [])); ?>" placeholder="e.g., hiking, movies, cooking, travel">
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div class="form-group">
                        <label>Occupation</label>
                        <input type="text" name="occupation" value="<?php echo htmlspecialchars($profile['occupation'] ?? ''); ?>" placeholder="Your job or profession">
                    </div>
                    
                    <div class="form-group">
                        <label>Education</label>
                        <select name="education">
                            <option value="">Select...</option>
                            <option value="high_school" <?php echo ($profile['education'] ?? '') == 'high_school' ? 'selected' : ''; ?>>High School</option>
                            <option value="some_college" <?php echo ($profile['education'] ?? '') == 'some_college' ? 'selected' : ''; ?>>Some College</option>
                            <option value="bachelors" <?php echo ($profile['education'] ?? '') == 'bachelors' ? 'selected' : ''; ?>>Bachelor's Degree</option>
                            <option value="masters" <?php echo ($profile['education'] ?? '') == 'masters' ? 'selected' : ''; ?>>Master's Degree</option>
                            <option value="phd" <?php echo ($profile['education'] ?? '') == 'phd' ? 'selected' : ''; ?>>PhD</option>
                            <option value="other" <?php echo ($profile['education'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div class="form-group">
                        <label>Smoking</label>
                        <select name="smoking">
                            <option value="">Select...</option>
                            <option value="never" <?php echo ($profile['smoking'] ?? '') == 'never' ? 'selected' : ''; ?>>Never</option>
                            <option value="occasionally" <?php echo ($profile['smoking'] ?? '') == 'occasionally' ? 'selected' : ''; ?>>Occasionally</option>
                            <option value="regularly" <?php echo ($profile['smoking'] ?? '') == 'regularly' ? 'selected' : ''; ?>>Regularly</option>
                            <option value="trying_to_quit" <?php echo ($profile['smoking'] ?? '') == 'trying_to_quit' ? 'selected' : ''; ?>>Trying to Quit</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Drinking</label>
                        <select name="drinking">
                            <option value="">Select...</option>
                            <option value="never" <?php echo ($profile['drinking'] ?? '') == 'never' ? 'selected' : ''; ?>>Never</option>
                            <option value="socially" <?php echo ($profile['drinking'] ?? '') == 'socially' ? 'selected' : ''; ?>>Socially</option>
                            <option value="regularly" <?php echo ($profile['drinking'] ?? '') == 'regularly' ? 'selected' : ''; ?>>Regularly</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div class="form-group">
                        <label>Have Kids?</label>
                        <select name="has_kids">
                            <option value="0" <?php echo !($profile['has_kids'] ?? false) ? 'selected' : ''; ?>>No</option>
                            <option value="1" <?php echo ($profile['has_kids'] ?? false) ? 'selected' : ''; ?>>Yes</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Want Kids?</label>
                        <select name="wants_kids">
                            <option value="">Select...</option>
                            <option value="yes" <?php echo ($profile['wants_kids'] ?? '') == 'yes' ? 'selected' : ''; ?>>Yes</option>
                            <option value="no" <?php echo ($profile['wants_kids'] ?? '') == 'no' ? 'selected' : ''; ?>>No</option>
                            <option value="maybe" <?php echo ($profile['wants_kids'] ?? '') == 'maybe' ? 'selected' : ''; ?>>Maybe</option>
                            <option value="have_and_want_more" <?php echo ($profile['wants_kids'] ?? '') == 'have_and_want_more' ? 'selected' : ''; ?>>Have and Want More</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Languages (comma-separated)</label>
                    <input type="text" name="languages" value="<?php echo htmlspecialchars(implode(', ', $profile['languages'] ?? [])); ?>" placeholder="e.g., English, Spanish, French">
                </div>
                
                <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Location</h3>
                
                <div class="form-group">
                    <label>State</label>
                    <select name="state_id" id="stateSelect">
                        <option value="">Select State</option>
                        <?php foreach($states as $state): ?>
                        <option value="<?php echo $state['id']; ?>">
                            <?php echo htmlspecialchars($state['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>City</label>
                    <select name="city_id" id="citySelect">
                        <option value="">Select State First</option>
                        <?php if($userLocation && $userLocation['city_id']): ?>
                        <?php $city = $location->getCityById($userLocation['city_id']); ?>
                        <option value="<?php echo $city['id']; ?>" selected><?php echo htmlspecialchars($city['name']); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Postal Code (Optional)</label>
                    <input type="text" name="postal_code" value="<?php echo htmlspecialchars($userLocation['postal_code'] ?? ''); ?>" placeholder="12345">
                </div>
                
                <div class="form-group">
                    <label>Maximum Distance for Matches (miles)</label>
                    <input type="number" name="max_distance" min="1" max="500" value="<?php echo $userLocation['max_distance'] ?? 50; ?>">
                </div>
                
                <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Privacy Settings</h3>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-white);">
                        <input type="checkbox" name="display_distance" <?php echo ($profile['display_distance'] ?? true) ? 'checked' : ''; ?>>
                        Display distance on my profile
                    </label>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-white);">
                        <input type="checkbox" name="show_age" <?php echo ($profile['show_age'] ?? true) ? 'checked' : ''; ?>>
                        Show my age on profile
                    </label>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-white);">
                        <input type="checkbox" name="show_online_status" <?php echo ($profile['show_online_status'] ?? true) ? 'checked' : ''; ?>>
                        Show when I'm online
                    </label>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn-primary" style="flex: 1;">Save Profile</button>
                    <a href="profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="btn-secondary" style="flex: 1; text-align: center;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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
            citySelect.innerHTML = '<option value="">Select City</option>';
            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error fetching cities:', error);
        });
});
</script>

<?php include 'views/footer.php'; ?>