/* ═══════════════════════════════════════════════════════════
   MLC Marketplace — Main JavaScript
═══════════════════════════════════════════════════════════ */

// Base URL set by footer.php (works for root and XAMPP subdirectory)
const BASE = window.MLC_BASE || '';

// ── Auto-scroll to products on search/filter ─────────────────
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const hasSearch = urlParams.has('q') && urlParams.get('q');
    const hasCategory = urlParams.has('cat');
    const hasFilters = urlParams.has('min_price') || urlParams.has('max_price') || 
                       urlParams.has('min_rating') || urlParams.has('brands') || 
                       urlParams.has('delivery');
    
    if (hasSearch || hasCategory || hasFilters) {
        setTimeout(() => {
            const productsSection = document.getElementById('products');
            if (productsSection) {
                productsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    }
});

// ── Load products for pagination (AJAX) ──────────────────────
function loadProductPage(pageNum, event) {
    if (event) {
        event.preventDefault();
    }
    
    // Get current query parameters and update page
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', pageNum);
    
    // Scroll to top first to show hero
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // Make AJAX request
    fetch(`${BASE}/index.php?${urlParams.toString()}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        // Update products section
        const productsSection = document.getElementById('products');
        const paginationWrapper = document.querySelector('.pagination-wrapper');
        
        if (!productsSection) return;
        
        // Parse HTML to extract new content
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        const newProductsSection = doc.getElementById('products');
        const newPaginationWrapper = doc.querySelector('.pagination-wrapper');
        
        if (newProductsSection) {
            productsSection.innerHTML = newProductsSection.innerHTML;
        }
        
        if (newPaginationWrapper && paginationWrapper) {
            paginationWrapper.innerHTML = newPaginationWrapper.innerHTML;
        }
    })
    .catch(error => {
        console.error('Error loading products:', error);
        showToast('Failed to load products', 'error');
    });
}

// ── FCFA Currency Helper ───────────────────────────────────
function mlcFCFA(usdAmount) {
    const xaf = Math.round(parseFloat(usdAmount) * 655);
    return xaf.toLocaleString('fr-FR') + '\u00A0FCFA';
}

// ── Products Dropdown ──────────────────────────────────────
function toggleProductsMenu(e) {
    e.stopPropagation();
    const menu = document.querySelector('.nav-products');
    if (menu) menu.classList.toggle('open');
}
document.addEventListener('click', e => {
    const menu = document.querySelector('.nav-products');
    if (menu && !menu.contains(e.target)) menu.classList.remove('open');
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelector('.nav-products')?.classList.remove('open');
});

// ── Horizontal Filter Dropdowns ────────────────────────────
function toggleHFilter(groupId) {
    const group = document.getElementById(groupId);
    if (!group) return;
    const isOpen = group.classList.contains('open');
    // Close all others
    document.querySelectorAll('.hfilter-group.open').forEach(g => g.classList.remove('open'));
    if (!isOpen) group.classList.add('open');
}
document.addEventListener('click', e => {
    if (!e.target.closest('.hfilter-group')) {
        document.querySelectorAll('.hfilter-group.open').forEach(g => g.classList.remove('open'));
    }
});


// ── User Dropdown ──────────────────────────────────────────
function toggleUserMenu() {
    const d = document.getElementById('userDropdown');
    if (d) d.classList.toggle('show');
}
document.addEventListener('click', e => {
    const menu = document.querySelector('.nav-user-menu');
    if (menu && !menu.contains(e.target)) {
        const d = document.getElementById('userDropdown');
        if (d) d.classList.remove('show');
    }
});

// ── Toast Notifications ────────────────────────────────────
function showToast(msg, type = 'default') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icons = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>',
        error:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        cart:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
        default: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
    };
    if (type === 'cart') toast.classList.add('success');
    toast.innerHTML = `<span class="toast-icon">${icons[type] || icons.default}</span><span>${msg}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 350);
    }, 3000);
}

// ── Add to Cart ────────────────────────────────────────────
function addToCart(productId, qty = 1, btn) {
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>'; }

    fetch(BASE + '/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add', product_id: productId, quantity: qty })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Item added to cart', 'cart');
            updateCartBadge(data.cart_count);
            if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
        } else if (data.redirect) {
            window.location.href = data.redirect;
        } else {
            showToast(data.error || 'Could not add to cart', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
        }
    })
    .catch(() => {
        showToast('Network error — try again', 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
    });
}

