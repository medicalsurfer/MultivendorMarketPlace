<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDB();
$B   = BASE_URL;
$id  = (int)($_GET['id'] ?? 0);

// Full product query with vendor + category info
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS cat_name, c.slug AS cat_slug,
           v.store_name, v.description AS store_desc, v.id AS vid,
           u.name AS vendor_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    JOIN vendors v    ON v.id = p.vendor_id
    JOIN users u      ON u.id = v.user_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) { header('Location: ' . $B . '/index.php'); exit; }

$pageTitle = htmlspecialchars($product['name']) . ' — Venmark';

// Related products (same category)
$related = $pdo->prepare("
    SELECT p.*, v.store_name FROM products p
    LEFT JOIN vendors v ON v.id = p.vendor_id
    WHERE p.category_id = ? AND p.id != ?
    ORDER BY p.rating DESC LIMIT 4
");
$related->execute([$product['category_id'], $id]);
$relatedProducts = $related->fetchAll();

// Reviews
$reviews = $pdo->prepare("
    SELECT r.*, u.name AS reviewer_name FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.product_id = ? ORDER BY r.created_at DESC LIMIT 20
");
$reviews->execute([$id]);
$reviewsList = $reviews->fetchAll();

// Rating breakdown
$ratingBreakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviewsList as $rev) {
    $star = max(1, min(5, (int)$rev['rating']));
    $ratingBreakdown[$star]++;
}
$totalReviews = array_sum($ratingBreakdown);

// Is this product in user's favourites?
$isFav = false;
if (isLoggedIn()) {
    $s = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
    $s->execute([$_SESSION['user_id'], $id]);
    $isFav = (bool)$s->fetch();
}

// Handle review submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submit'])) {
    if (!isLoggedIn()) {
        header('Location: ' . $B . '/login.php?redirect=' . urlencode($B . '/product-details.php?id=' . $id));
        exit;
    }
    $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');
    if ($comment) {
        $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?,?,?,?)")
            ->execute([$_SESSION['user_id'], $id, $rating, $comment]);
        $r = $pdo->prepare("SELECT AVG(rating), COUNT(*) FROM reviews WHERE product_id = ?");
        $r->execute([$id]);
        [$avg, $count] = $r->fetch(PDO::FETCH_NUM);
        $pdo->prepare("UPDATE products SET rating = ?, review_count = ? WHERE id = ?")
            ->execute([round($avg, 1), $count, $id]);
        header('Location: ' . $B . '/product-details.php?id=' . $id . '#reviews');
        exit;
    }
}

$discount  = $product['original_price'] ? round((1 - $product['price'] / $product['original_price']) * 100) : 0;
$savings   = $product['original_price'] ? ($product['original_price'] - $product['price']) : 0;
$brandName = $product['brand'] ?: $product['store_name'];

// Brand → Simple Icons slug map
$brandSlugs = [
    'Nike' => 'nike', 'Adidas' => 'adidas', 'Apple' => 'apple',
    'Samsung' => 'samsung', 'Sony' => 'sony', 'Xiaomi' => 'xiaomi',
    'New Balance' => 'newbalance', 'Asics' => 'asics', 'Razer' => 'razer',
    'Yamaha' => 'yamaha', 'Nintendo' => 'nintendo', 'Wacom' => 'wacom',
    "Levi's" => 'levis', 'Philips' => 'philips', 'IKEA' => 'ikea',
    'Lululemon' => 'lululemon', 'Audio-Technica' => 'audiotechnica',
    'Ledger' => 'ledger', 'Trezor' => 'trezor', 'Bowflex' => 'bowflex',
    'Withings' => 'withings', 'Optimum Nutrition' => 'on', 'Columbia' => 'columbia',
];
$brandSlug = $brandSlugs[$brandName] ?? null;
$brandIcon = $brandSlug ? "https://cdn.simpleicons.org/{$brandSlug}/7C3AED" : null;

include __DIR__ . '/includes/header.php';
?>

<!-- ══════════════════════════════════════════════════════
     STICKY ADD-TO-CART BAR  (reveals on scroll)
