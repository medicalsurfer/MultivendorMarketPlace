<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Vendor Dashboard — Venmark';
$B = BASE_URL;

requireLogin();
if (!isVendor() && !isAdmin()) {
    header('Location: ' . $B . '/index.php');
    exit;
}

$pdo    = getDB();
$userId = $_SESSION['user_id'];

// Get vendor info
$vendor = $pdo->prepare("SELECT * FROM vendors WHERE user_id = ?");
$vendor->execute([$userId]);
$vendor = $vendor->fetch();

if (!$vendor && !isAdmin()) {
    header('Location: ' . $B . '/register.php?role=vendor');
    exit;
}

$vendorId = $vendor['id'] ?? null;

// Stats
$statsWhere = $vendorId ? "WHERE p.vendor_id = $vendorId" : '';
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products p $statsWhere")->fetchColumn();

$revenueSQL = $vendorId
    ? "SELECT COALESCE(SUM(oi.quantity * oi.price),0) FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE p.vendor_id = $vendorId"
    : "SELECT COALESCE(SUM(total),0) FROM orders";
$totalRevenue = (float)$pdo->query($revenueSQL)->fetchColumn();

$ordersSQL = $vendorId
    ? "SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE p.vendor_id = $vendorId"
    : "SELECT COUNT(*) FROM orders";
$totalOrders = (int)$pdo->query($ordersSQL)->fetchColumn();

$avgRatingSQL = $vendorId ? "SELECT ROUND(AVG(rating),1) FROM products WHERE vendor_id = $vendorId" : "SELECT ROUND(AVG(rating),1) FROM products";
$avgRating = $pdo->query($avgRatingSQL)->fetchColumn() ?: '—';

// Recent products
$productsSQL = $vendorId
    ? "SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.vendor_id = $vendorId ORDER BY p.created_at DESC LIMIT 8"
    : "SELECT p.*, c.name as cat_name, v.store_name FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN vendors v ON v.id = p.vendor_id ORDER BY p.created_at DESC LIMIT 8";
$products = $pdo->query($productsSQL)->fetchAll();

// Recent orders
$recentOrdersSQL = $vendorId
    ? "SELECT o.*, u.name as customer_name, COUNT(oi.id) as item_count FROM orders o JOIN order_items oi ON oi.order_id = o.id JOIN products p ON p.id = oi.product_id JOIN users u ON u.id = o.user_id WHERE p.vendor_id = $vendorId GROUP BY o.id ORDER BY o.created_at DESC LIMIT 5"
    : "SELECT o.*, u.name as customer_name, COUNT(oi.id) as item_count FROM orders o JOIN order_items oi ON oi.order_id = o.id JOIN users u ON u.id = o.user_id GROUP BY o.id ORDER BY o.created_at DESC LIMIT 5";
$recentOrders = $pdo->query($recentOrdersSQL)->fetchAll();

// Categories for add product
$categories = $pdo->query("SELECT * FROM categories WHERE slug != 'all' ORDER BY name")->fetchAll();

