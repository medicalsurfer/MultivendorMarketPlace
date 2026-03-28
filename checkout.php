<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Checkout — Venmark';
$B = BASE_URL;

requireLogin();
$pdo    = getDB();
$userId = $_SESSION['user_id'];

// Grab cart items
$stmt = $pdo->prepare("SELECT c.quantity, p.id AS product_id, p.name, p.price, p.original_price, p.image, v.store_name
    FROM cart c JOIN products p ON p.id = c.product_id LEFT JOIN vendors v ON v.id = p.vendor_id
    WHERE c.user_id = ? ORDER BY c.id ASC");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header('Location: ' . $B . '/cart.php');
    exit;
}

$subtotalUsd = array_sum(array_map(fn($i) => $i['quantity'] * $i['price'], $cartItems));
$shippingUsd = 5.99;
$taxUsd      = round($subtotalUsd * 0.05, 2);
$totalUsd    = $subtotalUsd + $shippingUsd + $taxUsd;
$totalXaf    = (int)round($totalUsd * 655);

// Delivery info pre-filled from POST (from cart.php) or session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['co_delivery'] = $_POST['delivery_type'] ?? 'standard';
    $_SESSION['co_address']  = $_POST['address']       ?? '';
}
$deliveryType = $_SESSION['co_delivery'] ?? 'standard';
$address      = $_SESSION['co_address']  ?? '';

include __DIR__ . '/includes/header.php';
?>
<!DOCTYPE html><!-- checkout inline styles: page is included via header.php above -->
<style>
/* ── Checkout Page Layout ─────────────────────────────── */
.checkout-wrap {
    max-width: 1080px;
    margin: 36px auto 60px;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 28px;
    align-items: start;
}
@media (max-width: 820px) {
    .checkout-wrap { grid-template-columns: 1fr; }
    .checkout-summary { order: -1; }
}

/* ── Glass cards ──────────────────────────────────────── */
.co-card {
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(20px) saturate(160%);
    border: 1px solid rgba(124,58,237,0.12);
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 8px 40px rgba(124,58,237,0.08);
}
[data-theme="dark"] .co-card {
    background: rgba(28,24,54,0.9);
    border-color: rgba(124,58,237,0.25);
}

/* ── Section title ────────────────────────────────────── */
.co-section-title {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--primary);
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 7px;
}

/* ── Payment method tabs ──────────────────────────────── */
.pay-methods {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 22px;
}
.pay-method-btn {
    border: 2px solid var(--border);
    border-radius: 14px;
    padding: 14px 12px;
    background: var(--bg-white);
    cursor: pointer;
    text-align: center;
    transition: all 0.22s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text-mid);
    position: relative;
}
.pay-method-btn:hover { border-color: var(--primary); }
.pay-method-btn.selected {
    border-color: var(--primary);
    background: var(--primary-light);
    color: var(--primary);
}
.pay-method-btn .method-logo {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}
.method-mtn .method-logo { background: #FFCC00; color: #1A1A1A; }
.method-orange .method-logo { background: #FF6600; color: white; }
.pay-method-btn.selected::after {
    content: '✓';
    position: absolute;
    top: 8px; right: 10px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.65rem;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 18px;
}

/* ── Phone field ──────────────────────────────────────── */
.co-phone-wrap {
    position: relative;
    margin-bottom: 20px;
}
.co-phone-prefix {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-mid);
    pointer-events: none;
    display: flex;
    align-items: center;
    gap: 5px;
}
.co-phone-input {
    width: 100%;
    border: 2px solid var(--border);
    border-radius: 12px;
    padding: 13px 14px 13px 68px;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-dark);
    background: var(--bg-white);
    outline: none;
    transition: all 0.22s ease;
    box-sizing: border-box;
    letter-spacing: 1px;
}
.co-phone-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(124,58,237,0.12); }
.co-phone-hint { font-size: 0.73rem; color: var(--text-mid); margin-top: 6px; }