══════════════════════════════════════════════════════ -->
<div class="pdp-sticky" id="pdpStickyBar">
    <div class="pdp-sticky-inner">
        <img src="<?= htmlspecialchars($product['image']) ?>"
             alt="" class="pdp-sticky-img"
             onerror="this.src='https://via.placeholder.com/56x56?text=+'">
        <div class="pdp-sticky-info">
            <span class="pdp-sticky-name"><?= htmlspecialchars(substr($product['name'], 0, 55)) ?>…</span>
            <span class="pdp-sticky-price"><?= fcfa($product['price']) ?></span>
        </div>
        <div class="pdp-sticky-stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?= $i <= round($product['rating']) ? '' : 'empty' ?>">★</span>
            <?php endfor; ?>
            <span style="font-size:.8rem;color:var(--text-mid);margin-left:4px">(<?= $product['review_count'] ?>)</span>
        </div>
        <?php if ($product['stock'] > 0): ?>
            <button class="btn btn-primary pdp-sticky-btn"
                    onclick="addToCart(<?= $product['id'] ?>,1,this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                Add to Cart
            </button>
        <?php else: ?>
            <button class="btn btn-outline pdp-sticky-btn" disabled>Out of Stock</button>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════
     BREADCRUMB
══════════════════════════════════════════════════════ -->
<div class="breadcrumb">
    <a href="<?= $B ?>/index.php">Home</a>
    <span>›</span>
    <a href="<?= $B ?>/index.php?cat=<?= $product['cat_slug'] ?>"><?= htmlspecialchars($product['cat_name']) ?></a>
    <span>›</span>
    <span><?= htmlspecialchars(substr($product['name'], 0, 50)) ?>…</span>
</div>

<!-- ══════════════════════════════════════════════════════
     MAIN PRODUCT SECTION
