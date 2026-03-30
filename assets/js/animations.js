/* ═══════════════════════════════════════════════════════════
   Venmark — Animations & Micro-interactions v2
   Runs AFTER main.js via defer
═══════════════════════════════════════════════════════════ */

'use strict';

// ── FCFA Currency Formatter ────────────────────────────────
// 1 USD ≈ 655 XAF (Central African CFA Franc)
window.MLC_RATE = 655;
window.formatFCFA = function (usdAmount) {
    const xaf = Math.round(parseFloat(usdAmount) * window.MLC_RATE);
    return xaf.toLocaleString('fr-FR') + ' FCFA';
};

// ── Page Fade-in / Fade-out on navigation ─────────────────
document.documentElement.style.cssText += ';opacity:0;transition:opacity .3s ease';
window.addEventListener('load', () => {
    document.documentElement.style.opacity = '1';

    // After fade-in completes, scroll to hash anchor if present
    // (browser scroll happens before layout is ready so we redo it)
    const hash = window.location.hash;
    if (hash && hash !== '#') {
        setTimeout(() => {
            const target = document.querySelector(hash);
            if (target) {
                const navH = document.querySelector('.navbar')?.offsetHeight || 70;
                const catH = document.querySelector('.category-bar')?.offsetHeight || 0;
                const offset = navH + catH + 12;
                const top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
            }
        }, 320); // wait for fade-in to finish (300ms) + small buffer
    }
});

document.addEventListener('click', e => {
    const a = e.target.closest('a[href]');
    if (!a || e.ctrlKey || e.metaKey || e.shiftKey || a.target === '_blank') return;
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript') || href.startsWith('mailto')) return;
    e.preventDefault();
    document.documentElement.style.opacity = '0';
    setTimeout(() => { window.location.href = href; }, 280);
}, true);

// ── Scroll Progress Bar ────────────────────────────────────
const progressBar = Object.assign(document.createElement('div'), { className: 'scroll-progress' });
document.body.prepend(progressBar);
window.addEventListener('scroll', () => {
    const pct = window.scrollY / (document.documentElement.scrollHeight - window.innerHeight) * 100;
    progressBar.style.width = Math.min(100, pct) + '%';
}, { passive: true });

// ── Cursor System (glow + dot + ring) ─────────────────────
const cursorGlow = Object.assign(document.createElement('div'), { className: 'cursor-glow' });
const cursorDot  = Object.assign(document.createElement('div'), { className: 'cursor-dot' });
const cursorRing = Object.assign(document.createElement('div'), { className: 'cursor-ring' });
document.body.append(cursorGlow, cursorDot, cursorRing);

let mx = -300, my = -300;
let gx = -300, gy = -300;
let rx = -300, ry = -300;

document.addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; }, { passive: true });
document.addEventListener('mousedown', () => document.body.classList.add('cursor-clicking'));
document.addEventListener('mouseup',   () => document.body.classList.remove('cursor-clicking'));

// Hoverable elements trigger ring expansion
const hoverables = 'a, button, [role="button"], input, select, textarea, label, .product-card, .cat-tab, .nav-dropdown-item, .fd-cat-item';
document.addEventListener('mouseover', e => {
    if (e.target.closest(hoverables)) document.body.classList.add('cursor-hover');
}, { passive: true });
document.addEventListener('mouseout', e => {
    if (e.target.closest(hoverables)) document.body.classList.remove('cursor-hover');
}, { passive: true });

(function cursorLoop() {
    // Dot follows instantly
    cursorDot.style.left = mx + 'px';
    cursorDot.style.top  = my + 'px';
    // Glow trails slowly
    gx += (mx - gx) * 0.1;
    gy += (my - gy) * 0.1;
    cursorGlow.style.transform = `translate(${gx}px, ${gy}px) translate(-50%, -50%)`;
    // Ring trails medium speed
    rx += (mx - rx) * 0.18;
    ry += (my - ry) * 0.18;
    cursorRing.style.left = rx + 'px';
    cursorRing.style.top  = ry + 'px';
    requestAnimationFrame(cursorLoop);
})();

// ── Scroll Reveal (Intersection Observer) ─────────────────
function makeRevealObserver() {
    return new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                revealIO.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -48px 0px' });
}
const revealIO = makeRevealObserver();
function observeReveal() {
    document.querySelectorAll('.reveal-up:not(.revealed), .reveal-left:not(.revealed), .reveal-fade:not(.revealed)')
        .forEach(el => revealIO.observe(el));
}
observeReveal();

// ── Stagger-delay helper ───────────────────────────────────
function applyStagger(parent, delay = 70) {
    [...parent.children].forEach((child, i) => {
        child.style.setProperty('--sd', `${i * delay}ms`);
    });
}
document.querySelectorAll('.stagger-children').forEach(p => applyStagger(p));