// ── Toggle Favourite ───────────────────────────────────────
function toggleFav(productId, btn) {
    fetch(BASE + '/api/favorites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const isFav = data.is_favorite;
            btn.classList.toggle('active', isFav);
            const svg = btn.querySelector('svg');
            if (svg) {
                svg.setAttribute('fill', isFav ? '#EF4444' : 'none');
                svg.setAttribute('stroke', isFav ? '#EF4444' : 'currentColor');
            }
            showToast(isFav ? 'Added to favourites' : 'Removed from favourites', isFav ? 'success' : 'default');
            updateFavBadge(data.fav_count);
        } else if (data.redirect) {
            window.location.href = data.redirect;
        }
    })
    .catch(() => showToast('Network error', 'error'));
}

// ── Badge Updaters ─────────────────────────────────────────
function updateCartBadge(count) {
    const cartBtn = document.querySelector('.cart-btn');
    if (!cartBtn) return;
    let badge = cartBtn.querySelector('.badge');
    if (count > 0) {
        if (!badge) { badge = document.createElement('span'); badge.className = 'badge'; cartBtn.appendChild(badge); }
        badge.textContent = count;
        // Bounce animation
        badge.classList.remove('bouncing');
        void badge.offsetWidth; // reflow
        badge.classList.add('bouncing');
        badge.addEventListener('animationend', () => badge.classList.remove('bouncing'), { once: true });
    } else if (badge) {
        badge.remove();
    }
}

function updateFavBadge(count) {
    const favBtn = document.querySelector('a[href*="favorites"] .badge');
    const favNav = document.querySelector('a[href*="favorites"]');
    if (!favNav) return;
    let badge = favNav.querySelector('.badge');
    if (count > 0) {
        if (!badge) { badge = document.createElement('span'); badge.className = 'badge'; favNav.appendChild(badge); }
        badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
}

// ── Cart Quantity Control ──────────────────────────────────
function updateCartQty(productId, qty, row) {
    if (qty < 1) { removeFromCart(productId, row); return; }
    fetch(BASE + '/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update', product_id: productId, quantity: qty })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartBadge(data.cart_count);
            const priceEl  = row?.querySelector('.cart-item-price');
            const totalEl  = document.querySelector('.order-total-val');
            if (priceEl  && data.item_total)  priceEl.textContent  = mlcFCFA(data.item_total);
            if (totalEl  && data.order_total) totalEl.textContent  = mlcFCFA(data.order_total);
        }
    });
}

function removeFromCart(productId, row) {
    fetch(BASE + '/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'remove', product_id: productId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (row) { row.style.opacity = '0'; row.style.transform = 'translateX(-20px)'; row.style.transition = '0.3s'; setTimeout(() => { row.remove(); checkEmptyCart(); }, 300); }
            updateCartBadge(data.cart_count);
            showToast('Item removed', 'default');
            const totalEl = document.querySelector('.order-total-val');
            if (totalEl && data.order_total) totalEl.textContent = mlcFCFA(data.order_total);
        }
    });
}

function checkEmptyCart() {
    const items = document.querySelectorAll('.cart-item');
    if (items.length === 0) {
        const list = document.querySelector('.cart-items');
        if (list) list.innerHTML = `<div class="empty-state"><div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></div><h3>Your cart is empty</h3><p>Discover amazing products.</p><a href="${BASE}/index.php" class="btn btn-primary">Shop Now</a></div>`;
    }
}

