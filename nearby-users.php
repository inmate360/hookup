<?php
session_start();
require_once 'config/database.php';
require_once 'classes/LocationService.php';
require_once 'includes/maintenance_check.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

if(checkMaintenanceMode($db)) {
    header('Location: maintenance.php');
    exit();
}

$locationService = new LocationService($db);

// Get user's current location
$query = "SELECT current_latitude, current_longitude, search_radius, show_distance 
          FROM users WHERE id = :user_id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$current_user = $stmt->fetch();

$has_location = !empty($current_user['current_latitude']);

include 'views/header.php';
?>

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.6); }
    50% { box-shadow: 0 0 30px rgba(16, 185, 129, 0.9); }
}

.online-badge {
    animation: pulse-glow 2s infinite;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-backdrop-blur {
    backdrop-filter: blur(8px);
    animation: fadeIn 0.3s ease;
}

.modal-slide-up {
    animation: slideUp 0.4s ease;
}

.user-card-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.user-card-hover:hover {
    transform: translateY(-8px) scale(1.02);
}

.glassmorphism {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}
</style>

<div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        <!-- Header Card -->
        <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl shadow-2xl p-8 mb-8 text-white">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-3 flex items-center justify-center gap-3">
                    <i class="bi bi-geo-alt-fill"></i>
                    Nearby Users
                </h1>
                <p class="text-blue-100 text-lg">Discover people close to you</p>
                
                <div class="flex flex-wrap justify-center items-center gap-4 mt-6">
                    <button class="btn btn-light px-6 py-3 rounded-pill shadow-lg hover:shadow-xl transition-all" 
                            onclick="requestLocation()" id="locationBtn">
                        <i class="bi bi-geo-alt me-2"></i>
                        Update My Location
                    </button>
                    
                    <div class="d-flex align-items-center gap-3 glassmorphism rounded-pill px-4 py-3 border border-white/30">
                        <label class="text-white fw-semibold mb-0">Search Radius:</label>
                        <input type="range" class="form-range" style="width: 200px;"
                               id="radiusSlider" 
                               min="5" max="100" step="5" 
                               value="<?php echo $current_user['search_radius'] ?? 50; ?>"
                               oninput="updateRadius(this.value)">
                        <span id="radiusValue" class="badge bg-white text-primary fs-6 px-3 py-2">
                            <?php echo $current_user['search_radius'] ?? 50; ?> km
                        </span>
                    </div>
                    
                    <div class="form-check form-switch glassmorphism rounded-pill px-4 py-3 border border-white/30">
                        <input class="form-check-input" type="checkbox" role="switch" 
                               id="showDistanceToggle"
                               <?php echo $current_user['show_distance'] ? 'checked' : ''; ?>
                               onchange="toggleDistanceVisibility(this.checked)">
                        <label class="form-check-label text-white ms-2 mb-0">
                            Show my distance to others
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <?php if(!$has_location): ?>
        <!-- No Location State -->
        <div class="card border-0 shadow-2xl rounded-3xl overflow-hidden">
            <div class="card-body text-center p-5">
                <div class="display-1 mb-4">📍</div>
                <h2 class="h1 fw-bold mb-3">Location Not Set</h2>
                <p class="text-muted fs-5 mb-4">
                    Enable location access to discover nearby users and see who's close to you
                </p>
                
                <div class="card bg-primary bg-opacity-10 border-primary border-2 rounded-3 p-4 mb-4 mx-auto" style="max-width: 600px;">
                    <h3 class="text-primary mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        How It Works
                    </h3>
                    <ol class="list-group list-group-numbered text-start">
                        <li class="list-group-item border-0 bg-transparent">Click "Update My Location" button</li>
                        <li class="list-group-item border-0 bg-transparent">Allow location access in your browser</li>
                        <li class="list-group-item border-0 bg-transparent">We'll show you nearby users within your search radius</li>
                        <li class="list-group-item border-0 bg-transparent">You control your distance visibility settings</li>
                    </ol>
                </div>
                
                <button class="btn btn-primary btn-lg px-5 py-3 rounded-pill shadow-lg" 
                        onclick="requestLocation()">
                    <i class="bi bi-geo-alt me-2"></i>
                    Enable Location Access
                </button>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Filters Bar -->
        <div class="card border-0 shadow-lg rounded-3 mb-4 bg-white/95 backdrop-blur-md">
            <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" 
                                   id="searchUsers" 
                                   placeholder="Search users...">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <select class="form-select" id="genderFilter" onchange="loadNearbyUsers()">
                            <option value="">All Genders</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <select class="form-select" id="onlineFilter" onchange="loadNearbyUsers()">
                            <option value="">All Users</option>
                            <option value="online">Online Now</option>
                            <option value="recent">Active Recently</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <select class="form-select" id="sortBy" onchange="loadNearbyUsers()">
                            <option value="distance">Distance (Nearest)</option>
                            <option value="recent">Recently Active</option>
                            <option value="newest">Newest Members</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Loading State -->
        <div id="loadingState" class="text-center py-5 d-none">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-white mt-3 fs-5">Finding nearby users...</p>
        </div>
        
        <!-- Results Count -->
        <div id="resultsCount" class="text-white mb-3 fs-5"></div>
        
        <!-- Users Grid -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="usersGrid">
            <!-- Users will be loaded here -->
        </div>
        
        <!-- No Results -->
        <div id="noResults" class="card border-0 shadow-2xl rounded-3 d-none">
            <div class="card-body text-center p-5">
                <div class="display-1 mb-4">🔍</div>
                <h3 class="h2 fw-bold mb-3">No Nearby Users Found</h3>
                <p class="text-muted fs-5">
                    Try increasing your search radius or check back later
                </p>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-gradient-to-br from-blue-900 to-blue-800 text-white border-0 rounded-4 shadow-2xl modal-slide-up">
            <div class="modal-header border-0 position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" 
                        data-bs-dismiss="modal" aria-label="Close"></button>
                
                <div class="w-100 text-center pt-4">
                    <div class="mx-auto mb-3 rounded-circle glassmorphism d-flex align-items-center justify-content-center border border-3 border-white/30" 
                         style="width: 100px; height: 100px; font-size: 3rem;" id="modalAvatar">
                        👤
                    </div>
                    <h4 class="modal-title fs-2 fw-bold" id="modalUsername">Username</h4>
                    <p class="text-white/90 mb-0" id="modalStatus">
                        <i class="bi bi-circle-fill text-success me-1"></i> Online Now
                    </p>
                </div>
            </div>
            
            <div class="modal-body p-4">
                <div class="card glassmorphism border-0 rounded-3 mb-3 d-none" id="modalDistance">
                    <div class="card-body text-center py-3">
                        <div class="fs-1 fw-bold" id="modalDistanceValue">--</div>
                        <div class="text-white-50">away from you</div>
                    </div>
                </div>
                
                <div class="card glassmorphism border-white/20 border rounded-3">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-clipboard me-2"></i> Profile Info
                        </h5>
                        <div class="d-flex align-items-center py-2 border-bottom border-white/10">
                            <i class="bi bi-calendar3 me-3 fs-5"></i>
                            <span class="fw-semibold me-auto" style="min-width: 100px;">Joined:</span>
                            <span id="modalJoined" class="text-white-50">--</span>
                        </div>
                        <div class="d-flex align-items-center py-2">
                            <i class="bi bi-eye me-3 fs-5"></i>
                            <span class="fw-semibold me-auto" style="min-width: 100px;">Last Seen:</span>
                            <span id="modalLastSeen" class="text-white-50">--</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer border-0 p-4 gap-2">
                <a href="#" id="modalViewProfile" class="btn btn-outline-light flex-fill rounded-pill py-3">
                    <i class="bi bi-person me-2"></i> View Profile
                </a>
                <a href="#" id="modalMessage" class="btn btn-light flex-fill rounded-pill py-3 fw-semibold">
                    <i class="bi bi-chat-dots me-2"></i> Send Message
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let currentRadius = <?php echo $current_user['search_radius'] ?? 50; ?>;
let currentLocation = {
    latitude: <?php echo $current_user['current_latitude'] ?? 'null'; ?>,
    longitude: <?php echo $current_user['current_longitude'] ?? 'null'; ?>
};
let userModalInstance;

document.addEventListener('DOMContentLoaded', function() {
    userModalInstance = new bootstrap.Modal(document.getElementById('userModal'));
});

function requestLocation() {
    const btn = document.getElementById('locationBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Getting Location...';
    
    if(!navigator.geolocation) {
        alert('Geolocation is not supported by your browser');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-geo-alt me-2"></i> Update My Location';
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            updateLocation(position.coords.latitude, position.coords.longitude, true);
        },
        (error) => {
            console.error('Geolocation error:', error);
            alert('Unable to get your location. Please enable location access in your browser.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-geo-alt me-2"></i> Update My Location';
        },
        {enableHighAccuracy: true, timeout: 10000, maximumAge: 0}
    );
}

function updateLocation(latitude, longitude, autoDetected = false) {
    const formData = new FormData();
    formData.append('action', 'update_location');
    formData.append('latitude', latitude);
    formData.append('longitude', longitude);
    formData.append('auto_detected', autoDetected);
    
    fetch('/api/location.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            currentLocation = {latitude, longitude};
            location.reload();
        } else {
            alert(data.error || 'Failed to update location');
        }
        
        const btn = document.getElementById('locationBtn');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-geo-alt me-2"></i> Update My Location';
    });
}

