<?php $B = BASE_URL; ?>

<!-- ── Cart Preview Panel ─────────────────────────────────── -->
<div class="cart-preview-overlay" id="cartPreviewOverlay" onclick="closeCartPreview()"></div>
<div class="cart-preview" id="cartPreview">
    <div class="cart-preview-header">
        <h3>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="vertical-align:middle;margin-right:6px"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Your Cart
        </h3>
        <button class="cart-preview-close" onclick="closeCartPreview()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <div class="cart-preview-items" id="cartPreviewItems">
        <div class="cart-preview-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40" style="margin:0 auto 12px;display:block;color:var(--text-light)"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Your cart is empty
        </div>
    </div>
    <div class="cart-preview-footer" id="cartPreviewFooter" style="display:none">
        <div class="cart-preview-total">
            <span>Total</span>
            <span id="cartPreviewTotal">0 FCFA</span>
        </div>
        <a href="<?= $B ?>/cart.php" class="btn btn-primary btn-full">View Cart & Checkout</a>
    </div>
</div>

<footer class="site-footer">

    <!-- Newsletter -->
    <div class="footer-newsletter">
        <div class="footer-newsletter-inner">
            <div class="footer-newsletter-text">
                <h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24" style="display: inline-block; margin-right: 8px; vertical-align: middle;"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>Stay in the loop</h3>
                <p>Get the best deals, new arrivals, and vendor news — no spam, ever.</p>
            </div>
            <form class="footer-newsletter-form" onsubmit="event.preventDefault();this.querySelector('button').textContent='Subscribed ✓';this.querySelector('input').value='';">
                <input type="email" placeholder="your@email.com" required>
                <button type="submit">Subscribe</button>
            </form>
        </div>
    </div>

    <div class="footer-inner">
        <div class="footer-brand">
            <svg width="130" height="32" viewBox="0 0 160 40" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-bottom:12px">
                <defs>
                    <linearGradient id="ftIconG" x1="0" y1="0" x2="32" y2="36" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#6D28D9"/>
                    </linearGradient>
                    <linearGradient id="ftTextG" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#C4B5FD"/><stop offset="100%" stop-color="#E9D5FF"/>
                    </linearGradient>
                </defs>
                <rect x="0" y="4" width="32" height="32" rx="9" fill="url(#ftIconG)"/>
                <path d="M7.5 13L16 27L24.5 13" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="16" cy="30.5" r="1.8" fill="white" opacity="0.9"/>
                <text x="42" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="white">Ven</text>
                <text x="82" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="url(#ftTextG)">mark</text>
            </svg>
            <p>Your trusted multi-vendor marketplace.<br>Shop from thousands of verified sellers worldwide.</p>
        </div>
        <div class="footer-links">
            <div class="footer-col">
                <h4>SHOP</h4>
                <a href="<?= $B ?>/index.php">All Products</a>
                <a href="<?= $B ?>/index.php?cat=deals">Deals</a>
                <a href="<?= $B ?>/index.php?cat=fashion">Fashion</a>
                <a href="<?= $B ?>/index.php?cat=electronics">Electronics</a>
            </div>
            <div class="footer-col">
                <h4>ACCOUNT</h4>
                <a href="<?= $B ?>/login.php">Login</a>
                <a href="<?= $B ?>/register.php">Register</a>
                <a href="<?= $B ?>/orders.php">My Orders</a>
                <a href="<?= $B ?>/favorites.php">Favourites</a>
            </div>
            <div class="footer-col">
                <h4>SELL</h4>
                <a href="<?= $B ?>/register.php?role=vendor">Become a Vendor</a>
                <a href="<?= $B ?>/vendor-dashboard/index.php">Vendor Dashboard</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="footer-bottom-row">
            <p style="color:rgba(255,255,255,0.4);font-size:0.82rem;">&copy; <?= date('Y') ?> Venmark. All rights reserved.</p>

            <!-- Social links -->
            <div class="footer-social">
                <a href="#" title="Twitter/X" aria-label="Twitter">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="#" title="Instagram" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                </a>
                <a href="#" title="Facebook" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <a href="#" title="WhatsApp" aria-label="WhatsApp">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                </a>
            </div>

            <!-- Payment badges -->
            <div class="footer-payments">
                <span class="payment-badge">VISA</span>
                <span class="payment-badge">Mastercard</span>
                <span class="payment-badge">MTN MoMo</span>
                <span class="payment-badge">Orange Money</span>
            </div>
        </div>
    </div>
</footer>