// ── Price Range Slider ─────────────────────────────────────
function initPriceSlider() {
    const minRange   = document.getElementById('minPrice');
    const maxRange   = document.getElementById('maxPrice');
    const minDisplay = document.getElementById('minPriceDisplay');
    const maxDisplay = document.getElementById('maxPriceDisplay');
    const fill       = document.getElementById('rangeFill');
    if (!minRange || !maxRange) return;

    function updateSlider() {
        let min = parseInt(minRange.value);
        let max = parseInt(maxRange.value);
        if (min > max - 10) { min = max - 10; minRange.value = min; }
        if (minDisplay) minDisplay.textContent = mlcFCFA(min);
        if (maxDisplay) maxDisplay.textContent = mlcFCFA(max);
        const total = parseInt(maxRange.max) - parseInt(minRange.min);
        const left  = ((min - parseInt(minRange.min)) / total) * 100;
        const right = 100 - ((max - parseInt(minRange.min)) / total) * 100;
        if (fill) { fill.style.left = left + '%'; fill.style.right = right + '%'; }
    }
    minRange.addEventListener('input', updateSlider);
    maxRange.addEventListener('input', updateSlider);
    updateSlider();
    [minRange, maxRange].forEach(r => r.addEventListener('change', applyFilters));
}

// ── Star Rating Filter ─────────────────────────────────────
function initStarFilter() {
    document.querySelectorAll('.star-option').forEach(opt => {
        opt.addEventListener('click', () => {
            document.querySelectorAll('.star-option').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            applyFilters();
        });
    });
}

// ── Brand Filter ───────────────────────────────────────────
function initBrandFilter() {
    document.querySelectorAll('.brand-checkbox').forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });
}

// ── Delivery Filter ────────────────────────────────────────
function initDeliveryFilter() {
    document.querySelectorAll('.delivery-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.delivery-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilters();
        });
    });
}

// ── Category Filter (drawer pills) ────────────────────────
function initCatFilter() {
    document.querySelectorAll('.fd-cat-item').forEach(item => {
        item.addEventListener('click', () => {
            document.querySelectorAll('.fd-cat-item').forEach(i => i.classList.remove('selected'));
            item.classList.add('selected');
            const radio = item.querySelector('.fd-cat-radio');
            if (radio) radio.checked = true;
        });
    });
}

// ── Apply All Filters ──────────────────────────────────────
function applyFilters() {
    const params  = new URLSearchParams(window.location.search);
    const minEl   = document.getElementById('minPrice');
    const maxEl   = document.getElementById('maxPrice');
    if (minEl) params.set('min_price', minEl.value);
    if (maxEl) params.set('max_price', maxEl.value);

    const selectedStar = document.querySelector('.star-option.selected');
    if (selectedStar) params.set('min_rating', selectedStar.dataset.rating);
    else params.delete('min_rating');

    const checked = [...document.querySelectorAll('.brand-checkbox:checked')].map(c => c.value);
    if (checked.length) params.set('brands', checked.join(','));
    else params.delete('brands');

    const activeDelivery = document.querySelector('.delivery-btn.active');
    if (activeDelivery && activeDelivery.dataset.type && activeDelivery.dataset.type !== 'all')
        params.set('delivery', activeDelivery.dataset.type);
    else params.delete('delivery');

    const selectedCat = document.querySelector('.fd-cat-radio:checked');
    if (selectedCat && selectedCat.value) params.set('cat', selectedCat.value);
    else params.delete('cat');

    params.delete('page');
    window.location.href = window.location.pathname + '?' + params.toString() + '#products';
}

// ── Sort ───────────────────────────────────────────────────
function initSort() {
    const sortEl = document.getElementById('sortSelect');
    if (!sortEl) return;
    sortEl.addEventListener('change', () => {
        const params = new URLSearchParams(window.location.search);
        params.set('sort', sortEl.value);
        window.location.search = params.toString();
    });
}

// ── Product Detail Thumbnails ──────────────────────────────
function initThumbs() {
    document.querySelectorAll('.detail-thumb').forEach(thumb => {
        thumb.addEventListener('click', () => {
            document.querySelectorAll('.detail-thumb').forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
            const main = document.getElementById('mainImage');
            if (main) { main.style.opacity = '0'; setTimeout(() => { main.src = thumb.src; main.style.opacity = '1'; }, 150); }
        });
    });
}

// ── Quantity Control ───────────────────────────────────────
function initQtyControl() {
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('.qty-input');
            if (!input) return;
            let val = parseInt(input.value) || 1;
            val = btn.dataset.action === 'inc' ? val + 1 : Math.max(1, val - 1);
            input.value = val;
        });
    });
}