/* ── Secure badges ────────────────────────────────────── */
.co-secure-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    padding: 12px 0 0;
    font-size: 0.72rem;
    color: var(--text-light);
}
.co-secure-row span { display:flex; align-items:center; gap:4px; }

/* ── Pay button ───────────────────────────────────────── */
.co-pay-btn {
    width: 100%;
    padding: 15px;
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
    letter-spacing: 0.3px;
    margin-bottom: 14px;
}
.co-pay-btn:hover:not(:disabled) { opacity: 0.92; transform: translateY(-1px); box-shadow: 0 8px 28px rgba(124,58,237,0.35); }
.co-pay-btn:disabled { opacity: 0.65; cursor: not-allowed; transform: none; }
.co-pay-btn .btn-spinner {
    display: none;
    width: 18px; height: 18px;
    border: 2.5px solid rgba(255,255,255,0.35);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.75s linear infinite;
    position: absolute;
    left: 50%; top: 50%;
    transform: translate(-50%,-50%);
}
.co-pay-btn.loading .btn-text { visibility: hidden; }
.co-pay-btn.loading .btn-spinner { display: block; }
@keyframes spin { to { transform: translate(-50%,-50%) rotate(360deg); } }

/* ── Status panel ─────────────────────────────────────── */
.co-status-panel {
    display: none;
    border-radius: 16px;
    padding: 22px;
    text-align: center;
    margin-top: 16px;
    animation: fadeUp 0.35s ease;
}
.co-status-panel.show { display: block; }
.co-status-panel.pending {
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.3);
}
.co-status-panel.success {
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.3);
}
.co-status-panel.failed {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.3);
}
.co-status-panel .status-icon {
    width: 56px; height: 56px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 14px;
    font-size: 1.5rem;
}
.pending .status-icon { background: rgba(245,158,11,0.15); }
.success .status-icon { background: rgba(16,185,129,0.15); }
.failed  .status-icon { background: rgba(239,68,68,0.15); }
.co-status-panel h4 { font-size: 1rem; font-weight: 700; margin-bottom: 6px; }
.co-status-panel p  { font-size: 0.83rem; color: var(--text-mid); margin: 0; }

/* ── Pulse ring animation (waiting) ──────────────────── */
.pulse-ring {
    position: relative;
    width: 56px; height: 56px;
    margin: 0 auto 14px;
}
.pulse-ring::before, .pulse-ring::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(245,158,11,0.18);
    animation: pulse-out 1.8s ease-out infinite;
}
.pulse-ring::after { animation-delay: 0.9s; }
.pulse-ring-inner {
    position: absolute;
    inset: 8px;
    border-radius: 50%;
    background: rgba(245,158,11,0.25);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
}
@keyframes pulse-out {
    0%   { transform: scale(1);   opacity: 0.7; }
    100% { transform: scale(2.2); opacity: 0; }
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Order summary ────────────────────────────────────── */
.co-items-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
    max-height: 300px;
    overflow-y: auto;
    padding-right: 4px;
    scrollbar-width: thin;
    scrollbar-color: var(--border) transparent;
}
.co-item {
    display: flex;
    align-items: center;
    gap: 12px;
}
.co-item img {
    width: 52px; height: 52px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid var(--border);
    flex-shrink: 0;
}
.co-item-info { flex: 1; min-width: 0; }
.co-item-name { font-size: 0.82rem; font-weight: 600; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.co-item-qty  { font-size: 0.72rem; color: var(--text-mid); margin-top: 2px; }
.co-item-price { font-size: 0.82rem; font-weight: 700; color: var(--primary); flex-shrink: 0; }
.co-divider { border: none; border-top: 1px solid var(--border); margin: 14px 0; }
.co-total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: 0.83rem; color: var(--text-mid); }
.co-total-row.grand { font-size: 1rem; font-weight: 800; color: var(--text-dark); margin-top: 10px; padding-top: 12px; border-top: 2px solid var(--border); }
.co-total-row.grand .total-xaf { color: var(--primary); font-size: 1.1rem; }
.back-to-cart { display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; color: var(--text-mid); text-decoration: none; margin-bottom: 20px; transition: color 0.2s; }
.back-to-cart:hover { color: var(--primary); }
</style>