══════════════════════════════════════════════════════ -->
<div class="pdp-layout">

    <!-- ── GALLERY ────────────────────────────────────── -->
    <div class="pdp-gallery-col">

        <!-- Zoom wrapper -->
        <div class="pdp-zoom-wrap" id="pdpZoomWrap">
            <img id="pdpMainImg"
                 src="<?= htmlspecialchars($product['image']) ?>"
                 alt="<?= htmlspecialchars($product['name']) ?>"
                 class="pdp-main-img"
                 onerror="this.src='https://via.placeholder.com/600x500?text=No+Image'">

            <!-- Badges overlay -->
            <div class="pdp-img-badges">
                <?php if ($discount >= 10): ?>
                    <span class="pdp-badge-disc">-<?= $discount ?>%</span>
                <?php endif; ?>
                <?php if ($product['is_top_item']): ?>
                    <span class="pdp-badge-top">
                        <svg viewBox="0 0 24 24" fill="#F59E0B" width="12" height="12"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        Top Item
                    </span>
                <?php endif; ?>
            </div>

            <!-- Expand / fullscreen button -->
            <button class="pdp-expand-btn" id="pdpExpandBtn" title="View fullscreen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
            </button>

            <!-- Zoom lens (CSS + JS) -->
            <div class="pdp-lens" id="pdpLens"></div>
            <div class="pdp-zoom-result" id="pdpZoomResult"></div>
        </div>

        <!-- Thumbnails -->
        <div class="pdp-thumbs">
            <button class="pdp-thumb active" data-src="<?= htmlspecialchars($product['image']) ?>">
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="">
            </button>
            <button class="pdp-thumb" data-src="<?= htmlspecialchars($product['image']) ?>" data-filter="brightness(0.85) saturate(1.2)">
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="" style="filter:brightness(0.85) saturate(1.2)">
            </button>
            <button class="pdp-thumb" data-src="<?= htmlspecialchars($product['image']) ?>" data-filter="saturate(1.5) hue-rotate(8deg)">
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="" style="filter:saturate(1.5) hue-rotate(8deg)">
            </button>
            <button class="pdp-thumb" data-src="<?= htmlspecialchars($product['image']) ?>" data-filter="brightness(1.08) contrast(1.05)">
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="" style="filter:brightness(1.08) contrast(1.05)">
            </button>
        </div>
    </div>

    <!-- ── INFO PANEL ─────────────────────────────────── -->
    <div class="pdp-info-col" id="pdpInfoCol">

        <!-- Category chip -->
        <a href="<?= $B ?>/index.php?cat=<?= $product['cat_slug'] ?>" class="pdp-cat-chip">
            <?= htmlspecialchars($product['cat_name']) ?>
        </a>

        <!-- Brand row -->
        <div class="pdp-brand-row">
            <div class="pdp-brand-logo-wrap">
                <?php if ($brandIcon): ?>
                    <img src="<?= $brandIcon ?>"
                         alt="<?= htmlspecialchars($brandName) ?>"
                         class="pdp-brand-logo"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <span class="pdp-brand-initial" style="display:none"><?= strtoupper(substr($brandName, 0, 1)) ?></span>
                <?php else: ?>
                    <span class="pdp-brand-initial"><?= strtoupper(substr($brandName, 0, 1)) ?></span>
                <?php endif; ?>
            </div>
            <a href="<?= $B ?>/index.php?q=<?= urlencode($brandName) ?>" class="pdp-brand-name">
                <?= htmlspecialchars($brandName) ?>
            </a>
            <span class="pdp-verified-badge">
                <svg viewBox="0 0 24 24" fill="#7C3AED" width="14" height="14"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Verified
            </span>
        </div>

        <!-- Title -->
        <h1 class="pdp-title"><?= htmlspecialchars($product['name']) ?></h1>

        <!-- Rating row -->
        <div class="pdp-rating-row">
            <div class="pdp-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?= $i <= round($product['rating']) ? '' : 'empty' ?>">★</span>
                <?php endfor; ?>
            </div>
            <span class="pdp-rating-val"><?= number_format($product['rating'], 1) ?></span>
            <a href="#reviews" class="pdp-review-link"><?= $product['review_count'] ?> reviews</a>
            <span class="pdp-sep">·</span>
            <?php if ($product['stock'] > 0): ?>
                <span class="pdp-stock-badge in">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11"><polyline points="20 6 9 17 4 12"/></svg>
                    In Stock (<?= $product['stock'] ?>)
                </span>
            <?php else: ?>
                <span class="pdp-stock-badge out">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Out of Stock
                </span>
            <?php endif; ?>
        </div>

        <!-- Price block -->
        <div class="pdp-price-section">
            <div class="pdp-price-main"><?= fcfa($product['price']) ?></div>
            <?php if ($product['original_price']): ?>
                <div class="pdp-price-sub">
                    <span class="pdp-original"><?= fcfa($product['original_price']) ?></span>
                    <span class="pdp-disc-badge">-<?= $discount ?>%</span>
                    <span class="pdp-savings">Save <?= fcfa($savings) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stock progress bar -->
        <?php if ($product['stock'] > 0 && $product['stock'] <= 30): ?>
        <div class="pdp-stock-bar-wrap">
            <span class="pdp-stock-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2" width="13" height="13"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Only <?= $product['stock'] ?> left — order soon
            </span>
            <div class="pdp-stock-bar">
                <div class="pdp-stock-fill" style="width:<?= min(100, round($product['stock'] / 30 * 100)) ?>%"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Short description -->
        <p class="pdp-short-desc"><?= htmlspecialchars(mb_substr($product['description'] ?? '', 0, 160)) ?>…</p>

        <!-- Divider -->
        <div class="pdp-divider"></div>

        <!-- Quantity + CTA -->
        <div class="pdp-cta-row">
            <div class="qty-control">
                <button class="qty-btn" data-action="dec">−</button>
                <input type="number" class="qty-input" id="detailQty" value="1" min="1" max="<?= $product['stock'] ?>">
                <button class="qty-btn" data-action="inc">+</button>
            </div>
            <div class="pdp-cta-btns">
                <?php if ($product['stock'] > 0): ?>
                    <button class="btn btn-primary btn-lg pdp-cart-btn" id="mainCartBtn"
                            onclick="addToCart(<?= $product['id'] ?>, parseInt(document.getElementById('detailQty').value), this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        Add to Cart
                    </button>
                <?php else: ?>
                    <button class="btn btn-outline btn-lg" disabled>Out of Stock</button>
                <?php endif; ?>
                <button class="pdp-fav-btn <?= $isFav ? 'active' : '' ?>"
                        onclick="toggleFav(<?= $product['id'] ?>, this)"
                        title="<?= $isFav ? 'Remove from favourites' : 'Add to favourites' ?>">
                    <svg viewBox="0 0 24 24" fill="<?= $isFav ? '#EF4444' : 'none' ?>" stroke="<?= $isFav ? '#EF4444' : 'currentColor' ?>" stroke-width="2" width="20" height="20"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </button>
                <button class="pdp-share-btn" onclick="shareProduct()" title="Share this product">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                </button>
            </div>
        </div>

        <!-- Trust badges -->
        <div class="pdp-trust">
            <div class="pdp-trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Secure Payment</span>
            </div>
            <div class="pdp-trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                <span>Easy Returns</span>
            </div>
            <div class="pdp-trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
                <span>Quality Guarantee</span>
            </div>
            <div class="pdp-trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                <span>Fast Delivery</span>
            </div>
        </div>

        <!-- Delivery options -->
        <div class="pdp-section">
            <div class="pdp-section-head">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Delivery Options
            </div>
            <div class="pdp-delivery-opts">
                <?php if (in_array($product['delivery_type'], ['standard', 'both'])): ?>
                    <div class="pdp-delivery-opt">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" width="18" height="18"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        <div>
                            <strong>Standard Delivery</strong>
                            <span>3–5 business days</span>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (in_array($product['delivery_type'], ['pickup', 'both'])): ?>
                    <div class="pdp-delivery-opt">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" width="18" height="18"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        <div>
                            <strong>Store Pick Up</strong>
                            <span>Ready within 24h</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sold by -->
        <div class="pdp-section">
            <div class="pdp-section-head">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Sold By
            </div>
            <div class="pdp-vendor-card">
                <div class="pdp-vendor-avatar"><?= strtoupper(substr($product['store_name'], 0, 1)) ?></div>
                <div class="pdp-vendor-info">
                    <strong><?= htmlspecialchars($product['store_name']) ?></strong>
                    <p><?= htmlspecialchars(substr($product['store_desc'] ?? 'Verified marketplace seller.', 0, 90)) ?>…</p>
                    <div class="pdp-vendor-meta">
                        <span>
                            <svg viewBox="0 0 24 24" fill="#F59E0B" width="12" height="12"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            Verified Seller
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                            Secure Transactions
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /pdp-info-col -->
</div><!-- /pdp-layout -->

