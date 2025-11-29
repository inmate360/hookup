<?php
session_start();
require_once 'config/database.php';
require_once 'classes/MediaContent.php';
require_once 'classes/CoinsSystem.php';

$database = new Database();
$db = $database->getConnection();
$mediaContent = new MediaContent($db);

// Get filters
$page = (int)($_GET['page'] ?? 1);
$per_page = 24;
$content_type = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$search = $_GET['search'] ?? '';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : null;
$view_mode = $_GET['view'] ?? 'grid';

$filters = [];
if($content_type) $filters['content_type'] = $content_type;
if($search) $filters['search'] = $search;
if($min_price !== null) $filters['min_price'] = $min_price;
if($max_price !== null) $filters['max_price'] = $max_price;

$content_items = $mediaContent->browseContent($filters, $page, $per_page);

$coin_balance = 0;
$user_wishlist = [];
if(isset($_SESSION['user_id'])) {
    $coinsSystem = new CoinsSystem($db);
    $coin_balance = $coinsSystem->getBalance($_SESSION['user_id']);
    
    try {
        $w_query = "SELECT content_id FROM content_wishlist WHERE user_id = :user_id";
        $w_stmt = $db->prepare($w_query);
        $w_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $w_stmt->execute();
        $user_wishlist = array_column($w_stmt->fetchAll(), 'content_id');
    } catch(Exception $e) {
        $user_wishlist = [];
    }
}