<div style="max-width:1080px;margin:32px auto 0;padding:0 20px;">
    <a href="<?= $B ?>/cart.php" class="back-to-cart">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><polyline points="15 18 9 12 15 6"/></svg>
        Back to cart
    </a>
    <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:24px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1.3rem;height:1.3rem;vertical-align:middle;margin-right:8px;color:var(--primary)"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Secure Checkout
    </h1>
</div>

<div class="checkout-wrap">

    <!-- ── LEFT: Payment form ─────────────────────────── -->
    <div>
        <div class="co-card">
            <div class="co-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Payment Method
            </div>

            <!-- Method buttons -->
            <div class="pay-methods">
                <button type="button" class="pay-method-btn method-mtn selected" id="btnMTN" onclick="selectMethod('mtn')">
                    <div class="method-logo">MTN</div>
                    MTN MoMo
                </button>
                <button type="button" class="pay-method-btn method-orange" id="btnOrange" onclick="selectMethod('orange')">
                    <div class="method-logo">OM</div>
                    Orange Money
                </button>
            </div>

            <!-- Phone number -->
            <div class="co-section-title" style="margin-top:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.59 3.47 2 2 0 0 1 3.56 1.29h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.81a16 16 0 0 0 6 6l.88-.88a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
                Mobile Number
            </div>

            <div class="co-phone-wrap">
                <span class="co-phone-prefix">
                    <span>🇨🇲</span> +237
                </span>
                <input type="tel" id="phoneInput" class="co-phone-input"
                    placeholder="6X XXX XX XX"
                    maxlength="9"
                    inputmode="numeric"
                    oninput="this.value=this.value.replace(/\D/g,'')">
            </div>
            <p class="co-phone-hint" id="phoneHint">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11" style="vertical-align:middle;margin-right:2px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Enter your MTN MoMo number. You will receive a USSD push to confirm.
            </p>

            <!-- Delivery info (read-only recap) -->
            <hr class="co-divider" style="margin-top:20px;">
            <div class="co-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Delivery Info
            </div>
            <div style="font-size:0.83rem;color:var(--text-mid);line-height:1.6;background:var(--bg);border-radius:10px;padding:12px 14px;">
                <strong style="color:var(--text-dark);"><?= $deliveryType === 'pickup' ? '🏠 Pick Up' : '🚚 Standard Delivery' ?></strong>
                <?php if ($address): ?>
                    <br><?= htmlspecialchars($address) ?>
                <?php endif; ?>
                <br><a href="<?= $B ?>/cart.php" style="font-size:0.72rem;color:var(--primary);text-decoration:none;">Change →</a>
            </div>

            <!-- Pay button -->
            <div style="margin-top:22px;">
                <button class="co-pay-btn" id="payBtn" onclick="initPayment()">
                    <span class="btn-text">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16" style="vertical-align:middle;margin-right:6px"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Pay <?= number_format($totalXaf, 0, ',', "\u{00A0}") ?>&nbsp;FCFA
                    </span>
                    <span class="btn-spinner"></span>
                </button>

                <!-- Status panel -->
                <div class="co-status-panel" id="statusPanel">
                    <div id="statusIcon"></div>
                    <h4 id="statusTitle"></h4>
                    <p id="statusMsg"></p>
                    <div id="statusAction" style="margin-top:14px;"></div>
                </div>

                <div class="co-secure-row">
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        SSL encrypted
                    </span>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        Verified by Campay
                    </span>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Safe &amp; secure
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── RIGHT: Order summary ────────────────────────── -->
    <div class="checkout-summary">
        <div class="co-card">
            <div class="co-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                Order Summary
            </div>

            <div class="co-items-list">
                <?php foreach ($cartItems as $item):
                    $lineUsd = $item['quantity'] * $item['price'];
                    $lineXaf = (int)round($lineUsd * 655);
                ?>
                <div class="co-item">
                    <img src="<?= htmlspecialchars($item['image'] ?? '') ?>"
                         alt="<?= htmlspecialchars($item['name']) ?>"
                         onerror="this.src='https://via.placeholder.com/52?text=?'">
                    <div class="co-item-info">
                        <div class="co-item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="co-item-qty">Qty: <?= $item['quantity'] ?> &times; <?= fcfa($item['price']) ?></div>
                    </div>
                    <div class="co-item-price"><?= fcfa($lineUsd) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <hr class="co-divider">
            <div class="co-total-row"><span>Subtotal</span><span><?= fcfa($subtotalUsd) ?></span></div>
            <div class="co-total-row"><span>Shipping</span><span><?= fcfa($shippingUsd) ?></span></div>
            <div class="co-total-row"><span>Tax (5%)</span><span><?= fcfa($taxUsd) ?></span></div>
            <div class="co-total-row grand">
                <span>Total</span>
                <span class="total-xaf"><?= number_format($totalXaf, 0, ',', "\u{00A0}") ?>&nbsp;FCFA</span>
            </div>

            <!-- Payment logos -->
            <div style="display:flex;align-items:center;gap:8px;margin-top:18px;justify-content:center;flex-wrap:wrap;">
                <div style="background:#FFCC00;color:#1A1A1A;font-weight:800;font-size:0.68rem;padding:5px 10px;border-radius:7px;letter-spacing:0.3px;">MTN MoMo</div>
                <div style="background:#FF6600;color:white;font-weight:800;font-size:0.68rem;padding:5px 10px;border-radius:7px;letter-spacing:0.3px;">Orange Money</div>
                <div style="background:rgba(124,58,237,0.1);color:var(--primary);font-weight:700;font-size:0.68rem;padding:5px 10px;border-radius:7px;border:1px solid rgba(124,58,237,0.2);">Powered by Campay</div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE     = '<?= $B ?>';