// ── Ripple on click ────────────────────────────────────────
document.addEventListener('click', e => {
    const el = e.target.closest('.btn, .price-btn, .cat-tab, .delivery-btn, .page-btn, .nav-btn');
    if (!el) return;
    const r = el.getBoundingClientRect();
    const size = Math.max(r.width, r.height) * 2.2;
    const rip = Object.assign(document.createElement('span'), { className: 'ripple-fx' });
    Object.assign(rip.style, {
        width: size + 'px', height: size + 'px',
        left: (e.clientX - r.left - size / 2) + 'px',
        top:  (e.clientY - r.top  - size / 2) + 'px',
    });
    el.appendChild(rip);
    rip.addEventListener('animationend', () => rip.remove());
}, { passive: true });

// ── Cart Icon Pop ──────────────────────────────────────────
const _origUpdateBadge = window.updateCartBadge;
window.updateCartBadge = function (count) {
    _origUpdateBadge && _origUpdateBadge(count);
    const btn = document.querySelector('.cart-btn');
    if (!btn) return;
    btn.classList.remove('cart-pop');
    void btn.offsetWidth; // force reflow
    btn.classList.add('cart-pop');
    btn.addEventListener('animationend', () => btn.classList.remove('cart-pop'), { once: true });
};

// ── Fav Heart Burst ────────────────────────────────────────
document.addEventListener('click', e => {
    const btn = e.target.closest('.fav-btn');
    if (!btn || btn.classList.contains('active')) return;
    const colors = ['#EF4444', '#F97316', '#EC4899', '#7C3AED'];
    for (let i = 0; i < 8; i++) {
        const p = Object.assign(document.createElement('span'), { className: 'heart-p', textContent: '♥' });
        const angle = (i / 8) * 360 + Math.random() * 30;
        const dist  = 22 + Math.random() * 20;
        p.style.cssText = `--ang:${angle}deg;--dist:${dist}px;color:${colors[i % colors.length]}`;
        btn.appendChild(p);
        p.addEventListener('animationend', () => p.remove());
    }
});

// ── 3-D Product Card Tilt ──────────────────────────────────
function initCardTilt() {
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('mousemove', e => {
            const r = card.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width  - 0.5;
            const y = (e.clientY - r.top)  / r.height - 0.5;
            card.style.transform = `perspective(800px) rotateX(${-y * 8}deg) rotateY(${x * 8}deg) translateY(-6px) scale(1.015)`;
            card.style.boxShadow = `${-x * 14}px ${-y * 14}px 36px rgba(124,58,237,0.18)`;
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
            card.style.boxShadow = '';
        });
    });
}
initCardTilt();

// Magnetic button effect removed — caused unwanted movement

// ── Hero Parallax ──────────────────────────────────────────
const heroMain = document.querySelector('.hero-main');
const heroDeco = document.querySelector('.hero-deco-svg');
if (heroMain) {
    let lastScrollY = 0, ticking = false;
    window.addEventListener('scroll', () => {
        lastScrollY = window.scrollY;
        if (!ticking) {
            requestAnimationFrame(() => {
                if (heroDeco) {
                    heroDeco.style.transform = `translateY(${lastScrollY * 0.12}px) rotate(${lastScrollY * 0.015}deg)`;
                }
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });
}

// ── Number Counter ─────────────────────────────────────────
const counterIO = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const end      = parseFloat(el.dataset.count);
        const isFloat  = el.dataset.count.includes('.');
        const prefix   = el.dataset.prefix || '';
        const suffix   = el.dataset.suffix || '';
        const duration = 1400;
        const startTs  = performance.now();
        (function tick(now) {
            const t   = Math.min((now - startTs) / duration, 1);
            const ease = 1 - Math.pow(1 - t, 3);
            const val  = end * ease;
            el.textContent = prefix + (isFloat ? val.toFixed(1) : Math.floor(val).toLocaleString('fr-FR')) + suffix;
            if (t < 1) requestAnimationFrame(tick);
        })(startTs);
        counterIO.unobserve(el);
    });
});
document.querySelectorAll('[data-count]').forEach(el => counterIO.observe(el));

// ── Toast enter animation hookup ───────────────────────────
const toastContainer = document.querySelector('.toast-container');
if (toastContainer) {
    new MutationObserver(muts => {
        muts.forEach(m => m.addedNodes.forEach(node => {
            if (node.nodeType === 1 && node.classList.contains('toast')) {
                node.classList.add('toast-enter');
            }
        }));
    }).observe(toastContainer, { childList: true });
}

// ── Sidebar filter cards: hover indicator ─────────────────
document.querySelectorAll('.filter-card').forEach((card, i) => {
    card.style.setProperty('--fc-delay', `${i * 80}ms`);
});

// ── Price pill animate on slider change ───────────────────
['minPriceDisplay', 'maxPriceDisplay'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    const mo = new MutationObserver(() => {
        el.classList.remove('pill-pop');
        void el.offsetWidth;
        el.classList.add('pill-pop');
    });
    mo.observe(el, { childList: true, characterData: true, subtree: true });
});

