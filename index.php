<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Venmark — Shop Everything';
$B = BASE_URL;

$pdo = getDB();

// ── Check DB is set up (redirect to setup if not) ──────────────
try {
    $pdo->query("SELECT id, slug FROM categories LIMIT 1");
    $pdo->query("SELECT is_top_item FROM products LIMIT 1");
    $pdo->query("SELECT store_name FROM vendors LIMIT 1");
} catch (PDOException $e) {
    header('Location: ' . $B . '/setup.php');
    exit;
}

// ── Filters from GET ─────────────────────────────────────
$catSlug   = $_GET['cat']       ?? '';
$search    = trim($_GET['q']    ?? '');
$minPrice  = (float)($_GET['min_price'] ?? 0);
$maxPrice  = (float)($_GET['max_price'] ?? 9999);
$minRating = (float)($_GET['min_rating'] ?? 0);
$brands    = !empty($_GET['brands']) ? explode(',', $_GET['brands']) : [];
$delivery  = $_GET['delivery']  ?? '';
$sort      = $_GET['sort']      ?? 'default';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 9;

// ── Categories ─────────────────────────────────────────────
$categories = $pdo->query("SELECT * FROM categories ORDER BY id")->fetchAll();
$activeCat  = null;
foreach ($categories as $c) {
    if (($c['slug'] ?? '') === $catSlug && $catSlug !== '') {
        $activeCat = $c;
        break;
    }
}

// ── Build WHERE clause ────────────────────────────────────
$where  = ['p.price >= ?', 'p.price <= ?'];
$params = [$minPrice, $maxPrice];

// Filter by category ID (safe — doesn't rely on c.slug in SQL)
if ($activeCat && ($activeCat['slug'] ?? '') !== 'all') {
    $where[]  = 'p.category_id = ?';
    $params[] = $activeCat['id'];
}
if ($search) {
    $where[]  = '(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($minRating > 0) {
    $where[]  = 'p.rating >= ?';
    $params[] = $minRating;
}
if (!empty($brands)) {
    $ph      = implode(',', array_fill(0, count($brands), '?'));
    $where[] = "p.brand IN ($ph)";
    $params  = array_merge($params, $brands);
}
if ($delivery === 'standard') {
    $where[] = "p.delivery_type IN ('standard','both')";
} elseif ($delivery === 'pickup') {
    $where[] = "p.delivery_type IN ('pickup','both')";
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// ── ORDER BY ──────────────────────────────────────────────
$orderMap = [
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'rating'     => 'p.rating DESC',
    'newest'     => 'p.created_at DESC',
    'default'    => 'p.is_top_item DESC, p.rating DESC',
];
$orderSQL = $orderMap[$sort] ?? $orderMap['default'];

// ── Count & Fetch ─────────────────────────────────────────
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM products p
     JOIN categories c ON c.id = p.category_id
     LEFT JOIN vendors v ON v.id = p.vendor_id
     $whereSQL"
);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages    = max(1, ceil($totalProducts / $perPage));
$offset        = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS cat_name, v.store_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     LEFT JOIN vendors v ON v.id = p.vendor_id
     $whereSQL
     ORDER BY $orderSQL
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

// ── Favorites ─────────────────────────────────────────────
$userFavs = [];
if (isLoggedIn()) {
    $s = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
    $s->execute([$_SESSION['user_id']]);
    $userFavs = array_column($s->fetchAll(), 'product_id');
}

// ── Brands for sidebar ────────────────────────────────────
$allBrands = $pdo->query(
    "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand"
)->fetchAll(PDO::FETCH_COLUMN);

// ── Featured product for hero ─────────────────────────────
$featured = $pdo->query(
    "SELECT p.*, v.store_name FROM products p
     LEFT JOIN vendors v ON v.id = p.vendor_id
     WHERE p.is_top_item = 1
     ORDER BY RAND() LIMIT 1"
)->fetch();