const DELIVERY = '<?= addslashes($deliveryType) ?>';
const ADDRESS  = '<?= addslashes($address) ?>';
let   selectedMethod = 'mtn';
let   pollTimer      = null;
let   pollCount      = 0;

function selectMethod(method) {
    selectedMethod = method;
    document.getElementById('btnMTN').classList.toggle('selected',    method === 'mtn');
    document.getElementById('btnOrange').classList.toggle('selected', method === 'orange');
    document.getElementById('phoneHint').textContent =
        method === 'mtn'
            ? 'ℹ️ Enter your MTN MoMo number. You will receive a USSD push to confirm.'
            : 'ℹ️ Enter your Orange Money number. You will receive a USSD push to confirm.';
}

function setStatus(state, title, msg, actionHtml = '') {
    const panel = document.getElementById('statusPanel');
    panel.className = 'co-status-panel show ' + state;

    const icons = {
        pending: `<div class="pulse-ring"><div class="pulse-ring-inner">📱</div></div>`,
        success: `<div class="status-icon"><svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5" width="28" height="28"><polyline points="20 6 9 17 4 12"/></svg></div>`,
        failed:  `<div class="status-icon"><svg viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" width="28" height="28"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div>`,
    };
    document.getElementById('statusIcon').innerHTML   = icons[state] || '';
    document.getElementById('statusTitle').textContent = title;
    document.getElementById('statusMsg').textContent   = msg;
    document.getElementById('statusAction').innerHTML  = actionHtml;
}

