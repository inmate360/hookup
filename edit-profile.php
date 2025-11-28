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

// Handle avatar upload/removal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_avatar'])) {
    if (isset($_POST['remove_avatar'])) {
        $userProfile->removeAvatar($_SESSION['user_id']);
        $success = 'Profile photo removed.';
    } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
        $result = $userProfile->uploadAvatar($_SESSION['user_id'], $_FILES['avatar']);
        if($result === true) {
            $success = 'Profile photo updated!';
        } else {
            $error = $result;
        }
    }
    $profile = $userProfile->getProfile($_SESSION['user_id']);
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['change_avatar'])) {
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
            $city = $location->getCityById($_POST['city_id']);
            if($city) {
                $userProfile->saveLocation(
                    $_SESSION['user_id'],
                    $_POST['city_id'],
                    null,
                    null,
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
            <div style="text-align:center; margin-bottom: 2rem;">
                <div style="display:inline-block;position:relative;">
                    <img src="<?php echo htmlspecialchars($profile['avatar'] ?? '/assets/images/default-avatar.png'); ?>" alt="Avatar" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid var(--primary-blue); background:#222;">
                    <div style="position: absolute; bottom: -10px; right: -10px; display: flex; gap: 7px;">
                        <?php if($profile['is_admin'] ?? false): ?>
                            <span title="Admin" style="background:#ef4444;color:white;border-radius:50%;padding:4px 8px;font-size:1.1rem;">🛡️</span>
                        <?php endif; ?>
                        <?php if($profile['verified'] ?? false): ?>
                            <span title="Verified" style="background:#3b82f6;color:white;border-radius:50%;padding:4px 8px;font-size:1.1rem;">✔️</span>
                        <?php endif; ?>
                        <?php if($profile['creator'] ?? false): ?>
                            <span title="Creator" style="background:#f59e0b;color:white;border-radius:50%;padding:4px 8px;font-size:1.1rem;">⭐</span>
                        <?php endif; ?>
                        <?php if($profile['is_moderator'] ?? false): ?>
                            <span title="Moderator" style="background:#06b6d4;color:white;border-radius:50%;padding:4px 8px;font-size:1.1rem;">🛡️</span>
                        <?php endif; ?>
                    </div>
                </div>
                <form method="POST" action="edit-profile.php" enctype="multipart/form-data" style="margin-top:10px;">
                    <label for="avatar" style="display:inline-block;cursor:pointer;">
                        <span class="btn-secondary">Change Photo</span>
                        <input type="file" name="avatar" id="avatar" style="display:none;" accept="image/png, image/jpeg">
                    </label>
                    <?php if(!empty($profile['avatar'])): ?>
                        <button type="submit" name="remove_avatar" class="btn-danger" style="margin-left:10px;">Remove</button>
                    <?php endif; ?>
                    <input type="hidden" name="change_avatar" value="1">
                </form>
                <small style="color:var(--text-gray);display:block;margin-top:0.5rem;">JPEG or PNG up to 2MB.</small>
            </div>

            <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="edit-profile.php">
                <!-- ... keep all of your previous form fields here ... -->
                <!-- (insert your existing code for Bio, Physical, Lifestyle, etc.) -->
                <!-- for brevity, not copied again here but keep as-is! -->

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