// ── Category SVG icons (Lucide icon paths) ───────────────────
$catSvgIcons = [
    'all'            => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>',
    'deals'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7" stroke-width="3"/></svg>',
    'crypto'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M11.767 19.089c4.924.868 6.14-6.025 1.216-6.894m-1.216 6.894L5.86 18.047m5.908 1.042-.347 1.97m1.563-8.864c4.924.869 6.14-6.025 1.215-6.893m-1.215 6.893-3.94-.694m5.155-6.2L8.29 4.26m5.908 1.042.348-1.97M7.48 20.364l3.126-17.727"/></svg>',
    'fashion'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.57a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.57a2 2 0 0 0-1.34-2.23z"/></svg>',
    'health-wellness'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
    'art'            => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.47-1.125-.29-.289-.47-.688-.47-1.125a1.64 1.64 0 0 1 1.648-1.648h1.93c3.243 0 5.712-2.469 5.712-5.712C22 6.245 17.479 2 12 2z"/></svg>',
    'home'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
    'sport'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>',
    'music'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
    'gaming'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><line x1="15" y1="13" x2="15.01" y2="13" stroke-width="3"/><line x1="18" y1="11" x2="18.01" y2="11" stroke-width="3"/><rect x="2" y="6" width="20" height="12" rx="2"/></svg>',
    'electronics'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
];

// ── Brand → Simple Icons slug (cdn.simpleicons.org) ──────────
$brandIconSlugs = [
    'Nike'             => 'nike',
    'Adidas'           => 'adidas',
    'Apple'            => 'apple',
    'Samsung'          => 'samsung',
    'Sony'             => 'sony',
    'Xiaomi'           => 'xiaomi',
    'New Balance'      => 'newbalance',
    'Asics'            => 'asics',
    'Columbia'         => 'columbia',
    'Razer'            => 'razer',
    'Yamaha'           => 'yamaha',
    'Nintendo'         => 'nintendo',
    'Wacom'            => 'wacom',
    "Levi's"           => 'levis',
    'Philips'          => 'philips',
    'IKEA'             => 'ikea',
    'Lululemon'        => 'lululemon',
    'Audio-Technica'   => 'audiotechnica',
    'Ledger'           => 'ledger',
    'Trezor'           => 'trezor',
    'Bowflex'          => 'bowflex',
    'Withings'         => 'withings',
    'Optimum Nutrition'=> 'optimumnutrition',
];

include __DIR__ . '/includes/header.php';
?>