// ── Smooth empty-cart transition ──────────────────────────
// Patch checkEmptyCart from main.js so the transition is animated
const _origCheck = window.checkEmptyCart;
window.checkEmptyCart = function () {
    const items = document.querySelectorAll('.cart-item');
    if (items.length === 0) {
        const list = document.querySelector('.cart-items');
        if (list) {
            list.style.transition = 'opacity .4s ease';
            list.style.opacity = '0';
            setTimeout(() => {
                list.innerHTML = `<div class="empty-state reveal-up">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48">
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                    </div>
                    <h3>Your cart is empty</h3>
                    <p>Discover amazing products and add them to your cart.</p>
                    <a href="${window.MLC_BASE}/index.php" class="btn btn-primary btn-lg">Start Shopping</a>
                </div>`;
                list.style.opacity = '1';
                observeReveal();
            }, 400);
        }
    }
};

// ── Login card tilt ────────────────────────────────────────
const loginCard = document.querySelector('.card-glass');
if (loginCard) {
    const loginRight = loginCard.closest('.login-right');
    const tiltEl = loginRight || loginCard;
    tiltEl.addEventListener('mousemove', e => {
        const r = loginCard.getBoundingClientRect();
        const x = (e.clientX - r.left - r.width  / 2) / r.width;
        const y = (e.clientY - r.top  - r.height / 2) / r.height;
        loginCard.style.transform = `perspective(900px) rotateX(${-y * 6}deg) rotateY(${x * 6}deg) translateY(-4px)`;
        loginCard.style.boxShadow = `${-x * 18}px ${-y * 12}px 48px rgba(124,58,237,.18), inset 0 1px 0 rgba(255,255,255,.9)`;
    });
    tiltEl.addEventListener('mouseleave', () => {
        loginCard.style.transform = '';
        loginCard.style.boxShadow = '';
    });
}

// ── Product image spotlight on mousemove ──────────────────
document.addEventListener('mousemove', e => {
    const wrap = e.target.closest('.product-img-wrap');
    if (!wrap) return;
    const r  = wrap.getBoundingClientRect();
    const px = ((e.clientX - r.left) / r.width  * 100).toFixed(1);
    const py = ((e.clientY - r.top)  / r.height * 100).toFixed(1);
    wrap.style.setProperty('--mx', px + '%');
    wrap.style.setProperty('--my', py + '%');
}, { passive: true });

// ── Magnetic pull on primary buttons ─────────────────────
document.querySelectorAll('.btn-primary, .btn-login, .checkout-btn, .fd-apply-btn').forEach(btn => {
    btn.addEventListener('mousemove', e => {
        const r  = btn.getBoundingClientRect();
        const dx = (e.clientX - (r.left + r.width  / 2)) * 0.22;
        const dy = (e.clientY - (r.top  + r.height / 2)) * 0.22;
        btn.style.transform = `translate(${dx}px, ${dy}px) translateY(-3px) scale(1.02)`;
    });
    btn.addEventListener('mouseleave', () => { btn.style.transform = ''; });
});

// ── Nav dropdown icon bounce on item hover ────────────────
document.querySelectorAll('.nav-dropdown-item').forEach(item => {
    const icon = item.querySelector('.nav-dropdown-icon svg');
    if (!icon) return;
    item.addEventListener('mouseenter', () => {
        icon.style.transition = 'transform .3s cubic-bezier(.34,1.56,.64,1)';
        icon.style.transform  = 'scale(1.3) rotate(-12deg)';
    });
    item.addEventListener('mouseleave', () => { icon.style.transform = ''; });
});

// ── Trust item icon spin on hover ─────────────────────────
document.querySelectorAll('.trust-item').forEach(item => {
    const icon = item.querySelector('.trust-icon, svg');
    if (!icon) return;
    item.addEventListener('mouseenter', () => {
        icon.style.transition = 'transform .4s cubic-bezier(.34,1.56,.64,1)';
        icon.style.transform  = 'rotateY(180deg) scale(1.1)';
    });
    item.addEventListener('mouseleave', () => { icon.style.transform = ''; });
});

// ── Smart bar search input focus glow ────────────────────
const sbarInput = document.querySelector('.sbar-search input');
if (sbarInput) {
    const form = sbarInput.closest('.sbar-search');
    sbarInput.addEventListener('focus', () => {
        if (form) form.style.boxShadow = '0 0 0 3px rgba(124,58,237,.18), 0 4px 16px rgba(124,58,237,.12)';
    });
    sbarInput.addEventListener('blur', () => {
        if (form) form.style.boxShadow = '';
    });
}

// ── Stat cards number shimmer on hover ────────────────────
document.querySelectorAll('.stat-card').forEach(card => {
    const num = card.querySelector('.stat-value, [data-count], h3, .stat-number');
    if (!num) return;
    card.addEventListener('mouseenter', () => {
        num.style.transition = 'transform .3s cubic-bezier(.34,1.56,.64,1)';
        num.style.transform  = 'scale(1.08)';
    });
    card.addEventListener('mouseleave', () => { num.style.transform = ''; });
});

// ── Re-init card tilt after dynamic content ───────────────
const gridObserver = new MutationObserver(() => initCardTilt());
const grid = document.querySelector('.products-grid');
if (grid) gridObserver.observe(grid, { childList: true });