// ── Password Toggle ────────────────────────────────────────
function initPasswordToggle() {
    document.querySelectorAll('.toggle-pw').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.closest('.input-icon-wrap')?.querySelector('input[type="password"], input[type="text"]');
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
}

// ── Image Upload Preview ───────────────────────────────────
function initImagePreview() {
    const fileInput = document.getElementById('productImage');
    const preview   = document.getElementById('imagePreview');
    if (!fileInput || !preview) return;
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
            reader.readAsDataURL(file);
        }
    });
}

// ── Hero Carousel ──────────────────────────────────────────
function initHeroCarousel() {
    const slides = document.querySelectorAll('.hero-slide');
    if (slides.length <= 1) return;
    let current = 0;
    const dots = document.querySelectorAll('.carousel-dot');
    function goTo(idx) {
        slides[current].classList.remove('active');
        dots[current]?.classList.remove('active');
        current = (idx + slides.length) % slides.length;
        slides[current].classList.add('active');
        dots[current]?.classList.add('active');
    }
    document.querySelector('.carousel-prev')?.addEventListener('click', () => goTo(current - 1));
    document.querySelector('.carousel-next')?.addEventListener('click', () => goTo(current + 1));
    setInterval(() => goTo(current + 1), 4000);
}

// ── AJAX Category Switcher ─────────────────────────────────
// Intercepts .cat-tab and .nav-dropdown-item clicks on index.php
// Fetches filtered products and swaps them in without a page reload
(function initAjaxCats() {
    // Only run on the index/shop page
    if (!document.getElementById('products')) return;

    const navbarH = () => (document.querySelector('.navbar')?.offsetHeight || 0);
    const catBarH = () => (document.querySelector('.category-bar')?.offsetHeight || 0);

    function scrollToProducts() {
        const section = document.getElementById('products');
        if (!section) return;
        // Scroll so products appear right at the top under navbar and category bar
        const fixedHeaderHeight = navbarH() + catBarH();
        window.scrollTo({ 
            top: section.offsetTop - fixedHeaderHeight, 
            behavior: 'smooth' 
        });
    }

    function setLoading(on) {
        const grid = document.querySelector('.products-grid, .empty-state');
        if (!grid) return;
        if (on) {
            grid.style.transition = 'opacity 0.18s ease';
            grid.style.opacity    = '0.35';
            grid.style.pointerEvents = 'none';
        } else {
            grid.style.opacity    = '1';
            grid.style.pointerEvents = '';
        }
    }

    function swapProducts(html) {
        const parser  = new DOMParser();
        const doc     = parser.parseFromString(html, 'text/html');

        // Swap products grid
        const newGrid = doc.querySelector('.products-grid') || doc.querySelector('.empty-state');
        const oldGrid = document.querySelector('.products-grid') || document.querySelector('.empty-state');
        
        if (newGrid && oldGrid) {
            oldGrid.style.opacity = '0';
            setTimeout(() => {
                oldGrid.replaceWith(newGrid);
                newGrid.style.opacity = '0';
                newGrid.style.transition = 'opacity 0.25s ease';
                requestAnimationFrame(() => { newGrid.style.opacity = '1'; });
                // Re-init card animations if animations.js exposed initCardTilt
                if (typeof initCardTilt === 'function') initCardTilt();
                // Re-run reveal observer
                if (typeof observeReveal === 'function') observeReveal();
                // Scroll to products after fade-in completes
                setTimeout(() => scrollToProducts(), 250);
            }, 180);
        }

        // Swap topbar result count
        const newTopbar = doc.querySelector('.products-topbar');
        const oldTopbar = document.querySelector('.products-topbar');
        if (newTopbar && oldTopbar) oldTopbar.replaceWith(newTopbar);

        // Swap pagination (from separate pagination-wrapper container only)
        const newPaginationWrapper = doc.querySelector('.pagination-wrapper');
        const oldPaginationWrapper = document.querySelector('.pagination-wrapper');
        if (newPaginationWrapper && oldPaginationWrapper) {
            oldPaginationWrapper.replaceWith(newPaginationWrapper);
        }
    }

    function updateActiveTabs(catParam) {
        document.querySelectorAll('.cat-tab').forEach(tab => {
            const href   = tab.getAttribute('href') || '';
            const url    = new URL(href, window.location.origin);
            const tabCat = url.searchParams.get('cat') || '';
            tab.classList.toggle('active', tabCat === catParam);
        });
    }

    async function loadProducts(url, state = {}) {
        setLoading(true);
        try {
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await res.text();
            swapProducts(html);
            history.pushState(state, '', url);
            updateActiveTabs(state.cat || '');
        } catch (err) {
            console.error('Error loading products:', err);
            // Fallback to normal navigation on error
            window.location.href = url;
        } finally {
            setLoading(false);
        }
    }

    // Delegate click on category tabs, nav dropdown items, and pagination links
    document.addEventListener('click', e => {
        const link = e.target.closest('.cat-tab, .nav-dropdown-item, .page-btn');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('javascript')) return;

        // Strip the #products hash for the fetch URL
        const fetchUrl = href.replace(/#.*$/, '');
        const urlObj   = new URL(fetchUrl, window.location.origin);
        const catParam = urlObj.searchParams.get('cat') || '';
        const pageParam = urlObj.searchParams.get('page') || '1';

        // Close nav dropdown if open
        document.querySelector('.nav-products')?.classList.remove('open');

        e.preventDefault();
        e.stopPropagation();
        loadProducts(fetchUrl, { cat: catParam, page: pageParam });
    }, true);

    // Handle browser back/forward
    window.addEventListener('popstate', e => {
        const catParam = e.state?.cat ?? new URLSearchParams(window.location.search).get('cat') ?? '';
        const pageParam = e.state?.page ?? new URLSearchParams(window.location.search).get('page') ?? '1';
        loadProducts(window.location.href.replace(/#.*$/, ''), { cat: catParam, page: pageParam });
    });

    // Handle search form submission with AJAX
    document.addEventListener('submit', e => {
        const form = e.target.closest('.hero-search');
        if (!form) return;

        e.preventDefault();
        const formData = new FormData(form);
        const searchParams = new URLSearchParams(formData);
        const searchUrl = form.action + '?' + searchParams.toString();

        // Get search query for state
        const query = formData.get('q') || '';
        
        loadProducts(searchUrl, { query: query });
    });
})();

// ── Init ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initPriceSlider();
    initStarFilter();
    initBrandFilter();
    initDeliveryFilter();
    initCatFilter();
    initSort();
    initThumbs();
    initQtyControl();
    initPasswordToggle();
    initImagePreview();
    initHeroCarousel();
    document.querySelectorAll('[onclick*="addToCart"]').forEach(btn => {
        btn.dataset.originalHtml = btn.innerHTML;
    });
});