<!-- ── Category Bar ──────────────────────────────────────── -->
<div class="category-bar">
    <div class="category-tabs">
        <a href="<?= $B ?>/index.php#products" class="cat-tab <?= empty($catSlug) ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            All Categories
        </a>
        <?php foreach ($categories as $cat): ?>
            <?php $slug = $cat['slug'] ?? ''; if ($slug === 'all') continue; ?>
            <a href="<?= $B ?>/index.php?cat=<?= urlencode($slug) ?>#products"
               class="cat-tab <?= $catSlug === $slug ? 'active' : '' ?>">
                <?= $catSvgIcons[$slug] ?? '' ?>
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── Hero Banner ──────────────────────────────────────── -->
<div class="hero-banner">
    <div class="hero-main">
        <div class="hero-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            New Arrivals
        </div>
        <h1>Discover Amazing<br>Products Daily</h1>
        <p>Shop from thousands of verified vendors with unbeatable prices and fast delivery.</p>
        <!-- Hero search bar -->
        <form method="GET" action="<?= $B ?>/index.php" class="hero-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" placeholder="Search products, brands…" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="hero-search-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Search
            </button>
        </form>

        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:18px;">
            <a href="<?= $B ?>/index.php?cat=deals" class="btn btn-primary btn-lg">Shop Deals</a>
            <a href="<?= $B ?>/register.php" class="btn btn-outline btn-lg"
               style="color:white;border-color:rgba(255,255,255,0.5);">Sell With Us</a>
        </div>

        <!-- Hero stats -->
        <div class="hero-stats">
            <div class="hero-stat">
                <span class="hero-stat-num"><?= $totalProducts ?>+</span>
                <span class="hero-stat-label">Products</span>
            </div>
            <div class="hero-stat-divider"></div>
            <?php
                $vendorCount = (int)$pdo->query("SELECT COUNT(*) FROM vendors")->fetchColumn();
            ?>
            <div class="hero-stat">
                <span class="hero-stat-num"><?= $vendorCount ?>+</span>
                <span class="hero-stat-label">Vendors</span>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat">
                <span class="hero-stat-num">Fast</span>
                <span class="hero-stat-label">Delivery</span>
            </div>
        </div>
        <div style="position:absolute;right:40px;bottom:40px;opacity:0.07;pointer-events:none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1" width="120" height="120"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
    </div>
    <?php if ($featured): ?>
    <div class="hero-side">
        <p class="hero-side-label">
            <svg viewBox="0 0 24 24" fill="currentColor" stroke="none" width="14" height="14"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Featured Today
        </p>
        <div class="featured-product-card">
            <a href="<?= $B ?>/product-details.php?id=<?= $featured['id'] ?>">
                <img src="<?= htmlspecialchars(getImageUrl($featured['image'] ?? '')) ?>"
                     alt="<?= htmlspecialchars($featured['name']) ?>"
                     class="featured-img">
            </a>
            <div class="featured-info">
                <h3><?= htmlspecialchars($featured['name']) ?></h3>
                <p class="store-name">by <?= htmlspecialchars($featured['store_name'] ?? 'Venmark Seller') ?></p>
                <p class="price"><?= fcfa($featured['price']) ?></p>
            </div>
            <button class="add-to-cart-hero"
                    onclick="addToCart(<?= $featured['id'] ?>, 1, this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                Add to Cart — <?= fcfa($featured['price']) ?>
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── Trust Bar ─────────────────────────────────────────── -->
<div class="trust-bar">
    <div class="trust-bar-inner">
        <div class="trust-item">
            <div class="trust-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
            <div class="trust-text"><strong>Free Delivery</strong><span>On orders over 50 000 FCFA</span></div>
        </div>
        <div class="trust-item">
            <div class="trust-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
            <div class="trust-text"><strong>Secure Payment</strong><span>100% protected checkout</span></div>
        </div>
        <div class="trust-item">
            <div class="trust-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg></div>
            <div class="trust-text"><strong>Easy Returns</strong><span>30-day hassle-free returns</span></div>
        </div>
        <div class="trust-item">
            <div class="trust-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div>
            <div class="trust-text"><strong>Quality Guarantee</strong><span>Verified sellers only</span></div>
        </div>
    </div>
</div>

<!-- ── Filter Drawer Overlay ─────────────────────────────── -->
<div class="fd-overlay" id="fdOverlay" onclick="closeFilterDrawer()"></div>