include 'views/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
:root{--bg-dark:#0a0a0f;--bg-card:#1a1a2e;--border:#2d2d44;--blue:#4267f5;--text:#fff;--gray:#a0a0b0;--green:#10b981;--orange:#f59e0b;--red:#ef4444}
*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg-dark);color:var(--text)}
.hero{background:linear-gradient(135deg,rgba(15,23,42,.95),rgba(30,41,59,.95));border-bottom:2px solid rgba(66,103,245,.2);padding:3rem 0 2rem;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;top:-50%;right:-10%;width:500px;height:500px;background:radial-gradient(circle,rgba(66,103,245,.15),transparent);border-radius:50%}
.container{max-width:1400px;margin:0 auto;padding:0 1.5rem}.coin-card{background:linear-gradient(135deg,#fbbf24,#f59e0b);border-radius:15px;padding:1rem 1.5rem;display:inline-flex;align-items:center;gap:.75rem;box-shadow:0 8px 24px rgba(251,191,36,.4)}
.search-bar{max-width:600px;margin:2rem auto;position:relative}.search-bar input{width:100%;padding:1rem 3.5rem 1rem 1.5rem;background:var(--bg-card);border:2px solid var(--border);border-radius:50px;color:var(--text);font-size:1rem;transition:all .3s}
.search-bar input:focus{outline:none;border-color:var(--blue);box-shadow:0 0 0 4px rgba(66,103,245,.1)}.search-bar button{position:absolute;right:8px;top:50%;transform:translateY(-50%);background:var(--blue);border:none;padding:.75rem 1.5rem;border-radius:50px;color:#fff;cursor:pointer;transition:all .3s}
.search-bar button:hover{background:#3451d9}.filter-sec{background:var(--bg-card);border:2px solid var(--border);border-radius:20px;padding:2rem;margin:2rem 0}
.filter-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-top:1.5rem}.filter-grp{display:flex;flex-direction:column;gap:.5rem}
.filter-grp label{font-weight:600;font-size:.9rem}.filter-grp select,.filter-grp input{padding:.75rem;background:rgba(255,255,255,.05);border:2px solid var(--border);border-radius:10px;color:var(--text);transition:all .3s}
.filter-grp select:focus,.filter-grp input:focus{outline:none;border-color:var(--blue)}.view-toggle{display:flex;gap:.5rem;background:var(--bg-card);padding:.5rem;border-radius:12px;border:2px solid var(--border)}
.view-toggle button{padding:.75rem 1.5rem;background:transparent;border:none;color:var(--gray);border-radius:8px;cursor:pointer;transition:all .3s}.view-toggle button.active{background:var(--blue);color:#fff}
.content-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:2rem;margin-top:2rem}.content-list{display:flex;flex-direction:column;gap:1.5rem;margin-top:2rem}
.card{background:var(--bg-card);border:2px solid var(--border);border-radius:20px;overflow:hidden;transition:all .3s;cursor:pointer;position:relative}
.card:hover{transform:translateY(-8px);box-shadow:0 20px 60px rgba(66,103,245,.3);border-color:var(--blue)}.thumb-wrap{position:relative;width:100%;height:350px;overflow:hidden}
.thumb{width:100%;height:100%;object-fit:cover;transition:transform .3s}.card:hover .thumb{transform:scale(1.05)}.thumb.blur{filter:blur(20px)}
.lock{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.7);display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff}
.price{background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#fff;padding:.75rem 1.5rem;border-radius:25px;font-weight:800;font-size:1.2rem;box-shadow:0 4px 15px rgba(251,191,36,.5)}
.creator{position:absolute;top:1rem;left:1rem;background:rgba(0,0,0,.8);backdrop-filter:blur(10px);padding:.5rem 1rem;border-radius:20px;display:flex;align-items:center;gap:.5rem;z-index:2}
.avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#4267f5,#1d9bf0);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem}
.wishlist-btn{position:absolute;top:1rem;right:1rem;background:rgba(0,0,0,.8);backdrop-filter:blur(10px);border:none;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:2;transition:all .3s;color:#fff}
.wishlist-btn:hover{transform:scale(1.1)}.wishlist-btn.active{color:var(--red)}.info{padding:1.5rem}.title{font-weight:700;font-size:1.1rem;margin-bottom:.75rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.stats{display:flex;gap:1.5rem;color:var(--gray);font-size:.9rem;margin-top:1rem}.stats-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
@media(max-width:768px){.content-grid{grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem}.filter-row{grid-template-columns:1fr}}
</style>

<div class="hero"><div class="container" style="text-align:center"><h1 style="font-size:3rem;font-weight:800;color:#fff;margin-bottom:1rem">💎 Content Marketplace</h1><p style="font-size:1.2rem;color:rgba(255,255,255,.8);margin-bottom:2rem">Discover exclusive content from premium creators</p>
<?php if(isset($_SESSION['user_id'])):?><div style="margin-top:2rem"><div class="coin-card"><span style="font-size:1.5rem">💰</span><div><div style="font-size:.8rem;opacity:.9">Your Balance</div><div style="font-size:1.3rem;font-weight:800"><?php echo number_format($coin_balance);?> coins</div></div><a href="/buy-coins.php" style="background:#fff;color:#f59e0b;padding:.75rem 1.5rem;border-radius:10px;font-weight:700;text-decoration:none">Buy Coins</a></div></div>
<?php else:?><div style="margin-top:1rem"><a href="/login.php" style="background:var(--blue);color:#fff;padding:1rem 2rem;border-radius:10px;font-weight:700;text-decoration:none;display:inline-block">Login to Purchase</a></div><?php endif;?></div></div>

<div class="container" style="padding:3rem 1.5rem">
<div class="search-bar"><form method="GET"><input type="text" name="search" placeholder="Search content, creators..." value="<?php echo htmlspecialchars($search);?>"><button type="submit"><i class="bi bi-search"></i></button></form></div>

<div class="filter-sec"><h3 style="margin-bottom:1rem"><i class="bi bi-funnel"></i> Advanced Filters</h3><form method="GET"><div class="filter-row"><div class="filter-grp"><label>Content Type</label><select name="type"><option value="">All Types</option><option value="photo" <?php echo $content_type==='photo'?'selected':'';?>>📸 Photos</option><option value="photo_set" <?php echo $content_type==='photo_set'?'selected':'';?>>📷 Photo Sets</option><option value="video" <?php echo $content_type==='video'?'selected':'';?>>🎥 Videos</option><option value="video_set" <?php echo $content_type==='video_set'?'selected':'';?>>🎬 Video Sets</option></select></div>
<div class="filter-grp"><label>Min Price (coins)</label><input type="number" name="min_price" placeholder="0" value="<?php echo $min_price??'';?>" min="0"></div><div class="filter-grp"><label>Max Price (coins)</label><input type="number" name="max_price" placeholder="∞" value="<?php echo $max_price??'';?>" min="0"></div>
<div class="filter-grp"><label>Sort By</label><select name="sort"><option value="newest" <?php echo $sort==='newest'?'selected':'';?>>Newest First</option><option value="popular" <?php echo $sort==='popular'?'selected':'';?>>Most Popular</option><option value="price_low" <?php echo $sort==='price_low'?'selected':'';?>>Price: Low to High</option><option value="price_high" <?php echo $sort==='price_high'?'selected':'';?>>Price: High to Low</option></select></div></div>
<div style="display:flex;gap:1rem;margin-top:1.5rem"><button type="submit" style="background:var(--blue);color:#fff;padding:.75rem 2rem;border:none;border-radius:10px;font-weight:600;cursor:pointer">Apply Filters</button><a href="marketplace.php" style="background:var(--bg-card);color:var(--text);padding:.75rem 2rem;border:2px solid var(--border);border-radius:10px;font-weight:600;text-decoration:none;display:inline-block">Clear All</a></div></form></div>

<div class="stats-bar"><div style="color:var(--gray)">Showing <strong style="color:var(--text)"><?php echo count($content_items);?></strong> items</div>
<div class="view-toggle"><button class="<?php echo $view_mode==='grid'?'active':'';?>" onclick="changeView('grid')"><i class="bi bi-grid-3x3"></i> Grid</button><button class="<?php echo $view_mode==='list'?'active':'';?>" onclick="changeView('list')"><i class="bi bi-list"></i> List</button></div></div>

<?php if(empty($content_items)):?>
<div style="background:var(--bg-card);border:2px solid var(--border);border-radius:20px;padding:4rem 2rem;text-align:center;margin-top:2rem"><div style="font-size:5rem;margin-bottom:1rem;opacity:.3">🔍</div><h3 style="font-size:1.5rem;margin-bottom:1rem">No content found</h3><p style="color:var(--gray);margin-bottom:1.5rem">Try adjusting your filters or search terms</p><a href="marketplace.php" style="background:var(--blue);color:#fff;padding:.75rem 2rem;border-radius:10px;font-weight:600;text-decoration:none;display:inline-block">View All Content</a></div>
<?php else:?>
<div class="content-<?php echo $view_mode;?>">
<?php foreach($content_items as $item):?>
<div class="card" onclick="window.location.href='/view-content.php?id=<?php echo $item['id'];?>'"><div class="thumb-wrap">
<div class="creator"><div class="avatar"><?php echo strtoupper(substr($item['creator_name']??'U',0,1));?></div><span style="color:#fff;font-weight:600;font-size:.9rem"><?php echo htmlspecialchars($item['creator_name']??'Unknown');?></span><?php if($item['is_verified']??false):?><span style="color:#3b82f6">✓</span><?php endif;?></div>
<?php if(isset($_SESSION['user_id'])):?><button class="wishlist-btn <?php echo in_array($item['id'],$user_wishlist)?'active':'';?>" onclick="event.stopPropagation();toggleWishlist(<?php echo $item['id'];?>,this)"><i class="bi bi-heart<?php echo in_array($item['id'],$user_wishlist)?'-fill':'';?>"></i></button><?php endif;?>
<?php if($item['thumbnail']??false):?><img src="<?php echo htmlspecialchars($item['thumbnail']);?>" class="thumb <?php echo($item['blur_preview']??false)?'blur':'';?>" alt="Content"><?php else:?><div class="thumb" style="background:linear-gradient(135deg,#4267f5,#1d9bf0);display:flex;align-items:center;justify-content:center;font-size:5rem"><?php echo($item['content_type']??'')==='video'?'🎥':'📷';?></div><?php endif;?>
<?php if(!($item['is_free']??true)):?><div class="lock"><div style="font-size:3rem;margin-bottom:1rem">🔒</div><div class="price">💰 <?php echo number_format($item['price']??0);?> coins</div></div><?php else:?><div style="position:absolute;bottom:1rem;right:1rem;background:var(--green);color:#fff;padding:.5rem 1rem;border-radius:20px;font-weight:700;font-size:.9rem">FREE</div><?php endif;?></div>
<div class="info"><div class="title"><?php echo htmlspecialchars($item['title']??'Untitled');?></div><?php if($item['description']??false):?><p style="color:var(--gray);font-size:.9rem;margin-bottom:1rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?php echo htmlspecialchars($item['description']);?></p><?php endif;?>
<div class="stats"><span><i class="bi bi-eye"></i> <?php echo number_format($item['view_count']??0);?></span><span><i class="bi bi-heart"></i> <?php echo number_format($item['like_count']??0);?></span><span><i class="bi bi-cart"></i> <?php echo number_format($item['purchase_count']??0);?></span></div></div></div>
<?php endforeach;?></div><?php endif;?></div>

<script>
function changeView(mode){const url=new URL(window.location);url.searchParams.set('view',mode);window.location=url}
function toggleWishlist(contentId,button){fetch('/api/wishlist.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({content_id:contentId,action:'toggle'})}).then(r=>r.json()).then(data=>{if(data.success){button.classList.toggle('active');const icon=button.querySelector('i');icon.className=button.classList.contains('active')?'bi bi-heart-fill':'bi bi-heart'}}).catch(e=>console.error('Error:',e))}
</script>

<?php include 'views/footer.php';?>