<!-- ══════════════════════════════════════════════════════
     TABS: Description · Specs · Reviews
══════════════════════════════════════════════════════ -->
<div class="pdp-tabs-wrap">
    <div class="pdp-tab-bar">
        <button class="pdp-tab active" data-tab="desc">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Description
        </button>
        <button class="pdp-tab" data-tab="specs">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            Specifications
        </button>
        <button class="pdp-tab" data-tab="reviews" id="reviewsTab">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Reviews
            <?php if ($product['review_count'] > 0): ?>
                <span class="pdp-tab-count"><?= $product['review_count'] ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Description panel -->
    <div class="pdp-tab-panel active" id="tab-desc">
        <div class="pdp-desc-body">
            <h3>About this product</h3>
            <p><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>

            <?php
            // Generate feature bullets from description
            $descWords = str_word_count($product['description'] ?? '', 0);
            $features = [];
            if (stripos($product['description'], 'wireless') !== false) $features[] = 'Wireless connectivity';
            if (stripos($product['description'], 'bluetooth') !== false) $features[] = 'Bluetooth enabled';
            if (stripos($product['description'], 'waterproof') !== false || stripos($product['description'], 'water') !== false) $features[] = 'Water resistant';
            if (stripos($product['description'], 'premium') !== false) $features[] = 'Premium quality build';
            if (stripos($product['description'], 'lightweight') !== false || stripos($product['description'], 'light') !== false) $features[] = 'Lightweight design';
            if (stripos($product['description'], 'durable') !== false || stripos($product['description'], 'durability') !== false) $features[] = 'High durability';
            if (stripos($product['description'], 'comfort') !== false || stripos($product['description'], 'comfortable') !== false) $features[] = 'Ergonomic & comfortable';
            if (empty($features)) {
                $features = ['Premium quality', 'Authentic product', 'Verified seller', 'Satisfaction guaranteed'];
            }
            ?>
            <?php if (!empty($features)): ?>
            <div class="pdp-features">
                <h4>Key Features</h4>
                <ul class="pdp-feature-list">
                    <?php foreach ($features as $f): ?>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.5" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
                            <?= htmlspecialchars($f) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Specifications panel -->
    <div class="pdp-tab-panel" id="tab-specs">
        <div class="pdp-specs-wrap">
            <h3>Product Specifications</h3>
            <table class="pdp-specs-table">
                <tbody>
                    <tr>
                        <th>Brand</th>
                        <td>
                            <?php if ($brandIcon): ?>
                                <img src="<?= $brandIcon ?>" alt="<?= htmlspecialchars($brandName) ?>"
                                     style="height:18px;width:auto;vertical-align:middle;margin-right:7px"
                                     onerror="this.style.display='none'">
                            <?php endif; ?>
                            <?= htmlspecialchars($brandName) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Category</th>
                        <td><?= htmlspecialchars($product['cat_name']) ?></td>
                    </tr>
                    <tr>
                        <th>SKU / Model</th>
                        <td>VNM-<?= str_pad($product['id'], 5, '0', STR_PAD_LEFT) ?></td>
                    </tr>
                    <tr>
                        <th>Condition</th>
                        <td><span style="color:var(--success);font-weight:600">Brand New</span></td>
                    </tr>
                    <tr>
                        <th>Availability</th>
                        <td>
                            <?php if ($product['stock'] > 0): ?>
                                <span style="color:var(--success);font-weight:600"><?= $product['stock'] ?> units in stock</span>
                            <?php else: ?>
                                <span style="color:var(--danger);font-weight:600">Out of Stock</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Delivery</th>
                        <td>
                            <?php
                            $dt = $product['delivery_type'];
                            echo $dt === 'standard' ? 'Standard Delivery' :
                                ($dt === 'pickup' ? 'Store Pick Up' : 'Standard Delivery &amp; Pick Up');
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Seller</th>
                        <td><?= htmlspecialchars($product['store_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Customer Rating</th>
                        <td>
                            <span style="color:var(--accent);font-weight:700"><?= $product['rating'] ?>/5</span>
                            <span style="color:var(--text-mid);font-size:.85rem;margin-left:6px">(<?= $product['review_count'] ?> reviews)</span>
                        </td>
                    </tr>
                    <?php if ($product['original_price']): ?>
                    <tr>
                        <th>Discount</th>
                        <td><span style="color:var(--danger);font-weight:700">-<?= $discount ?>% off</span> — You save <?= fcfa($savings) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reviews panel -->
    <div class="pdp-tab-panel" id="tab-reviews" id="reviews">
        <div class="pdp-reviews-wrap">

            <!-- Rating overview -->
            <div class="pdp-rating-overview">
                <div class="pdp-rating-big">
                    <span class="pdp-rating-num"><?= number_format($product['rating'], 1) ?></span>
                    <div class="pdp-stars-lg">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?= $i <= round($product['rating']) ? '' : 'empty' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <span class="pdp-rating-count"><?= $totalReviews ?: $product['review_count'] ?> reviews</span>
                </div>

                <!-- Rating breakdown bars -->
                <div class="pdp-rating-bars">
                    <?php foreach ([5,4,3,2,1] as $star): ?>
                    <div class="pdp-bar-row">
                        <span class="pdp-bar-label"><?= $star ?>★</span>
                        <div class="pdp-bar-track">
                            <div class="pdp-bar-fill"
                                 style="width:<?= $totalReviews ? round($ratingBreakdown[$star] / $totalReviews * 100) : 0 ?>%"
                                 data-pct="<?= $totalReviews ? round($ratingBreakdown[$star] / $totalReviews * 100) : 0 ?>"></div>
                        </div>
                        <span class="pdp-bar-count"><?= $ratingBreakdown[$star] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Write a review -->
            <?php if (isLoggedIn()): ?>
            <div class="pdp-write-review">
                <h4>Write a Review</h4>
                <form method="POST">
                    <div class="pdp-star-picker" id="starPicker">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star" data-val="<?= $i ?>" onclick="setRating(<?= $i ?>)">★</span>
                        <?php endfor; ?>
                        <span class="pdp-star-hint" id="starHint">Click to rate</span>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="5">
                    <textarea name="comment"
                              placeholder="Share your experience — what did you like or dislike?"
                              required class="pdp-review-ta"></textarea>
                    <button type="submit" name="review_submit" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Submit Review
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="pdp-login-prompt">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="36" height="36"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                <p>Love this product? <a href="<?= $B ?>/login.php?redirect=<?= urlencode($B . '/product-details.php?id=' . $id . '#reviews') ?>">Login to write a review</a></p>
            </div>
            <?php endif; ?>

            <!-- Reviews list -->
            <?php if (empty($reviewsList)): ?>
                <div class="pdp-no-reviews">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <h4>No reviews yet</h4>
                    <p>Be the first to share your thoughts!</p>
                </div>
            <?php else: ?>
                <div class="pdp-reviews-list">
                    <?php foreach ($reviewsList as $rev): ?>
                    <div class="pdp-review-card">
                        <div class="pdp-review-top">
                            <div class="pdp-reviewer-avatar"><?= strtoupper(substr($rev['reviewer_name'], 0, 1)) ?></div>
                            <div class="pdp-reviewer-meta">
                                <strong><?= htmlspecialchars($rev['reviewer_name']) ?></strong>
                                <span><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <div class="pdp-review-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?= $i <= $rev['rating'] ? '' : 'empty' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="pdp-review-text"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                        <div class="pdp-review-helpful">
                            <button class="pdp-helpful-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                                Helpful
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════
     RELATED PRODUCTS
══════════════════════════════════════════════════════ -->
<?php if (!empty($relatedProducts)): ?>
<div class="pdp-related">
    <div class="pdp-related-head">
        <h2>You May Also Like</h2>
        <a href="<?= $B ?>/index.php?cat=<?= $product['cat_slug'] ?>" class="btn btn-outline btn-sm">View all</a>
    </div>
    <div class="pdp-related-grid">
        <?php foreach ($relatedProducts as $rp): ?>
        <div class="product-card" onclick="window.location='<?= $B ?>/product-details.php?id=<?= $rp['id'] ?>'">
            <div class="product-img-wrap">
                <img src="<?= htmlspecialchars($rp['image']) ?>"
                     alt="<?= htmlspecialchars($rp['name']) ?>"
                     loading="lazy"
                     onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
            </div>
            <div class="product-body">
                <div class="product-name"><?= htmlspecialchars($rp['name']) ?></div>
                <?php if (!empty($rp['store_name'])): ?>
                    <div class="product-brand"><?= htmlspecialchars($rp['store_name']) ?></div>
                <?php endif; ?>
                <div class="product-price-row">
                    <button class="price-btn" onclick="event.stopPropagation();addToCart(<?= $rp['id'] ?>,1,this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <?= fcfa($rp['price']) ?>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Fullscreen lightbox -->
<div class="pdp-lightbox" id="pdpLightbox">
    <button class="pdp-lb-close" id="pdpLbClose">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="24" height="24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <img id="pdpLbImg" src="" alt="">
</div>

<script>
/* ── Tab switching ────────────────────────── */
document.querySelectorAll('.pdp-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.pdp-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.pdp-tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        const panel = document.getElementById('tab-' + btn.dataset.tab);
        if (panel) {
            panel.classList.add('active');
            // Animate rating bars on first open
            if (btn.dataset.tab === 'reviews') {
                panel.querySelectorAll('.pdp-bar-fill').forEach(bar => {
                    bar.style.width = '0';
                    setTimeout(() => { bar.style.width = bar.dataset.pct + '%'; }, 80);
                });
            }
        }
    });
});