function pollStatus(reference, orderId) {
    pollCount++;
    if (pollCount > 40) {          // ~2 min timeout
        stopPoll();
        setStatus('failed', 'Payment timed out', 'No response received. Please try again or contact support.',
            `<button class="co-pay-btn" style="max-width:200px;margin:0 auto;display:block;" onclick="resetForm()">Try Again</button>`);
        return;
    }

    fetch(BASE + '/api/payment.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ action: 'status', reference, order_id: orderId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.payment_status === 'SUCCESSFUL') {
            stopPoll();
            setStatus('success', 'Payment Successful! 🎉',
                'Your payment has been confirmed. Redirecting to your orders…');
            setTimeout(() => {
                window.location.href = BASE + '/orders.php?placed=' + orderId;
            }, 2200);
        } else if (data.payment_status === 'FAILED') {
            stopPoll();
            setStatus('failed', 'Payment Failed', 'The transaction was declined or cancelled.',
                `<button class="co-pay-btn" style="max-width:200px;margin:0 auto;display:block;" onclick="resetForm()">Try Again</button>`);
            document.getElementById('payBtn').disabled = false;
            document.getElementById('payBtn').classList.remove('loading');
        }
        // else PENDING — keep polling
    })
    .catch(() => {}); // ignore network errors during polling
}

function stopPoll() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

function resetForm() {
    stopPoll();
    pollCount = 0;
    document.getElementById('statusPanel').className = 'co-status-panel';
    document.getElementById('payBtn').disabled = false;
    document.getElementById('payBtn').classList.remove('loading');
}

function initPayment() {
    const phone = document.getElementById('phoneInput').value.replace(/\D/g, '');
    if (phone.length < 9) {
        document.getElementById('phoneInput').focus();
        document.getElementById('phoneInput').style.borderColor = '#EF4444';
        setTimeout(() => document.getElementById('phoneInput').style.borderColor = '', 1800);
        return;
    }

    const btn = document.getElementById('payBtn');
    btn.disabled = true;
    btn.classList.add('loading');
    document.getElementById('statusPanel').className = 'co-status-panel';
    stopPoll();
    pollCount = 0;

    fetch(BASE + '/api/payment.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
            action:        'initiate',
            phone:         phone,
            delivery_type: DELIVERY,
            address:       ADDRESS,
        }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            btn.disabled = false;
            btn.classList.remove('loading');
            setStatus('failed', 'Error', data.error,
                `<button class="co-pay-btn" style="max-width:200px;margin:0 auto;display:block;" onclick="resetForm()">Try Again</button>`);
            return;
        }

        if (data.status === 'demo') {
            // Demo mode: fake success after 3 seconds
            setStatus('pending', 'Demo Mode', data.message + ' (Sandbox — no API key configured)');
            setTimeout(() => {
                fetch(BASE + '/api/payment.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ action: 'status', reference: data.reference, order_id: data.order_id }),
                })
                .then(r => r.json())
                .then(d => {
                    setStatus('success', 'Demo Payment Successful! 🎉',
                        'Order created (demo mode). Redirecting…');
                    setTimeout(() => {
                        window.location.href = BASE + '/orders.php?placed=' + data.order_id;
                    }, 2200);
                });
            }, 3000);
            return;
        }

        // Real payment — show pending and start polling
        setStatus('pending', 'Awaiting Payment',
            'A payment request has been sent to +237' + phone +
            '. Open your phone and approve the USSD prompt.');

        const ref     = data.reference;
        const orderId = data.order_id;
        pollTimer = setInterval(() => pollStatus(ref, orderId), 3000);
    })
    .catch(err => {
        btn.disabled = false;
        btn.classList.remove('loading');
        setStatus('failed', 'Network Error', 'Could not reach the payment server. Check your connection.',
            `<button class="co-pay-btn" style="max-width:200px;margin:0 auto;display:block;" onclick="resetForm()">Try Again</button>`);
    });
}

// Prevent leaving if payment is in progress
window.addEventListener('beforeunload', e => {
    if (pollTimer) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
