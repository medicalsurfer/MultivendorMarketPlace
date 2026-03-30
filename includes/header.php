<?php
require_once __DIR__ . '/auth.php';
$cartCount   = getCartCount();
$favCount    = getFavCount();
$user        = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$B           = BASE_URL; // shorthand
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Venmark — Your Marketplace' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $B ?>/assets/css/style.css?v=16">
</head>
<body>

<!-- ── Top Navigation ──────────────────────────────────────────── -->
<header class="navbar">
    <div class="navbar-inner">
        <!-- Logo -->
        <a href="<?= $B ?>/index.php" class="nav-logo">
            <svg width="148" height="36" viewBox="0 0 160 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="vnIconG" x1="0" y1="0" x2="32" y2="36" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#8B5CF6"/>
                        <stop offset="100%" stop-color="#6D28D9"/>
                    </linearGradient>
                    <linearGradient id="vnTextG" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#7C3AED"/>
                        <stop offset="100%" stop-color="#A855F7"/>
                    </linearGradient>
                </defs>
                <rect x="0" y="4" width="32" height="32" rx="9" fill="url(#vnIconG)"/>
                <rect x="0" y="4" width="32" height="14" rx="9" fill="white" opacity="0.08"/>
                <path d="M7.5 13L16 27L24.5 13" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="16" cy="30.5" r="1.8" fill="white" opacity="0.9"/>
                <text x="42" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="#7C3AED">Ven</text>
                <text x="82" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="url(#vnTextG)">mark</text>
            </svg>
        </a>

        <!-- Products Dropdown -->
        <div class="nav-products <?= $currentPage === 'index' ? 'active' : '' ?>">
            <button type="button" class="nav-products-trigger" onclick="toggleProductsMenu(event)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="6" height="6" rx="1"/><rect x="9" y="3" width="6" height="6" rx="1"/><rect x="16" y="3" width="6" height="6" rx="1"/><rect x="2" y="10" width="6" height="6" rx="1"/><rect x="9" y="10" width="6" height="6" rx="1"/><rect x="16" y="10" width="6" height="6" rx="1"/><rect x="2" y="17" width="6" height="6" rx="1"/><rect x="9" y="17" width="6" height="6" rx="1"/><rect x="16" y="17" width="6" height="6" rx="1"/></svg>
                Products
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12" class="nav-chevron"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-products-dropdown">
                <div class="nav-dropdown-header">Browse Categories</div>
                <div class="nav-dropdown-grid">
                    <a href="<?= $B ?>/index.php#products" class="nav-dropdown-item">
                        <span class="nav-dropdown-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                        </span>
                        All Products
                    </a>
                    <a href="<?= $B ?>/index.php?cat=deals#products" class="nav-dropdown-item">
                        <span class="nav-dropdown-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        </span>
                        Deals
                    </a>
                    <a href="<?= $B ?>/index.php?cat=electronics#products" class="nav-dropdown-item">
                        <span class="nav-dropdown-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        </span>
                        Electronics
                    </a>
                    <a href="<?= $B ?>/index.php?cat=fashion#products" class="nav-dropdown-item">
                        <span class="nav-dropdown-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.57a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.57a2 2 0 0 0-1.34-2.23z"/></svg>
                        </span>
                        Fashion
                    </a>
                    <a href="<?= $B ?>/index.php?cat=gaming#products" class="nav-dropdown-item">
                        <span class="nav-dropdown-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15" cy="13" r="1"/><circle cx="18" cy="11" r="1"/><rect x="2" y="6" width="20" height="12" rx="4"/></svg>
                        </span>
                        Gaming
                    </a>
                    <a href="<?= $B ?>/index.php?cat=sport#products" class="nav-dropdown-item">
                        <span class="nav-dropdown-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
                        </span>
                        Sport
                    </a>
                    <a href="<?= $B ?>/index.php?cat=health-wellness#products" class="nav-dropdown-item">
                        <span class="nav-dropdown-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        </span>
                        Health &amp; Wellness
                    </a>
                    <a href="<?= $B ?>/index.php?cat=home#products" class="nav-dropdown-item">
                        <span class="nav-dropdown-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </span>
                        Home &amp; Living
                    </a>
                    <a href="<?= $B ?>/index.php?cat=music#products" class="nav-dropdown-item">
                        <span class="nav-dropdown-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        </span>
                        Music
                    </a>
                    <a href="<?= $B ?>/index.php?cat=art#products" class="nav-dropdown-item">
                        <span class="nav-dropdown-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><path d="M8 12s1.5-3 4-3 4 3 4 3-1.5 3-4 3-4-3-4-3z"/></svg>
                        </span>
                        Art &amp; Craft
                    </a>
                    <a href="<?= $B ?>/index.php?cat=crypto#products" class="nav-dropdown-item">
                        <span class="nav-dropdown-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </span>
                        Crypto
                    </a>
                    <a href="<?= $B ?>/index.php#products" class="nav-dropdown-item nav-dropdown-viewall">
                        <span class="nav-dropdown-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </span>
                        View All
                    </a>
                </div>
            </div>
        </div>

        <!-- Sell link -->
        <a href="<?= $B ?>/register.php?role=vendor" class="nav-sell-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.4 7h12.8"/></svg>
            Sell
        </a>

        <!-- Search (desktop) -->
        <form class="nav-search" action="<?= $B ?>/index.php" method="GET">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" name="q" placeholder="Search products, brands…"
                   value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        </form>

        <!-- Mobile search icon (hidden on desktop) -->
        <button class="mob-search-trigger" id="mobSearchTrigger" aria-label="Search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="20" height="20"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        </button>

        <!-- Nav Actions -->
        <nav class="nav-actions">
            <a href="<?= $B ?>/orders.php" class="nav-btn <?= $currentPage === 'orders' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                </svg>
                <span>Orders</span>
            </a>

            <a href="<?= $B ?>/favorites.php" class="nav-btn <?= $currentPage === 'favorites' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <span>Favourites</span>
                <?php if ($favCount > 0): ?>
                    <span class="badge"><?= $favCount ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= $B ?>/cart.php" class="nav-btn cart-btn <?= $currentPage === 'cart' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <span>Cart</span>
                <?php if ($cartCount > 0): ?>
                    <span class="badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>

            <?php if (isLoggedIn() && (isVendor() || isAdmin())): ?>
                <a href="<?= $B ?>/vendor-dashboard/index.php" class="nav-btn <?= str_starts_with($currentPage, 'vendor') ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                    <span>Dashboard</span>
                </a>
            <?php endif; ?>


            <?php if (isLoggedIn()): ?>
                <div class="nav-user-menu">
                    <button class="nav-avatar" onclick="toggleUserMenu()">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="avatar">
                        <?php else: ?>
                            <span><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="dropdown-header">
                            <strong><?= htmlspecialchars($user['name']) ?></strong>
                            <small><?= htmlspecialchars($user['email']) ?></small>
                        </div>
                        <?php if (isVendor() || isAdmin()): ?>
                            <a href="<?= $B ?>/vendor-dashboard/index.php" class="dropdown-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                                </svg>
                                Vendor Dashboard
                            </a>
                        <?php endif; ?>
                        <a href="<?= $B ?>/orders.php" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                            </svg>
                            My Orders
                        </a>
                        <a href="<?= $B ?>/logout.php" class="dropdown-item text-danger">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= $B ?>/login.php" class="btn btn-primary btn-sm">Login</a>
            <?php endif; ?>
            <!-- Hamburger (mobile only) -->
            <button class="nav-hamburger" id="navHamburger" onclick="toggleMobileDrawer()" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </nav>
    </div>
