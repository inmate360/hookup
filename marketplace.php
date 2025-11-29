<?php
// ...[rest of marketplace.php code above remains unchanged]...

include 'views/header.php';
?>
<link rel="stylesheet" href="/assets/css/creator-cards.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">

<!-- NEW CREATOR/USER CARDS BEGIN -->
<div class="container" style="margin-top:2.5rem;max-width:1200px;">
  <h2 style="color:#fff;margin:2rem 0 1rem">Featured Creators</h2>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:2rem">
    <?php foreach($featured_creators as $user): ?>
      <div class="subscription-card" onclick="window.location.href='/profile.php?id=<?php echo $user['id']; ?>'">
        <div class="subscription-cover">
          <?php if($user['cover_image']): ?><img src="<?php echo htmlspecialchars($user['cover_image']); ?>" alt="Cover"><?php endif; ?>
          <div class="subscription-status">
            <?php echo $user['is_online'] ? 'Available now' : 'Offline'; ?>
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
            <button class="subscription-btn tip" onclick="event.stopPropagation();showTipModal(<?php echo $user['id']; ?>)"><i class="bi bi-currency-dollar"></i> Send a tip</button>
          </div>
          <?php if($user['is_free']): ?>
          <div class="subscription-badge free">SUBSCRIBED FOR FREE</div>
          <?php elseif($user['is_subscribed']): ?>
          <div class="subscription-badge premium">SUBSCRIBED - $<?php echo number_format($user['subscription_price'],2); ?>/mo</div>
          <?php else: ?>
          <div class="subscription-badge" style="border-color:#ef4444;color:#ef4444;background:rgba(239,68,68,.1)">🔒 NOT SUBSCRIBED</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <h2 style="color:#fff;margin:2rem 0 1rem">Browse Users</h2>
  <?php foreach($users as $user): ?>
    <div class="user-list-item" onclick="window.location.href='/profile.php?id=<?php echo $user['id']; ?>'">
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
          <span>@<?php echo htmlspecialchars($user['username']); ?></span>
          <span>• <?php echo $user['is_online'] ? 'Online now' : 'Last seen '.timeAgo($user['last_seen']); ?></span>
          <span>• <?php echo number_format($user['post_count']); ?> posts</span>
        </div>
      </div>
      <div class="user-list-actions">
        <a href="/messages-compose.php?to=<?php echo $user['id']; ?>" class="user-list-btn" onclick="event.stopPropagation()"><i class="bi bi-chat"></i> Message</a>
        <button class="user-list-btn" onclick="event.stopPropagation();toggleFollow(<?php echo $user['id']; ?>)"><i class="bi bi-star"></i></button>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<!-- NEW CREATOR/USER CARDS END -->

<?php include 'views/footer.php'; ?>