// ── Mobile Drawer ───────────────────────────────────────────
function toggleMobileDrawer() {
    const drawer  = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileOverlay');
    const burger  = document.getElementById('navHamburger');
    if (!drawer) return;
    const isOpen = drawer.classList.toggle('open');
    overlay && overlay.classList.toggle('open', isOpen);
    burger  && burger.classList.toggle('open', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
}

// ── Cart Preview Panel ──────────────────────────────────────
function openCartPreview() {
    const panel   = document.getElementById('cartPreview');
    const overlay = document.getElementById('cartPreviewOverlay');
    if (!panel) return;
    panel.classList.add('open');
    overlay && overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    loadCartPreview();
}

function closeCartPreview() {
    const panel   = document.getElementById('cartPreview');
    const overlay = document.getElementById('cartPreviewOverlay');
    panel  && panel.classList.remove('open');
    overlay && overlay.classList.remove('open');
    document.body.style.overflow = '';
}

function loadCartPreview() {
    fetch(BASE + '/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get' })
    })
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById('cartPreviewItems');
        const footer    = document.getElementById('cartPreviewFooter');
        const totalEl   = document.getElementById('cartPreviewTotal');
        if (!container) return;

        if (!data.items || data.items.length === 0) {
            container.innerHTML = '<div class="cart-preview-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40" style="margin:0 auto 12px;display:block;color:var(--text-light)"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>Your cart is empty</div>';
            footer && (footer.style.display = 'none');
            return;
        }

        let html = '';
        let total = 0;
        data.items.forEach(item => {
            const itemTotal = parseFloat(item.price) * parseInt(item.quantity);
            total += itemTotal;
            const xaf = Math.round(itemTotal * 655).toLocaleString('fr-FR');
            html += `<div class="cart-preview-item">
                <img src="${item.image || ''}" alt="${item.name}" onerror="this.src='https://via.placeholder.com/52?text=?'">
                <div class="cart-preview-item-info">
                    <div class="cart-preview-item-name">${item.name}</div>
                    <div class="cart-preview-item-price">${xaf}\u00A0FCFA &times; ${item.quantity}</div>
                </div>
            </div>`;
        });
        container.innerHTML = html;
        if (footer) footer.style.display = '';
        if (totalEl) totalEl.textContent = Math.round(total * 655).toLocaleString('fr-FR') + '\u00A0FCFA';
    })
    .catch(() => {});
}


