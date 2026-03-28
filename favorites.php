<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Favourites — Venmark';
$B = BASE_URL;

requireLogin();

$pdo    = getDB();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT p.*, v.store_name
     FROM favorites f
     JOIN products p ON p.id = f.product_id
     LEFT JOIN vendors v ON v.id = p.vendor_id
     WHERE f.user_id = ?
     ORDER BY f.id DESC"
);
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <div>
            <h1>
                <svg viewBox="0 0 24 24" fill="#EF4444" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1.3rem;height:1.3rem;vertical-align:middle;margin-right:6px;"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>Favourites
                <span class="count-badge">(<?= count($favorites) ?>)</span>
            </h1>
            <p style="color:var(--text-mid);margin-top:4px;">Your saved products</p>
        </div>
        <a href="<?= $B ?>/index.php" class="btn btn-outline">Browse More</a>
    </div>

    <?php if (empty($favorites)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </div>
            <h3>No favourites yet</h3>
            <p>Click the heart icon on any product to save it here.</p>
            <a href="<?= $B ?>/index.php" class="btn btn-primary btn-lg">Discover Products</a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($favorites as $p): ?>
            <div class="product-card"
                 onclick="window.location='<?= $B ?>/product-details.php?id=<?= $p['id'] ?>'">
                <div class="product-img-wrap">
                    <img src="<?= htmlspecialchars($p['image'] ?? '') ?>"
                         alt="<?= htmlspecialchars($p['name']) ?>"
                         loading="lazy"
                         onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                    <button class="fav-btn active"
                            onclick="event.stopPropagation();toggleFav(<?= $p['id'] ?>,this);setTimeout(()=>this.closest('.product-card').remove(),400);">
                        <svg viewBox="0 0 24 24" fill="#EF4444" stroke="#EF4444" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                    </button>
                    <?php if ($p['is_top_item']): ?>
                        <span class="top-badge">Top item</span>
                    <?php endif; ?>
                </div>
                <div class="product-body">
                    <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                    <?php if (!empty($p['store_name'])): ?>
                        <div class="product-brand"><?= htmlspecialchars($p['store_name']) ?></div>
                    <?php endif; ?>
                    <div class="product-price-row">
                        <?php if (!empty($p['original_price'])): ?>
                            <span class="price-original"><?= fcfa($p['original_price']) ?></span>
                        <?php endif; ?>
                        <button class="price-btn"
                                onclick="event.stopPropagation();addToCart(<?= $p['id'] ?>,1,this)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                            </svg>
                            <?= fcfa($p['price']) ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
