<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Shopping Cart — Venmark';
$B = BASE_URL;

requireLogin();

$pdo    = getDB();
$userId = $_SESSION['user_id'];

// Route delivery info to checkout.php (payment gateway)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed'])) {
    $_SESSION['co_delivery'] = $_POST['delivery_type'] ?? 'standard';
    $_SESSION['co_address']  = trim($_POST['address']  ?? '');
    header('Location: ' . $B . '/checkout.php');
    exit;
}

// Get cart items
$stmt = $pdo->prepare("SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.original_price, p.image, p.brand, p.stock, v.store_name FROM cart c JOIN products p ON p.id = c.product_id LEFT JOIN vendors v ON v.id = p.vendor_id WHERE c.user_id = ? ORDER BY c.id DESC");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$subtotal  = array_sum(array_map(fn($i) => $i['quantity'] * $i['price'], $cartItems));
$shipping  = $subtotal > 0 ? 5.99 : 0;
$total     = $subtotal + $shipping;

include __DIR__ . '/includes/header.php';
?>

<div style="max-width:1100px;margin:28px auto 0;padding:0 24px;">
    <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:24px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1.4rem;height:1.4rem;vertical-align:middle;margin-right:8px;"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>Shopping Cart <span style="font-size:1rem;font-weight:400;color:var(--text-mid);">(<?= count($cartItems) ?> items)</span>
    </h1>
</div>

<div class="cart-layout">
    <!-- Cart Items -->
    <div>
        <?php if (empty($cartItems)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                </div>
                <h3>Your cart is empty</h3>
                <p>Discover amazing products and add them to your cart.</p>
                <a href="<?= $B ?>/index.php" class="btn btn-primary btn-lg">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-items" id="cartItemsList">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item" id="cartItem<?= $item['product_id'] ?>">
                    <a href="<?= $B ?>/product-details.php?id=<?= $item['product_id'] ?>">
                        <img src="<?= htmlspecialchars(getImageUrl($item['image'])) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-img">
                    </a>
                    <div class="cart-item-info">
                        <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="cart-item-brand"><?= htmlspecialchars($item['brand'] ?? $item['store_name']) ?></div>
                        <div class="cart-item-price" id="itemPrice<?= $item['product_id'] ?>"><?= fcfa($item['quantity']*$item['price']) ?></div>
                    </div>
                    <div class="cart-item-actions">
                        <div class="qty-control">
                            <button class="qty-btn" onclick="changeQty(<?= $item['product_id'] ?>, <?= $item['quantity']-1 ?>)">−</button>
                            <input type="number" class="qty-input" id="qty<?= $item['product_id'] ?>" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" onchange="changeQty(<?= $item['product_id'] ?>, this.value)">
                            <button class="qty-btn" onclick="changeQty(<?= $item['product_id'] ?>, <?= $item['quantity']+1 ?>)">+</button>
                        </div>
                        <button class="remove-btn" onclick="removeItem(<?= $item['product_id'] ?>, document.getElementById('cartItem<?= $item['product_id'] ?>'))">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Summary -->
    <?php if (!empty($cartItems)): ?>
    <div>
        <div class="cart-summary">
            <h3>Order Summary</h3>

            <div class="promo-input">
                <input type="text" placeholder="Promo code">
                <button class="btn btn-outline">Apply</button>
            </div>

            <div class="summary-row"><span>Subtotal</span><span><?= fcfa($subtotal) ?></span></div>
            <div class="summary-row"><span>Shipping</span><span><?= $shipping > 0 ? fcfa($shipping) : 'Free' ?></span></div>
            <div class="summary-row"><span>Tax (5%)</span><span><?= fcfa($subtotal*0.05) ?></span></div>
            <div class="summary-row" style="padding-top:12px;"><span style="font-weight:700;">Total</span><span class="summary-total order-total-val"><?= fcfa($total + $subtotal*0.05) ?></span></div>

            <form method="POST" style="margin-top:20px;">
                <div class="form-group">
                    <label style="font-size:0.85rem;font-weight:600;">Delivery Method</label>
                    <div style="display:flex;gap:8px;margin-top:8px;">
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="delivery_type" value="standard" checked style="display:none">
                            <div class="delivery-btn active" onclick="this.classList.add('active');document.querySelectorAll('.delivery-btn').forEach(b=>b!=this&&b.classList.remove('active'))">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                Standard
                            </div>
                        </label>
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="delivery_type" value="pickup" style="display:none">
                            <div class="delivery-btn" onclick="this.classList.add('active');document.querySelectorAll('.delivery-btn').forEach(b=>b!=this&&b.classList.remove('active'))">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                Pick Up
                            </div>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label style="font-size:0.85rem;font-weight:600;">Delivery Address</label>
                    <textarea name="address" placeholder="Enter your delivery address..." style="min-height:70px;margin-top:8px;"></textarea>
                </div>
                <button type="submit" name="proceed" class="btn btn-primary" style="width:100%;justify-content:center;padding:14px;font-size:1rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15" style="vertical-align:middle;margin-right:6px"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Proceed to Payment — <?= fcfa($total + $subtotal*0.05) ?>
                </button>
                <!-- Payment method icons -->
                <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:10px;flex-wrap:wrap;">
                    <span style="background:#FFCC00;color:#1A1A1A;font-weight:800;font-size:0.65rem;padding:3px 8px;border-radius:5px;">MTN MoMo</span>
                    <span style="background:#FF6600;color:white;font-weight:800;font-size:0.65rem;padding:3px 8px;border-radius:5px;">Orange Money</span>
                    <span style="font-size:0.65rem;color:var(--text-light);">via Campay</span>
                </div>
            </form>

            <div style="display:flex;justify-content:center;gap:16px;margin-top:16px;color:var(--text-light);font-size:0.8rem;">
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="vertical-align:middle;margin-right:3px"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Secure checkout</span>
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="vertical-align:middle;margin-right:3px"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>Easy returns</span>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function changeQty(productId, qty) {
    qty = parseInt(qty);
    if (qty < 1) { removeItem(productId, document.getElementById('cartItem'+productId)); return; }
    const input = document.getElementById('qty'+productId);
    if (input) input.value = qty;
    updateCartQty(productId, qty, document.getElementById('cartItem'+productId));
}
function removeItem(productId, row) {
    removeFromCart(productId, row);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