<!-- ── Filter Drawer ─────────────────────────────────────── -->
<div class="filter-drawer" id="filterDrawer">
    <div class="fd-header">
        <div class="fd-header-left">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
            <span>Filters</span>
            <?php
                $activeCount = 0;
                if ($minPrice > 0 || ($maxPrice < 9999 && $maxPrice < 1500)) $activeCount++;
                if ($minRating > 0) $activeCount++;
                if (!empty($brands)) $activeCount++;
                if ($delivery) $activeCount++;
            ?>
            <?php if ($activeCount > 0): ?>
                <span class="fd-active-badge"><?= $activeCount ?></span>
            <?php endif; ?>
        </div>
        <div class="fd-header-right">
            <?php if ($activeCount > 0): ?>
                <a href="<?= $B ?>/index.php<?= $catSlug ? '?cat='.urlencode($catSlug) : '' ?>" class="fd-clear-all">Clear all</a>
            <?php endif; ?>
            <button class="fd-close" onclick="closeFilterDrawer()" aria-label="Close filters">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    </div>

    <div class="fd-body">

        <!-- Price Range -->
        <div class="fd-section">
            <div class="fd-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Price Range
            </div>
            <div class="price-display" style="margin-bottom:14px;display:flex;align-items:center;gap:8px">
                <span class="price-pill" id="minPriceDisplay"><?= fcfa_raw($minPrice) ?></span>
                <span style="color:var(--text-light);font-size:.8rem">–</span>
                <span class="price-pill" id="maxPriceDisplay"><?= fcfa_raw($maxPrice >= 9999 ? 1500 : $maxPrice) ?></span>
            </div>
            <div class="range-slider">
                <div class="range-fill" id="rangeFill"></div>
                <input type="range" id="minPrice" min="0" max="1500" value="<?= (int)$minPrice ?>">
                <input type="range" id="maxPrice" min="0" max="1500" value="<?= (int)($maxPrice >= 9999 ? 1500 : $maxPrice) ?>">
            </div>
        </div>

        <!-- Star Rating -->
        <div class="fd-section">
            <div class="fd-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Rating
            </div>
            <div class="fd-rating-grid">
                <?php foreach ([5,4,3,2,1] as $r): ?>
                <label class="star-option <?= $minRating == $r ? 'selected' : '' ?>" data-rating="<?= $r ?>">
                    <input type="radio" name="rating" value="<?= $r ?>" style="display:none">
                    <div class="stars"><?php for ($i=1;$i<=5;$i++): ?><span class="star <?= $i<=$r?'':'empty' ?>">★</span><?php endfor; ?></div>
                    <span class="star-label"><?= $r ?>+ stars</span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Delivery Type -->
        <div class="fd-section">
            <div class="fd-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Delivery
            </div>
            <div class="fd-delivery-grid">
                <button class="fd-delivery-btn <?= (!$delivery || $delivery==='standard') ? 'active' : '' ?>" data-type="standard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    <span>Home Delivery</span>
                </button>
                <button class="fd-delivery-btn <?= $delivery==='pickup' ? 'active' : '' ?>" data-type="pickup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>Pickup Point</span>
                </button>
                <button class="fd-delivery-btn <?= $delivery==='both' ? 'active' : '' ?>" data-type="both">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Both</span>
                </button>
            </div>
        </div>

        <!-- Brand -->
        <div class="fd-section">
            <div class="fd-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7" stroke-width="3"/></svg>
                Brand
            </div>
            <div class="fd-brand-search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" class="fd-brand-search" placeholder="Search brands…" oninput="filterBrandList(this.value)">
            </div>
            <div class="fd-brand-list" id="fdBrandList">
                <?php foreach ($allBrands as $brand): ?>
                <label class="fd-brand-item">
                    <div class="fd-brand-label">
                        <?php if (isset($brandIconSlugs[$brand])): ?>
                            <img src="https://cdn.simpleicons.org/<?= $brandIconSlugs[$brand] ?>/7C3AED"
                                 class="brand-si-icon" alt="<?= htmlspecialchars($brand) ?>"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <span class="brand-initial" style="display:none"><?= strtoupper(substr($brand,0,2)) ?></span>
                        <?php else: ?>
                            <span class="brand-initial"><?= strtoupper(substr($brand,0,2)) ?></span>
                        <?php endif; ?>
                        <span class="fd-brand-name"><?= htmlspecialchars($brand) ?></span>
                    </div>
                    <div class="fd-checkbox-wrap" onclick="this.closest('label').querySelector('.brand-checkbox').click()">
                        <input type="checkbox" class="brand-checkbox" value="<?= htmlspecialchars($brand) ?>"
                               <?= in_array($brand, $brands) ? 'checked' : '' ?> style="display:none">
                        <div class="fd-checkbox <?= in_array($brand, $brands) ? 'checked' : '' ?>"></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <div class="fd-footer">
        <button class="fd-apply-btn" onclick="applyFilters();closeFilterDrawer()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Show <?= $totalProducts ?> results
        </button>
    </div>
</div>