<!-- ── Mobile Bottom Navigation ────────────────────────── -->
<nav class="mob-bottom-nav" id="mobBottomNav">
    <a href="<?= $B ?>/index.php" class="mob-nav-item <?= basename($_SERVER['PHP_SELF'],'.php') === 'index' ? 'active' : '' ?>">
        <span class="mob-nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </span>
        <span class="mob-nav-label">Home</span>
    </a>
    <a href="<?= $B ?>/index.php?cat=#products" class="mob-nav-item" id="mobCatBtn" onclick="openMobCats(event)">
        <span class="mob-nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
        </span>
        <span class="mob-nav-label">Categories</span>
    </a>
    <a href="<?= $B ?>/cart.php" class="mob-nav-item mob-cart-item <?= basename($_SERVER['PHP_SELF'],'.php') === 'cart' ? 'active' : '' ?>">
        <span class="mob-nav-icon mob-cart-icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <?php if ($cartCount > 0): ?><span class="mob-badge"><?= $cartCount ?></span><?php endif; ?>
        </span>
        <span class="mob-nav-label">Cart</span>
    </a>
    <a href="<?= $B ?>/favorites.php" class="mob-nav-item <?= basename($_SERVER['PHP_SELF'],'.php') === 'favorites' ? 'active' : '' ?>">
        <span class="mob-nav-icon mob-fav-icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <?php if ($favCount > 0): ?><span class="mob-badge"><?= $favCount ?></span><?php endif; ?>
        </span>
        <span class="mob-nav-label">Saved</span>
    </a>
    <?php if (isLoggedIn()): ?>
    <a href="<?= $B ?>/orders.php" class="mob-nav-item <?= basename($_SERVER['PHP_SELF'],'.php') === 'orders' ? 'active' : '' ?>">
        <span class="mob-nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
        </span>
        <span class="mob-nav-label">Orders</span>
    </a>
    <?php else: ?>
    <a href="<?= $B ?>/login.php" class="mob-nav-item <?= basename($_SERVER['PHP_SELF'],'.php') === 'login' ? 'active' : '' ?>">
        <span class="mob-nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </span>
        <span class="mob-nav-label">Account</span>
    </a>
    <?php endif; ?>
</nav>

<!-- ── Mobile Search Overlay ────────────────────────────── -->
<div class="mob-search-overlay" id="mobSearchOverlay">
    <div class="mob-search-inner">
        <button class="mob-search-back" onclick="closeMobSearch()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="22" height="22"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <form action="<?= $B ?>/index.php" method="GET" class="mob-search-form">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" name="q" placeholder="Search products, brands…" autofocus
                   value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" id="mobSearchInput">
        </form>
    </div>
</div>

<!-- ── Mobile Categories Sheet ──────────────────────────── -->
<div class="mob-cats-overlay" id="mobCatsOverlay" onclick="closeMobCats()"></div>
<div class="mob-cats-sheet" id="mobCatsSheet">
    <div class="mob-cats-handle"></div>
    <div class="mob-cats-header">
        <span>Categories</span>
        <button onclick="closeMobCats()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>
    <div class="mob-cats-grid">
        <?php
        $mobCatIcons = [
            'deals'=>'🏷️','crypto'=>'₿','fashion'=>'👗','health-wellness'=>'💊',
            'art'=>'🎨','home'=>'🏠','sport'=>'⚽','music'=>'🎵','gaming'=>'🎮','electronics'=>'💻'
        ];
        ?>
        <a href="<?= $B ?>/index.php#products" class="mob-cat-chip" onclick="closeMobCats()">
            <span class="mob-cat-emoji">🛍️</span>
            <span>All</span>
        </a>
        <?php
        $mobCategories = $pdo->query("SELECT * FROM categories WHERE slug != 'all' ORDER BY id")->fetchAll();
        foreach ($mobCategories as $mc):
            $mslug = $mc['slug'];
        ?>
        <a href="<?= $B ?>/index.php?cat=<?= urlencode($mslug) ?>#products" class="mob-cat-chip" onclick="closeMobCats()">
            <span class="mob-cat-emoji"><?= $mobCatIcons[$mslug] ?? '📦' ?></span>
            <span><?= htmlspecialchars($mc['name']) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
window.MLC_BASE = '<?= $B ?>';
// Mobile search
function openMobSearch()  { document.getElementById('mobSearchOverlay').classList.add('open'); setTimeout(()=>document.getElementById('mobSearchInput')?.focus(),120); }
function closeMobSearch() { document.getElementById('mobSearchOverlay').classList.remove('open'); }
// Mobile categories sheet
function openMobCats(e)  { if(e) e.preventDefault(); document.getElementById('mobCatsSheet').classList.add('open'); document.getElementById('mobCatsOverlay').classList.add('open'); document.body.style.overflow='hidden'; }
function closeMobCats()  { document.getElementById('mobCatsSheet').classList.remove('open'); document.getElementById('mobCatsOverlay').classList.remove('open'); document.body.style.overflow=''; }
// Wire up mobile search icon in navbar
document.addEventListener('DOMContentLoaded', () => {
    const msi = document.getElementById('mobSearchTrigger');
    if (msi) msi.addEventListener('click', openMobSearch);
    // Hide bottom nav on scroll down, show on scroll up
    let lastY = 0;
    const bn = document.getElementById('mobBottomNav');
    window.addEventListener('scroll', () => {
        const y = window.scrollY;
        if (bn) bn.style.transform = (y > lastY && y > 80) ? 'translateY(100%)' : 'translateY(0)';
        lastY = y;
    }, { passive: true });
});
</script>
<script src="<?= $B ?>/assets/js/main.js"></script>
<script src="<?= $B ?>/assets/js/animations.js" defer></script>
</body>
</html>
