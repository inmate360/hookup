<?php
// ...[rest of marketplace.php code above remains unchanged]...

include 'views/header.php';
?>
<link rel="stylesheet" href="/assets/css/creator-cards.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
/* Enhanced Marketplace Styles */
.marketplace-hero {
  background: linear-gradient(135deg, #4267f5 0%, #1d9bf0 100%);
  padding: 3rem 2rem;
  border-radius: 25px;
  margin-bottom: 2.5rem;
  text-align: center;
  box-shadow: 0 20px 60px rgba(66, 103, 245, 0.3);
}

.marketplace-hero h1 {
  font-size: 2.5rem;
  font-weight: 800;
  color: #fff;
  margin-bottom: 1rem;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.marketplace-hero p {
  font-size: 1.2rem;
  color: rgba(255, 255, 255, 0.9);
  max-width: 600px;
  margin: 0 auto;
}

.search-filter-section {
  background: var(--bg-card);
  border: 2px solid var(--border);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 2.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.search-box {
  position: relative;
  margin-bottom: 1.5rem;
}

.search-input {
  width: 100%;
  padding: 1rem 1rem 1rem 3.5rem;
  background: var(--bg-dark);
  border: 2px solid var(--border);
  border-radius: 15px;
  color: var(--text);
  font-size: 1rem;
  transition: all 0.3s;
}

.search-input:focus {
  outline: none;
  border-color: var(--blue);
  box-shadow: 0 0 0 4px rgba(66, 103, 245, 0.1);
}

.search-icon {
  position: absolute;
  left: 1.25rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray);
  font-size: 1.2rem;
  pointer-events: none;
}

.filter-chips {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.filter-chip {
  padding: 0.75rem 1.5rem;
  background: var(--bg-dark);
  border: 2px solid var(--border);
  border-radius: 25px;
  color: var(--text);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-chip:hover {
  border-color: var(--blue);
  transform: translateY(-2px);
}

.filter-chip.active {
  background: var(--blue);
  border-color: var(--blue);
  color: #fff;
}

.sort-dropdown {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.sort-label {
  color: var(--gray);
  font-weight: 600;
}

.sort-select {
  padding: 0.75rem 1.25rem;
  background: var(--bg-dark);
  border: 2px solid var(--border);
  border-radius: 12px;
  color: var(--text);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.sort-select:focus {
  outline: none;
  border-color: var(--blue);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-title {
  color: #fff;
  font-size: 1.8rem;
  font-weight: 800;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.view-toggle {
  display: flex;
  gap: 0.5rem;
  background: var(--bg-card);
  border: 2px solid var(--border);
  border-radius: 12px;
  padding: 0.25rem;
}

.view-btn {
  padding: 0.5rem 1rem;
  background: transparent;
  border: none;
  border-radius: 8px;
  color: var(--gray);
  cursor: pointer;
  transition: all 0.3s;
  font-size: 1.2rem;
}

.view-btn.active {
  background: var(--blue);
  color: #fff;
}

.stats-bar {
  display: flex;
  gap: 2rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 2.5rem;
}

.stat-box {
  background: var(--bg-card);
  border: 2px solid var(--border);
  border-radius: 15px;
  padding: 1.5rem 2rem;
  text-align: center;
  transition: all 0.3s;
}

.stat-box:hover {
  border-color: var(--blue);
  transform: translateY(-3px);
}

.stat-value {
  font-size: 2rem;
  font-weight: 800;
  color: var(--blue);
  margin-bottom: 0.5rem;
}

.stat-label {
  color: var(--gray);
  font-weight: 600;
  font-size: 0.9rem;
}

.creator-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

.list-view .creator-grid {
  grid-template-columns: 1fr;
}

.loading-spinner {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 3rem;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid var(--border);
  border-top-color: var(--blue);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: var(--gray);
}

.empty-state i {
  font-size: 4rem;
  color: var(--border);
  margin-bottom: 1rem;
}

.empty-state h3 {
  color: var(--text);
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

.load-more-btn {
  display: block;
  width: 100%;
  max-width: 300px;
  margin: 2rem auto;
  padding: 1rem 2rem;
  background: var(--blue);
  border: none;
  border-radius: 15px;
  color: #fff;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
}

.load-more-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(66, 103, 245, 0.4);
}

/* Enhanced subscription card hover effects */
.subscription-card {
  position: relative;
  overflow: hidden;
}

.subscription-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(66, 103, 245, 0.1), transparent);
  transition: left 0.5s;
  z-index: 1;
  pointer-events: none;
}

.subscription-card:hover::before {
  left: 100%;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .marketplace-hero h1 { font-size: 2rem; }
  .marketplace-hero p { font-size: 1rem; }
  .search-filter-section { padding: 1.5rem; }
  .filter-chips { gap: 0.75rem; }
  .filter-chip { padding: 0.6rem 1.2rem; font-size: 0.9rem; }
  .section-title { font-size: 1.5rem; }
  .stats-bar { gap: 1rem; }
  .stat-box { padding: 1rem 1.5rem; }
  .creator-grid { grid-template-columns: 1fr; gap: 1.5rem; }
}
</style>

<!-- ENHANCED MARKETPLACE UI BEGIN -->
<div class="container" style="margin-top:2.5rem;max-width:1200px;">
  
  <!-- Hero Section -->
  <div class="marketplace-hero">
    <h1><i class="bi bi-shop"></i> Creator Marketplace</h1>
    <p>Discover and connect with amazing creators. Subscribe, message, and support your favorites.</p>
  </div>

  <!-- Stats Bar -->
  <div class="stats-bar">
    <div class="stat-box">
      <div class="stat-value"><?php echo count($featured_creators) + count($users); ?></div>
      <div class="stat-label">Total Creators</div>
    </div>
    <div class="stat-box">
      <div class="stat-value"><?php echo count(array_filter(array_merge($featured_creators, $users), fn($u) => $u['is_online'])); ?></div>
      <div class="stat-label">Online Now</div>
    </div>
    <div class="stat-box">
      <div class="stat-value"><?php echo count($featured_creators); ?></div>
      <div class="stat-label">Featured</div>
    </div>
  </div>

  <!-- Search & Filter Section -->
  <div class="search-filter-section">
    <div class="search-box">
      <i class="bi bi-search search-icon"></i>
      <input type="text" class="search-input" placeholder="Search creators by name or username..." id="creatorSearch">
    </div>
    
    <div class="filter-chips">
      <button class="filter-chip active" data-filter="all">
        <i class="bi bi-grid-3x3"></i> All Creators
      </button>
      <button class="filter-chip" data-filter="online">
        <i class="bi bi-circle-fill" style="color:var(--green);font-size:0.6rem"></i> Online
      </button>
      <button class="filter-chip" data-filter="verified">
        <i class="bi bi-patch-check-fill"></i> Verified
      </button>
      <button class="filter-chip" data-filter="subscribed">
        <i class="bi bi-star-fill"></i> Subscribed
      </button>
      <button class="filter-chip" data-filter="free">
        <i class="bi bi-gift"></i> Free
      </button>
    </div>

    <div class="sort-dropdown">
      <span class="sort-label">Sort by:</span>
      <select class="sort-select" id="sortBy">
        <option value="featured">Featured First</option>
        <option value="name">Name (A-Z)</option>
        <option value="online">Online Status</option>
        <option value="posts">Most Posts</option>
        <option value="recent">Recently Active</option>
      </select>
    </div>
  </div>

  <!-- Featured Creators Section -->
  <div class="section-header">
    <h2 class="section-title">
      <i class="bi bi-star-fill" style="color:var(--orange)"></i>
      Featured Creators
    </h2>
    <div class="view-toggle">
      <button class="view-btn active" data-view="grid"><i class="bi bi-grid-3x3"></i></button>
      <button class="view-btn" data-view="list"><i class="bi bi-list"></i></button>
    </div>
  </div>

  <div class="creator-grid" id="featuredGrid">
    <?php foreach($featured_creators as $user): ?>
      <div class="subscription-card creator-item" 
           data-name="<?php echo strtolower(htmlspecialchars($user['display_name'])); ?>" 
           data-username="<?php echo strtolower(htmlspecialchars($user['username'])); ?>"
           data-online="<?php echo $user['is_online'] ? '1' : '0'; ?>"
           data-verified="<?php echo $user['is_verified'] ? '1' : '0'; ?>"
           data-subscribed="<?php echo $user['is_subscribed'] ? '1' : '0'; ?>"
           data-free="<?php echo $user['is_free'] ? '1' : '0'; ?>"
           onclick="window.location.href='/profile.php?id=<?php echo $user['id']; ?>'">
        <div class="subscription-cover">
          <?php if($user['cover_image']): ?>
            <img src="<?php echo htmlspecialchars($user['cover_image']); ?>" alt="Cover">
          <?php endif; ?>
          <div class="subscription-status">
            <?php echo $user['is_online'] ? '<i class="bi bi-circle-fill" style="font-size:0.6rem;color:var(--green)"></i> Available now' : '<i class="bi bi-circle" style="font-size:0.6rem"></i> Offline'; ?>
          </div>
        </div>
        <div class="subscription-body">
          <div class="subscription-avatar-wrapper">
            <div class="subscription-avatar">
              <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar">
            </div>
            <?php if($user['is_online']): ?><div class="online-indicator"></div><?php endif; ?>
            <?php if($user['is_verified']): ?><div class="verification-badge"><i class="bi bi-check"></i></div><?php endif; ?>
          </div>
          <div class="subscription-info">
            <div class="subscription-username">
              <?php echo htmlspecialchars($user['display_name']); ?>
              <?php if($user['is_verified']): ?><i class="bi bi-patch-check-fill" style="color:#4267f5"></i><?php endif; ?>
            </div>
            <div class="subscription-handle">@<?php echo htmlspecialchars($user['username']); ?></div>
          </div>
          <div class="subscription-actions">
            <a href="/messages-compose.php?to=<?php echo $user['id']; ?>" class="subscription-btn message" onclick="event.stopPropagation();"><i class="bi bi-chat"></i> Message</a>
            <button class="subscription-btn tip" onclick="event.stopPropagation();showTipModal(<?php echo $user['id']; ?>)"><i class="bi bi-currency-dollar"></i> Tip</button>
          </div>
          <?php if($user['is_free']): ?>
          <div class="subscription-badge free"><i class="bi bi-gift"></i> SUBSCRIBED FOR FREE</div>
          <?php elseif($user['is_subscribed']): ?>
          <div class="subscription-badge premium"><i class="bi bi-star-fill"></i> SUBSCRIBED - $<?php echo number_format($user['subscription_price'],2); ?>/mo</div>
          <?php else: ?>
          <div class="subscription-badge" style="border-color:#ef4444;color:#ef4444;background:rgba(239,68,68,.1)"><i class="bi bi-lock-fill"></i> NOT SUBSCRIBED</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Browse All Users Section -->
  <div class="section-header" style="margin-top:3rem">
    <h2 class="section-title">
      <i class="bi bi-people-fill" style="color:var(--cyan)"></i>
      Browse All Creators
    </h2>
  </div>

  <div id="usersList">
    <?php foreach($users as $user): ?>
      <div class="user-list-item creator-item"
           data-name="<?php echo strtolower(htmlspecialchars($user['display_name'])); ?>" 
           data-username="<?php echo strtolower(htmlspecialchars($user['username'])); ?>"
           data-online="<?php echo $user['is_online'] ? '1' : '0'; ?>"
           data-verified="<?php echo $user['is_verified'] ? '1' : '0'; ?>"
           data-subscribed="<?php echo $user['is_subscribed'] ? '1' : '0'; ?>"
           data-free="<?php echo $user['is_free'] ? '1' : '0'; ?>"
           onclick="window.location.href='/profile.php?id=<?php echo $user['id']; ?>'">
        <div class="user-list-avatar">
          <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar">
          <?php if($user['is_online']): ?><div class="online-indicator"></div><?php endif; ?>
        </div>
        <div class="user-list-info">
          <div class="user-list-username">
            <?php echo htmlspecialchars($user['display_name']); ?>
            <?php if($user['is_verified']): ?><i class="bi bi-patch-check-fill" style="color:#4267f5"></i><?php endif; ?>
          </div>
          <div class="user-list-meta">
            <span><i class="bi bi-at"></i> <?php echo htmlspecialchars($user['username']); ?></span>
            <span>• <?php echo $user['is_online'] ? '<i class="bi bi-circle-fill" style="color:var(--green);font-size:0.5rem"></i> Online now' : 'Last seen '.timeAgo($user['last_seen']); ?></span>
            <span>• <i class="bi bi-file-post"></i> <?php echo number_format($user['post_count']); ?> posts</span>
          </div>
        </div>
        <div class="user-list-actions">
          <a href="/messages-compose.php?to=<?php echo $user['id']; ?>" class="user-list-btn" onclick="event.stopPropagation()"><i class="bi bi-chat"></i> Message</a>
          <button class="user-list-btn" onclick="event.stopPropagation();toggleFollow(<?php echo $user['id']; ?>)"><i class="bi bi-star"></i></button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Empty State -->
  <div class="empty-state" id="emptyState" style="display:none">
    <i class="bi bi-search"></i>
    <h3>No creators found</h3>
    <p>Try adjusting your search or filters</p>
  </div>

</div>

<script>
// Enhanced Marketplace Functionality
(function() {
  const searchInput = document.getElementById('creatorSearch');
  const filterChips = document.querySelectorAll('.filter-chip');
  const sortSelect = document.getElementById('sortBy');
  const creatorItems = document.querySelectorAll('.creator-item');
  const emptyState = document.getElementById('emptyState');
  const viewBtns = document.querySelectorAll('.view-btn');
  const featuredGrid = document.getElementById('featuredGrid');
  
  let currentFilter = 'all';
  let currentSort = 'featured';
  
  // Search functionality
  searchInput.addEventListener('input', function() {
    filterCreators();
  });
  
  // Filter chips
  filterChips.forEach(chip => {
    chip.addEventListener('click', function() {
      filterChips.forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      currentFilter = this.dataset.filter;
      filterCreators();
    });
  });
  
  // Sort functionality
  sortSelect.addEventListener('change', function() {
    currentSort = this.value;
    sortCreators();
  });
  
  // View toggle
  viewBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      viewBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      const view = this.dataset.view;
      if (view === 'list') {
        featuredGrid.classList.add('list-view');
      } else {
        featuredGrid.classList.remove('list-view');
      }
    });
  });
  
  function filterCreators() {
    const searchTerm = searchInput.value.toLowerCase();
    let visibleCount = 0;
    
    creatorItems.forEach(item => {
      const name = item.dataset.name;
      const username = item.dataset.username;
      const matchesSearch = name.includes(searchTerm) || username.includes(searchTerm);
      
      let matchesFilter = true;
      if (currentFilter === 'online') matchesFilter = item.dataset.online === '1';
      else if (currentFilter === 'verified') matchesFilter = item.dataset.verified === '1';
      else if (currentFilter === 'subscribed') matchesFilter = item.dataset.subscribed === '1';
      else if (currentFilter === 'free') matchesFilter = item.dataset.free === '1';
      
      if (matchesSearch && matchesFilter) {
        item.style.display = '';
        visibleCount++;
      } else {
        item.style.display = 'none';
      }
    });
    
    emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
  }
  
  function sortCreators() {
    // Sorting logic would go here
    // This is a placeholder - in a real implementation, you'd sort the items
    console.log('Sorting by:', currentSort);
  }
})();
</script>
<!-- ENHANCED MARKETPLACE UI END -->

<?php include 'views/footer.php'; ?>