/* Jump to reviews tab from anchor links */
document.querySelectorAll('a[href="#reviews"], .pdp-review-link').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        document.getElementById('reviewsTab')?.click();
        document.querySelector('.pdp-tabs-wrap')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

/* ── Thumbnail switching ──────────────────── */
document.querySelectorAll('.pdp-thumb').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.pdp-thumb').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        const mainImg = document.getElementById('pdpMainImg');
        if (!mainImg) return;
        mainImg.style.opacity = '0';
        mainImg.style.transform = 'scale(0.97)';
        setTimeout(() => {
            mainImg.src = btn.dataset.src;
            mainImg.style.filter = btn.dataset.filter || '';
            mainImg.style.opacity = '1';
            mainImg.style.transform = '';
        }, 160);
    });
});

/* ── Zoom on hover ───────────────────────── */
const zoomWrap   = document.getElementById('pdpZoomWrap');
const lens       = document.getElementById('pdpLens');
const zoomResult = document.getElementById('pdpZoomResult');
const mainImg    = document.getElementById('pdpMainImg');

if (zoomWrap && window.matchMedia('(hover:hover)').matches) {
    zoomWrap.addEventListener('mousemove', e => {
        const rect   = zoomWrap.getBoundingClientRect();
        const x      = e.clientX - rect.left;
        const y      = e.clientY - rect.top;
        const lw     = lens.offsetWidth  / 2;
        const lh     = lens.offsetHeight / 2;
        const lx     = Math.min(Math.max(x - lw, 0), rect.width  - lens.offsetWidth);
        const ly     = Math.min(Math.max(y - lh, 0), rect.height - lens.offsetHeight);
        lens.style.left = lx + 'px';
        lens.style.top  = ly + 'py';
        lens.style.top  = ly + 'px';
        const scale  = 2.5;
        zoomResult.style.backgroundImage    = `url(${mainImg.src})`;
        zoomResult.style.backgroundSize     = `${rect.width * scale}px ${rect.height * scale}px`;
        zoomResult.style.backgroundPosition = `-${lx * scale}px -${ly * scale}px`;
    });
    zoomWrap.addEventListener('mouseenter', () => {
        lens.style.display       = 'block';
        zoomResult.style.display = 'block';
    });
    zoomWrap.addEventListener('mouseleave', () => {
        lens.style.display       = 'none';
        zoomResult.style.display = 'none';
    });
}