function updateRadius(value) {
    document.getElementById('radiusValue').textContent = value + ' km';
    currentRadius = value;
    
    clearTimeout(window.radiusTimeout);
    window.radiusTimeout = setTimeout(() => {
        loadNearbyUsers();
    }, 500);
}

function toggleDistanceVisibility(show) {
    const formData = new FormData();
    formData.append('action', 'toggle_distance');
    formData.append('show_distance', show);
    
    fetch('/api/location.php', {method: 'POST', body: formData});
}

function loadNearbyUsers() {
    if(!currentLocation.latitude) return;
    
    const grid = document.getElementById('usersGrid');
    const loading = document.getElementById('loadingState');
    const noResults = document.getElementById('noResults');
    const resultsCount = document.getElementById('resultsCount');
    
    grid.classList.add('d-none');
    loading.classList.remove('d-none');
    noResults.classList.add('d-none');
    
    const params = new URLSearchParams({
        action: 'get_nearby_users',
        radius: currentRadius,
        limit: 100
    });
    
    fetch('/api/location.php?' + params)
        .then(response => response.json())
        .then(data => {
            loading.classList.add('d-none');
            
            if(data.success && data.users.length > 0) {
                displayUsers(data.users);
                resultsCount.textContent = `Found ${data.count} user${data.count !== 1 ? 's' : ''} within ${currentRadius}km`;
            } else {
                noResults.classList.remove('d-none');
                resultsCount.textContent = '';
            }
        })
        .catch(error => {
            console.error('Error loading nearby users:', error);
            loading.classList.add('d-none');
            noResults.classList.remove('d-none');
        });
}