// ── Filter Drawer ────────────────────────────────────────────
function openFilterDrawer() {
    const drawer  = document.getElementById('filterDrawer');
    const overlay = document.getElementById('fdOverlay');
    if (!drawer) return;
    drawer.classList.add('open');
    overlay && overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeFilterDrawer() {
    const drawer  = document.getElementById('filterDrawer');
    const overlay = document.getElementById('fdOverlay');
    drawer  && drawer.classList.remove('open');
    overlay && overlay.classList.remove('open');
    document.body.style.overflow = '';
}

// Filter the brand list inside the drawer by search term
function filterBrandList(value) {
    const term = value.trim().toLowerCase();
    document.querySelectorAll('.fd-brand-item').forEach(item => {
        const label = item.querySelector('.fd-brand-label');
        const text  = (label ? label.textContent : item.textContent).toLowerCase();
        item.classList.toggle('hidden', term.length > 0 && !text.includes(term));
    });
}

// Toggle brand checkbox selection state in drawer
document.addEventListener('click', function(e) {
    const item = e.target.closest('.fd-brand-item');
    if (!item) return;
    item.classList.toggle('checked');
    // sync underlying checkbox input if present
    const cb = item.querySelector('input[type="checkbox"]');
    if (cb) cb.checked = item.classList.contains('checked');
    updateFdBadge();
});

// Delivery buttons inside drawer
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.fd-delivery-btn');
    if (!btn) return;
    const wasActive = btn.classList.contains('active');
    document.querySelectorAll('.fd-delivery-btn').forEach(b => b.classList.remove('active'));
    if (!wasActive) btn.classList.add('active');
    updateFdBadge();
});

// Star rating options inside drawer
document.addEventListener('click', function(e) {
    const opt = e.target.closest('.fd-star-option');
    if (!opt) return;
    const wasActive = opt.classList.contains('active');
    document.querySelectorAll('.fd-star-option').forEach(o => o.classList.remove('active'));
    if (!wasActive) opt.classList.add('active');
    updateFdBadge();
});

// Keep fd-btn-badge count updated
function updateFdBadge() {
    let count = 0;
    if (document.querySelector('.fd-star-option.active'))  count++;
    if (document.querySelector('.fd-delivery-btn.active')) count++;
    if (document.querySelector('.fd-brand-item.checked'))  count++;
    // price range: check if changed from defaults
    const minEl = document.getElementById('minPrice');
    const maxEl = document.getElementById('maxPrice');
    if (minEl && maxEl) {
        const minDef = parseFloat(minEl.min || 0);
        const maxDef = parseFloat(maxEl.max || 999999);
        if (parseFloat(minEl.value) > minDef || parseFloat(maxEl.value) < maxDef) count++;
    }
    document.querySelectorAll('.fd-btn-badge').forEach(b => {
        b.textContent = count > 0 ? count : '';
        b.dataset.count = count;
    });
}

// Sync existing initDeliveryFilter to also handle .fd-delivery-btn
(function patchDeliveryFilter() {
    // Wait for DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        // Already handled by delegated click above; keep existing .delivery-btn logic if present
        const legacyBtns = document.querySelectorAll('.delivery-btn:not(.fd-delivery-btn)');
        legacyBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                legacyBtns.forEach(b => b.classList.remove('active'));
                this.classList.toggle('active');
            });
        });
    });
})();
