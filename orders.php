<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'My Orders — Venmark';
$B = BASE_URL;

requireLogin();

$pdo    = getDB();
$userId = $_SESSION['user_id'];

// Migrate: add payment columns to orders if not present
try {
    $pdo->exec("ALTER TABLE orders
        ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid','pending','paid','failed') NOT NULL DEFAULT 'unpaid',
        ADD COLUMN IF NOT EXISTS payment_ref    VARCHAR(120) NULL,
        ADD COLUMN IF NOT EXISTS payer_phone    VARCHAR(30)  NULL");
} catch (PDOException $e) { /* already exist */ }

// Fetch orders with items
$ordersStmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.created_at DESC");
$ordersStmt->execute([$userId]);
$orders = $ordersStmt->fetchAll();

$statusColors = [
    'pending'    => 'status-pending',
    'processing' => 'status-processing',
    'shipped'    => 'status-shipped',
    'delivered'  => 'status-delivered',
    'cancelled'  => 'status-cancelled',
];
$statusIcons = [
    'pending'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="vertical-align:middle;margin-right:3px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'processing' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="vertical-align:middle;margin-right:3px"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>',
    'shipped'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="vertical-align:middle;margin-right:3px"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
    'delivered'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="vertical-align:middle;margin-right:3px"><polyline points="20 6 9 17 4 12"/></svg>',
    'cancelled'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="vertical-align:middle;margin-right:3px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
];

include __DIR__ . '/includes/header.php';
?>

<div class="orders-layout">
    <div class="orders-header">
        <div>
            <h1>My Orders</h1>
            <p style="color:var(--text-mid);margin-top:4px;">Track and manage all your orders</p>
        </div>
        <a href="<?= $B ?>/index.php" class="btn btn-primary">Continue Shopping</a>
    </div>

    <?php if (isset($_GET['placed'])): ?>
        <div class="alert alert-success" style="border-radius:var(--radius);display:flex;align-items:center;gap:10px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
            <div>
                <strong>Order #<?= str_pad((int)$_GET['placed'],6,'0',STR_PAD_LEFT) ?> placed &amp; payment confirmed!</strong><br>
                <span style="font-size:0.82rem;opacity:0.85;">Thank you for your purchase. Your order is now being processed.</span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48"><path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div>
            <h3>No orders yet</h3>
            <p>Your order history will appear here once you make your first purchase.</p>
            <a href="<?= $B ?>/index.php" class="btn btn-primary btn-lg">Start Shopping</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order):
            $itemsStmt = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
            $itemsStmt->execute([$order['id']]);
            $orderItems = $itemsStmt->fetchAll();
        ?>
        <div class="order-card">
            <div class="order-card-header">
                <div>
                    <div style="font-weight:700;font-size:1rem;">Order #<?= str_pad($order['id'],6,'0',STR_PAD_LEFT) ?></div>
                    <div class="order-date" style="margin-top:4px;display:flex;align-items:center;gap:5px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><?= date('M d, Y \a\t h:i A', strtotime($order['created_at'])) ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <?php
                    $payStatus  = $order['payment_status'] ?? 'unpaid';
                    $payBadge   = match($payStatus) {
                        'paid'    => ['label' => '✓ Paid',        'bg' => '#D1FAE5', 'color' => '#065F46'],
                        'pending' => ['label' => '⏳ Pay Pending', 'bg' => '#FEF3C7', 'color' => '#92400E'],
                        'failed'  => ['label' => '✗ Pay Failed',  'bg' => '#FEE2E2', 'color' => '#991B1B'],
                        default   => ['label' => '💳 Unpaid',     'bg' => '#F3F4F6', 'color' => '#374151'],
                    };
                    ?>
                    <span style="font-size:0.72rem;font-weight:700;padding:3px 9px;border-radius:20px;background:<?= $payBadge['bg'] ?>;color:<?= $payBadge['color'] ?>;"><?= $payBadge['label'] ?></span>
                    <span class="order-status <?= $statusColors[$order['status']] ?>">
                        <?= $statusIcons[$order['status']] ?> <?= ucfirst($order['status']) ?>
                    </span>
                    <span style="font-size:0.85rem;color:var(--text-mid);">
                        <?php if ($order['delivery_type'] === 'pickup'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="vertical-align:middle;margin-right:3px"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Pick Up
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="vertical-align:middle;margin-right:3px"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>Standard
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <div class="order-items-list">
                <?php foreach ($orderItems as $oi): ?>
                <div class="order-item">
                    <img src="<?= htmlspecialchars($oi['image']) ?>" alt="<?= htmlspecialchars($oi['name']) ?>" class="order-item-img">
                    <div>
                        <div class="order-item-name"><?= htmlspecialchars($oi['name']) ?></div>
                        <div class="order-item-qty">Qty: <?= $oi['quantity'] ?> × <?= fcfa($oi['price']) ?></div>
                    </div>
                    <div style="margin-left:auto;font-weight:700;color:var(--primary);"><?= fcfa($oi['quantity']*$oi['price']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="order-card-footer">
                <div>
                    <span class="order-id"><?= $order['item_count'] ?> item<?= $order['item_count']!==1?'s':'' ?></span>
                    <?php if ($order['address']): ?>
                        <div style="font-size:0.82rem;color:var(--text-mid);margin-top:4px;display:flex;align-items:center;gap:4px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><?= htmlspecialchars(substr($order['address'],0,60)) ?>...</div>
                    <?php endif; ?>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:0.8rem;color:var(--text-mid);">Total</div>
                    <div class="order-total"><?= fcfa($order['total']) ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