</header>

<!-- ── Mobile Drawer ──────────────────────────────────────── -->
<div class="mobile-overlay" id="mobileOverlay" onclick="toggleMobileDrawer()"></div>
<div class="mobile-drawer" id="mobileDrawer">
    <div class="mobile-drawer-header">
        <svg width="110" height="28" viewBox="0 0 160 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="mIconG" x1="0" y1="0" x2="32" y2="36" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#6D28D9"/>
                </linearGradient>
            </defs>
            <rect x="0" y="4" width="32" height="32" rx="9" fill="url(#mIconG)"/>
            <path d="M7.5 13L16 27L24.5 13" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="16" cy="30.5" r="1.8" fill="white" opacity="0.9"/>
            <text x="42" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="#7C3AED">Ven</text>
            <text x="82" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="#7C3AED">mark</text>
        </svg>
        <button class="mobile-drawer-close" onclick="toggleMobileDrawer()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <nav class="mobile-drawer-nav">
        <a href="<?= $B ?>/index.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            Home
        </a>
        <a href="<?= $B ?>/index.php?cat=deals">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/></svg>
            Deals
        </a>
        <a href="<?= $B ?>/orders.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
            My Orders
        </a>
        <a href="<?= $B ?>/favorites.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            Favourites
        </a>
        <a href="<?= $B ?>/cart.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Cart
        </a>
        <a href="<?= $B ?>/register.php?role=vendor">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.4 7h12.8"/></svg>
            Sell on Venmark
        </a>
        <?php if (!isLoggedIn()): ?>
        <a href="<?= $B ?>/login.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Login / Register
        </a>
        <?php else: ?>
        <a href="<?= $B ?>/logout.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
        <?php endif; ?>
    </nav>
</div>