// Handle add product
$addError = '';
$addSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name         = trim($_POST['name'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $price        = (float)($_POST['price'] ?? 0);
    $origPrice    = $_POST['original_price'] ? (float)$_POST['original_price'] : null;
    $stock        = (int)($_POST['stock'] ?? 0);
    $categoryId   = (int)($_POST['category_id'] ?? 0);
    $brand        = trim($_POST['brand'] ?? '');
    $imageUrl     = trim($_POST['image_url'] ?? '');
    $isTop        = isset($_POST['is_top_item']) ? 1 : 0;
    $deliveryType = $_POST['delivery_type'] ?? 'both';

    if (!$name || !$price || !$categoryId) {
        $addError = 'Name, price and category are required.';
    } else {
        $pdo->prepare("INSERT INTO products (vendor_id, category_id, name, description, price, original_price, stock, image, brand, is_top_item, delivery_type) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$vendorId, $categoryId, $name, $description, $price, $origPrice, $stock, $imageUrl, $brand, $isTop, $deliveryType]);
        $addSuccess = 'Product added successfully!';
        header('Location: /vendor-dashboard/index.php?added=1');
        exit;
    }
}

// Handle delete product
if (isset($_GET['delete']) && $vendorId) {
    $deleteId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM products WHERE id = ? AND vendor_id = ?")->execute([$deleteId, $vendorId]);
    header('Location: /vendor-dashboard/index.php?deleted=1');
    exit;
}

// Handle order status update
if (isset($_POST['update_status'])) {
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $allowed   = ['pending','processing','shipped','delivered','cancelled'];
    if (in_array($newStatus, $allowed) && $orderId) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);
        header('Location: /vendor-dashboard/index.php#orders');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="dash-menu">
            <p class="dash-section-title">Main</p>
            <a href="#overview" class="dash-link active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Overview
            </a>
            <a href="#products" class="dash-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Products
            </a>
            <a href="#orders" class="dash-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                Orders
            </a>
            <a href="#add-product" class="dash-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Add Product
            </a>

            <p class="dash-section-title">Store</p>
            <a href="/index.php" class="dash-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Go to Store
            </a>
            <a href="/logout.php" class="dash-link text-danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-main">

        <!-- Header -->
        <div class="dash-header" id="overview">
            <h1>Welcome back, <?= htmlspecialchars($vendor['store_name'] ?? $_SESSION['user_name']) ?>!</h1>
            <p>Here's what's happening with your store today.</p>
        </div>

        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="15" height="15" style="vertical-align:middle;margin-right:6px"><polyline points="20 6 9 17 4 12"/></svg>Product added successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-error">Product deleted.</div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div>
                <div class="stat-value"><?= $totalProducts ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <div class="stat-value"><?= fcfa($totalRevenue) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></div>
                <div class="stat-value"><?= $totalOrders ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
                <div class="stat-value"><?= $avgRating ?>/5</div>
                <div class="stat-label">Avg Rating</div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="dash-card" id="products">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3>My Products</h3>
                <a href="#add-product" class="btn btn-primary btn-sm">+ Add Product</a>
            </div>
            <?php if (empty($products)): ?>
                <div class="empty-state" style="padding:32px;">
                    <div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div>
                    <h3>No products yet</h3>
                    <p>Add your first product to start selling!</p>
                </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Rating</th>
                            <th>Top Item</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <img src="<?= htmlspecialchars($p['image']) ?>" alt="" class="table-img">
                                    <div>
                                        <div style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars(substr($p['name'],0,35)) ?>...</div>
                                        <div style="font-size:0.78rem;color:var(--text-mid);"><?= htmlspecialchars($p['brand'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($p['cat_name']) ?></td>
                            <td style="font-weight:700;color:var(--primary);">$<?= number_format($p['price'],2) ?></td>
                            <td>
                                <span style="background:<?= $p['stock']>10?'#D1FAE5':($p['stock']>0?'#FEF3C7':'#FEE2E2') ?>;color:<?= $p['stock']>10?'#065F46':($p['stock']>0?'#92400E':'#991B1B') ?>;padding:3px 10px;border-radius:50px;font-size:0.78rem;font-weight:600;">
                                    <?= $p['stock'] ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:4px;">
                                    <span style="color:#FCD34D;">★</span>
                                    <span style="font-weight:600;"><?= $p['rating'] ?></span>
                                </div>
                            </td>
                            <td><?= $p['is_top_item'] ? '<svg viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>' : '<span style="color:var(--text-light)">—</span>' ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="/product-details.php?id=<?= $p['id'] ?>" class="icon-btn edit" title="View">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    <?php if ($vendorId): ?>
                                    <a href="?delete=<?= $p['id'] ?>" class="icon-btn delete" title="Delete" onclick="return confirm('Delete this product?')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Orders Table -->
        <div class="dash-card" id="orders">
            <h3>Recent Orders</h3>
            <?php if (empty($recentOrders)): ?>
                <div class="empty-state" style="padding:32px;">
                    <div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></div>
                    <h3>No orders yet</h3>
                </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $statusColors = ['pending'=>'status-pending','processing'=>'status-processing','shipped'=>'status-shipped','delivered'=>'status-delivered','cancelled'=>'status-cancelled'];
                        foreach ($recentOrders as $ord): ?>
                        <tr>
                            <td style="font-weight:700;">#<?= str_pad($ord['id'],6,'0',STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($ord['customer_name']) ?></td>
                            <td><?= $ord['item_count'] ?> items</td>
                            <td style="font-weight:700;color:var(--primary);">$<?= number_format($ord['total'],2) ?></td>
                            <td style="font-size:0.82rem;color:var(--text-mid);"><?= date('M d, Y', strtotime($ord['created_at'])) ?></td>
                            <td><span class="order-status <?= $statusColors[$ord['status']] ?>"><?= ucfirst($ord['status']) ?></span></td>
                            <td>
                                <form method="POST" style="display:flex;gap:6px;align-items:center;">
                                    <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                    <select name="status" class="sort-select" style="padding:6px 10px;font-size:0.78rem;">
                                        <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $ord['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-primary btn-sm">Save</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Add Product Form -->
        <?php if ($vendorId): ?>
        <div class="dash-card" id="add-product">
            <h3>➕ Add New Product</h3>
            <?php if ($addError): ?>
                <div class="alert alert-error"><?= htmlspecialchars($addError) ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" placeholder="e.g. Nike Air Max 270" required>
                    </div>
                    <div class="form-group">
                        <label>Brand</label>
                        <input type="text" name="brand" placeholder="e.g. Nike">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Describe your product..." style="min-height:100px;"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Price * ($)</label>
                        <input type="number" name="price" step="0.01" min="0" placeholder="99.99" required>
                    </div>
                    <div class="form-group">
                        <label>Original Price ($) <span style="color:var(--text-light)">(optional, for discount)</span></label>
                        <input type="number" name="original_price" step="0.01" min="0" placeholder="149.99">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock" min="0" placeholder="100" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <option value="">Select category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Image URL</label>
                    <input type="url" name="image_url" placeholder="https://example.com/image.jpg">
                    <p class="form-hint">Paste a public image URL for your product photo.</p>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Delivery Type</label>
                        <select name="delivery_type">
                            <option value="both">Standard + Pick Up</option>
                            <option value="standard">Standard Only</option>
                            <option value="pickup">Pick Up Only</option>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                            <input type="checkbox" name="is_top_item" value="1" style="width:18px;height:18px;accent-color:var(--primary);">
                            <span>Mark as Top Item (featured badge)</span>
                        </label>
                    </div>
                </div>
                <button type="submit" name="add_product" class="btn btn-primary btn-lg">Add Product</button>
            </form>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