/* ── Lightbox ────────────────────────────── */
const lightbox = document.getElementById('pdpLightbox');
const lbImg    = document.getElementById('pdpLbImg');
document.getElementById('pdpExpandBtn')?.addEventListener('click', () => {
    lbImg.src = mainImg.src;
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
});
document.getElementById('pdpLbClose')?.addEventListener('click', closeLb);
lightbox?.addEventListener('click', e => { if (e.target === lightbox) closeLb(); });
function closeLb() {
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLb(); });

/* ── Sticky bar ──────────────────────────── */
const stickyBar   = document.getElementById('pdpStickyBar');
const mainCartBtn = document.getElementById('mainCartBtn');
if (stickyBar && mainCartBtn) {
    const obs = new IntersectionObserver(entries => {
        stickyBar.classList.toggle('visible', !entries[0].isIntersecting);
    }, { threshold: 0 });
    obs.observe(mainCartBtn);
}

/* ── Star rating picker ──────────────────── */
const ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
function setRating(val) {
    document.getElementById('ratingInput').value = val;
    const hint = document.getElementById('starHint');
    if (hint) hint.textContent = ratingLabels[val] || '';
    document.querySelectorAll('#starPicker .star').forEach((s, i) => {
        s.classList.toggle('empty', i >= val);
    });
}
document.querySelectorAll('#starPicker .star').forEach(s => {
    s.addEventListener('mouseover', () => {
        const v = +s.dataset.val;
        document.querySelectorAll('#starPicker .star').forEach((st, i) => st.classList.toggle('empty', i >= v));
        const hint = document.getElementById('starHint');
        if (hint) hint.textContent = ratingLabels[v] || '';
    });
    s.addEventListener('mouseleave', () => {
        setRating(+document.getElementById('ratingInput').value);
    });
});

/* ── Share ───────────────────────────────── */
function shareProduct() {
    if (navigator.share) {
        navigator.share({ title: <?= json_encode($product['name']) ?>, url: window.location.href });
    } else {
        navigator.clipboard?.writeText(window.location.href)
            .then(() => showToast('Link copied to clipboard!', 'success'))
            .catch(() => showToast('Copy the URL from your address bar.', 'default'));
    }
}

/* ── Save to recently viewed (localStorage) ─ */
try {
    const key     = 'vnm_recent';
    const pid     = <?= $product['id'] ?>;
    let   recent  = JSON.parse(localStorage.getItem(key) || '[]');
    recent        = [pid, ...recent.filter(x => x !== pid)].slice(0, 10);
    localStorage.setItem(key, JSON.stringify(recent));
} catch(e) {}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