function displayUsers(users) {
    const grid = document.getElementById('usersGrid');
    grid.innerHTML = '';
    grid.classList.remove('d-none');
    
    users.forEach(user => {
        const col = document.createElement('div');
        col.className = 'col';
        
        col.innerHTML = `
            <div class="card border-0 shadow-lg rounded-3 overflow-hidden user-card-hover h-100 bg-gradient-to-br from-blue-800 to-blue-900 text-white position-relative cursor-pointer" 
                 onclick='openUserModal(${JSON.stringify(user).replace(/'/g, "&#39;")})'>
                ${user.show_distance ? `
                    <div class="position-absolute top-0 end-0 m-3 badge glassmorphism text-white px-3 py-2 rounded-pill fs-6 border border-white/30" style="z-index: 10;">
                        <i class="bi bi-geo-alt-fill me-1"></i> ${user.distance_display}
                    </div>
                ` : ''}
                
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-4">
                    <div class="position-relative mb-3">
                        <div class="rounded-circle glassmorphism d-flex align-items-center justify-content-center border border-3 border-white/30" 
                             style="width: 120px; height: 120px; font-size: 4rem;">
                            👤
                        </div>
                        ${user.is_online ? `
                            <span class="position-absolute bottom-0 end-0 bg-success border border-3 border-white rounded-circle online-badge" 
                                  style="width: 24px; height: 24px;"></span>
                        ` : ''}
                    </div>
                    
                    <h5 class="card-title fs-3 fw-bold mb-2">${escapeHtml(user.username)}</h5>
                    <p class="card-text mb-2 opacity-90">
                        ${user.is_online ? 
                            '<i class="bi bi-circle-fill text-success me-1"></i> Online Now' : 
                            (user.last_seen ? '<i class="bi bi-circle opacity-50 me-1"></i> Last seen ' + formatTime(user.last_seen) : '<i class="bi bi-circle opacity-50 me-1"></i> Offline')
                        }
                    </p>
                    <p class="card-text small opacity-70">
                        Member since ${formatDate(user.created_at)}
                    </p>
                    
                    <div class="mt-3 d-flex gap-2 w-100">
                        <a href="/profile.php?id=${user.id}" class="btn btn-outline-light flex-fill rounded-pill" onclick="event.stopPropagation()">
                            <i class="bi bi-person"></i> Profile
                        </a>
                        <a href="/messages-chat-simple.php?user=${user.id}" class="btn btn-light flex-fill rounded-pill fw-semibold" onclick="event.stopPropagation()">
                            <i class="bi bi-chat-dots"></i> Message
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        grid.appendChild(col);
    });
}

function openUserModal(user) {
    document.getElementById('modalUsername').textContent = user.username;
    document.getElementById('modalStatus').innerHTML = user.is_online ? 
        '<i class="bi bi-circle-fill text-success me-1"></i> Online Now' : 
        (user.last_seen ? '<i class="bi bi-circle opacity-50 me-1"></i> Last seen ' + formatTime(user.last_seen) : '<i class="bi bi-circle opacity-50 me-1"></i> Offline');
    
    document.getElementById('modalJoined').textContent = formatDate(user.created_at);
    document.getElementById('modalLastSeen').textContent = user.last_seen ? formatTime(user.last_seen) : 'Unknown';
    
    if(user.show_distance && user.distance_display) {
        document.getElementById('modalDistance').classList.remove('d-none');
        document.getElementById('modalDistanceValue').textContent = user.distance_display;
    } else {
        document.getElementById('modalDistance').classList.add('d-none');
    }
    
    document.getElementById('modalViewProfile').href = '/profile.php?id=' + user.id;
    document.getElementById('modalMessage').href = '/messages-chat-simple.php?user=' + user.id;
    
    userModalInstance.show();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    if(diff < 60000) return 'just now';
    if(diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
    if(diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
    if(diff < 604800000) return Math.floor(diff / 86400000) + 'd ago';
    
    return date.toLocaleDateString();
}

function formatDate(timestamp) {
    return new Date(timestamp).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short' 
    });
}

if(currentLocation.latitude) {
    loadNearbyUsers();
    setInterval(() => {
        loadNearbyUsers();
    }, 30000);
}
</script>

<?php include 'views/footer.php'; ?>