<!-- ── Smart Filter Bar ──────────────────────────────── -->
    <div class="smart-bar">
        <div class="smart-bar-inner">

            <!-- Count -->
            <span class="smart-bar-count">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                <strong><?= $totalProducts ?></strong> <?= $totalProducts === 1 ? 'product' : 'products' ?>
            </span>

            <!-- Active filter chips -->
            <?php
                $clearBase = $B . '/index.php' . ($catSlug ? '?cat='.urlencode($catSlug) : '?');
                function chipUrl($base, $keep, $params) {
                    $parts = [];
                    if (!empty($keep['cat']))       $parts[] = 'cat=' . urlencode($keep['cat']);
                    if (!empty($keep['q']))          $parts[] = 'q=' . urlencode($keep['q']);
                    if (!empty($keep['min_price']) && $params['min_price'] != $keep['min_price']) $parts[] = 'min_price='.$keep['min_price'];
                    if (!empty($keep['max_price']) && $params['max_price'] != $keep['max_price']) $parts[] = 'max_price='.$keep['max_price'];
                    if (!empty($keep['min_rating']) && $params['min_rating'] != $keep['min_rating']) $parts[] = 'min_rating='.$keep['min_rating'];
                    if (!empty($keep['brands']) && $params['brands'] != $keep['brands']) $parts[] = 'brands='.$keep['brands'];
                    if (!empty($keep['delivery']) && $params['delivery'] != $keep['delivery']) $parts[] = 'delivery='.$keep['delivery'];
                    if (!empty($keep['sort'])) $parts[] = 'sort='.$keep['sort'];
                    return $base . (strpos($base,'?')===false?'?':'&') . implode('&', $parts);
                }
                $curParams = $_GET;
                $baseUrl = $B . '/index.php';
            ?>

            <?php if ($minPrice > 0 || ($maxPrice < 9999 && $maxPrice < 1500)): ?>
            <?php
                $removeUrl = $baseUrl . '?' . http_build_query(array_filter(array_merge($curParams, ['min_price'=>null,'max_price'=>null]), fn($v)=>$v!==null));
            ?>
            <a href="<?= $removeUrl ?>" class="active-chip">
                💰 Price
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </a>
            <?php endif; ?>

            <?php if ($minRating > 0): ?>
            <?php
                $removeUrl = $baseUrl . '?' . http_build_query(array_filter(array_merge($curParams, ['min_rating'=>null]), fn($v)=>$v!==null));
            ?>
            <a href="<?= $removeUrl ?>" class="active-chip">
                <?= $minRating ?>★ & up
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </a>
            <?php endif; ?>

            <?php if (!empty($brands)): ?>
            <?php
                $removeUrl = $baseUrl . '?' . http_build_query(array_filter(array_merge($curParams, ['brands'=>null]), fn($v)=>$v!==null));
            ?>
            <a href="<?= $removeUrl ?>" class="active-chip">
                <?= count($brands) === 1 ? htmlspecialchars($brands[0]) : count($brands).' brands' ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </a>
            <?php endif; ?>

            <?php if ($delivery): ?>
            <?php
                $removeUrl = $baseUrl . '?' . http_build_query(array_filter(array_merge($curParams, ['delivery'=>null]), fn($v)=>$v!==null));
            ?>
            <a href="<?= $removeUrl ?>" class="active-chip">
                <?= $delivery === 'pickup' ? 'Pickup' : ($delivery === 'both' ? 'All delivery' : 'Home delivery') ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </a>
            <?php endif; ?>

            <!-- Product search -->
            <form class="sbar-search" action="<?= $B ?>/index.php" method="GET">
                <?php if ($catSlug): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($catSlug) ?>"><?php endif; ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="q" placeholder="Search products…" value="<?= htmlspecialchars($search) ?>">
            </form>

            <!-- Separator -->
            <span class="sbar-sep"></span>

            <!-- Sort label + select -->
            <span class="sbar-sort-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="9" y1="18" x2="15" y2="18"/></svg>
                Sort
            </span>
            <select id="sortSelect" class="sort-select">
                <option value="default"    <?= $sort==='default'    ?'selected':''?>>Best Match</option>
                <option value="price_asc"  <?= $sort==='price_asc'  ?'selected':''?>>Price: Low → High</option>
                <option value="price_desc" <?= $sort==='price_desc' ?'selected':''?>>Price: High → Low</option>
                <option value="rating"     <?= $sort==='rating'     ?'selected':''?>>Top Rated</option>
                <option value="newest"     <?= $sort==='newest'     ?'selected':''?>>Newest</option>
            </select>

            <!-- Separator -->
            <span class="sbar-sep"></span>

            <!-- Filter button -->
            <button class="fd-open-btn" onclick="openFilterDrawer()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                Filters
                <?php if ($activeCount > 0): ?><span class="fd-btn-badge"><?= $activeCount ?></span><?php endif; ?>
            </button>

        </div>
    </div>

<!-- ── Shop Layout ───────────────────────────────────────── -->
<div class="shop-layout">

    <!-- ── Products Section ─────────────────────────────── -->
    <section class="products-section" id="products">
        <div class="products-topbar">
            <h2>
                <?php if ($search): ?>
                    Results for "<em><?= htmlspecialchars($search) ?></em>"
                <?php elseif ($activeCat): ?>
                    <?= htmlspecialchars($activeCat['name']) ?>
                <?php else: ?>
                    All Products
                <?php endif; ?>
            </h2>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <h3>No products found</h3>
                <p>Try adjusting your filters or search term.</p>
                <a href="<?= $B ?>/index.php" class="btn btn-primary">Clear Filters</a>
            </div>
        <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $i => $p):
                $isFav = in_array($p['id'], $userFavs);
                $isFeaturedCard = ($i === 4);
            ?>
            <div class="product-card <?= $isFeaturedCard ? 'featured' : '' ?>"
                 onclick="window.location='<?= $B ?>/product-details.php?id=<?= $p['id'] ?>'">

                <div class="product-img-wrap">
                    <img src="<?= htmlspecialchars(getImageUrl($p['image'] ?? '')) ?>"
                         alt="<?= htmlspecialchars($p['name']) ?>"
                         loading="lazy"
                         onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">

                    <?php if ($isFeaturedCard): ?>
                    <div class="review-avatars">
                        <div class="review-chip">
                            <svg viewBox="0 0 24 24" fill="#F59E0B" stroke="none" width="13" height="13"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <span><?= number_format((float)$p['rating'], 1) ?>/5</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <button class="fav-btn <?= $isFav ? 'active' : '' ?>"
                            onclick="event.stopPropagation();toggleFav(<?= $p['id'] ?>,this)"
                            title="<?= $isFav ? 'Remove from favourites' : 'Add to favourites' ?>">
                        <svg viewBox="0 0 24 24"
                             fill="<?= $isFav ? '#EF4444' : 'none' ?>"
                             stroke="<?= $isFav ? '#EF4444' : 'currentColor' ?>"
                             stroke-width="2">
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
                        <div class="product-vendor">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            <?= htmlspecialchars($p['store_name']) ?>
                        </div>
                    <?php elseif (!empty($p['brand'])): ?>
                        <div class="product-brand"><?= htmlspecialchars($p['brand']) ?></div>
                    <?php endif; ?>
                    <div class="product-rating">
                        <?php $stars = round((float)($p['rating'] ?? 0)); ?>
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <span class="star-sm <?= $s <= $stars ? 'filled' : '' ?>">★</span>
                        <?php endfor; ?>
                        <span class="rating-count">(<?= (int)($p['review_count'] ?? 0) ?>)</span>
                    </div>
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
                    <?php
                        $dtype = $p['delivery_type'] ?? 'standard';
                        $chipLabel = $dtype === 'pickup' ? 'Pickup only' : 'Free delivery';
                        $chipIcon = $dtype === 'pickup'
                            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="10" height="10" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>'
                            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="10" height="10" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>';
                    ?>
                    <div class="product-delivery-chip"><?= $chipIcon ?><?= $chipLabel ?></div>

                    <a href="<?= $B ?>/product-details.php?id=<?= $p['id'] ?>"
                       class="card-details-btn"
                       onclick="event.stopPropagation()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        View Details
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12" stroke-linecap="round" stroke-linejoin="round" style="margin-left:auto"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($pg = 1; $pg <= $totalPages; $pg++):
                $pgParams = array_merge($_GET, ['page' => $pg]);
                $pgUrl    = '?' . http_build_query($pgParams);
            ?>
                <a href="<?= $pgUrl ?>" class="page-btn <?= $pg === $page ? 'active' : '' ?>"><?= $pg ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
