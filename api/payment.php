<?php
// ═══════════════════════════════════════════════════════════
//   Venmark — Payment API endpoint
//   POST actions: initiate | status
// ═══════════════════════════════════════════════════════════
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payment.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function jsonOut(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (!isLoggedIn()) jsonOut(['error' => 'Unauthenticated'], 401);

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? ($_GET['action'] ?? '');
$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];

// ── Migrate orders table (add payment columns if missing) ──
try {
    $pdo->exec("ALTER TABLE orders
        ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid','pending','paid','failed') NOT NULL DEFAULT 'unpaid',
        ADD COLUMN IF NOT EXISTS payment_ref    VARCHAR(120)  NULL,
        ADD COLUMN IF NOT EXISTS payer_phone    VARCHAR(30)   NULL");
} catch (PDOException $e) { /* columns may already exist */ }

// ══════════════════════════════════════════════════════════
//  ACTION: initiate — create pending order + call Campay
// ══════════════════════════════════════════════════════════
if ($action === 'initiate') {

    $phone        = trim($body['phone']        ?? '');
    $deliveryType = $body['delivery_type']     ?? 'standard';
    $address      = trim($body['address']      ?? '');

    // Validate phone (Cameroon: 6XXXXXXXX  9 digits)
    $cleanPhone = preg_replace('/\D/', '', $phone);
    if (!preg_match('/^(237)?6[5-9]\d{7}$/', $cleanPhone)) {
        // For sandbox accept any 9-digit number
        if (CAMPAY_ENV !== 'sandbox' || strlen($cleanPhone) < 9) {
            jsonOut(['error' => 'Invalid Cameroon mobile number (e.g. 650000000)']);
        }
    }
    // Normalise: strip country code prefix
    if (strpos($cleanPhone, '237') === 0 && strlen($cleanPhone) === 12) {
        $cleanPhone = substr($cleanPhone, 3);
    }

    // Get cart items
    $stmt = $pdo->prepare("SELECT c.quantity, p.price, p.id AS product_id, p.name, p.stock
        FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ?");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll();

    if (empty($cartItems)) {
        jsonOut(['error' => 'Your cart is empty']);
    }

    // Calculate totals (USD → XAF for payment)
    $subtotalUsd = array_sum(array_map(fn($i) => $i['quantity'] * $i['price'], $cartItems));
    $shippingUsd = 5.99;
    $taxUsd      = $subtotalUsd * 0.05;
    $totalUsd    = $subtotalUsd + $shippingUsd + $taxUsd;
    $totalXaf    = (int)round($totalUsd * 655);

    // Create a PENDING order in DB
    $pdo->prepare("INSERT INTO orders (user_id, total, status, delivery_type, address, payment_status, payer_phone)
        VALUES (?,?,?,?,?,?,?)")
        ->execute([$userId, round($totalUsd, 2), 'pending', $deliveryType, $address, 'pending', $cleanPhone]);
    $orderId     = (int)$pdo->lastInsertId();
    $externalRef = 'VNMK-' . $orderId . '-' . time();

    // Insert order items
    $ins = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
    foreach ($cartItems as $item) {
        $ins->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
    }

    // Get Campay token
    $token = campay_token();
    if (!$token) {
        // Campay unreachable (sandbox or wrong credentials) — for dev mode allow fallback
        if (CAMPAY_ENV === 'sandbox' && (CAMPAY_USERNAME === 'YOUR_CAMPAY_USERNAME')) {
            // Demo mode: pretend payment initiated
            jsonOut([
                'status'      => 'demo',
                'order_id'    => $orderId,
                'reference'   => 'DEMO-' . $orderId,
                'amount_xaf'  => $totalXaf,
                'message'     => 'Demo mode — no real API key set. Payment will auto-succeed in 3 seconds.',
            ]);
        }
        // Clean up the order we just created
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
        jsonOut(['error' => 'Cannot connect to payment gateway. Check your Campay credentials.'], 502);
    }

    $description = 'Venmark Order #' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
    $result = campay_collect($token, $totalXaf, $cleanPhone, $description, $externalRef);

    if (!empty($result['error']) || empty($result['reference'])) {
        $pdo->prepare("UPDATE orders SET payment_status='failed' WHERE id=?")->execute([$orderId]);
        $errMsg = $result['message'] ?? ($result['error'] ?? 'Payment initiation failed');
        jsonOut(['error' => $errMsg]);
    }

    // Store Campay reference in order
    $pdo->prepare("UPDATE orders SET payment_ref=? WHERE id=?")
        ->execute([$result['reference'], $orderId]);

    jsonOut([
        'status'     => 'pending',
        'order_id'   => $orderId,
        'reference'  => $result['reference'],
        'amount_xaf' => $totalXaf,
        'message'    => 'A payment request has been sent to your phone. Please approve it.',
    ]);
}

// ══════════════════════════════════════════════════════════
//  ACTION: status — poll Campay for transaction result
// ══════════════════════════════════════════════════════════
if ($action === 'status') {
    $reference = trim($body['reference'] ?? '');
    $orderId   = (int)($body['order_id'] ?? 0);

    // Demo mode auto-success
    if (strpos($reference, 'DEMO-') === 0) {
        // Clear cart and mark order paid
        $pdo->prepare("UPDATE orders SET payment_status='paid', status='processing' WHERE id=? AND user_id=?")
            ->execute([$orderId, $userId]);
        $pdo->prepare("DELETE FROM cart WHERE user_id=?")->execute([$userId]);
        jsonOut(['payment_status' => 'SUCCESSFUL', 'order_id' => $orderId]);
    }

    if (!$reference || !$orderId) jsonOut(['error' => 'Missing reference or order_id']);

    // Verify this order belongs to current user
    $order = $pdo->prepare("SELECT id, payment_status FROM orders WHERE id=? AND user_id=?");
    $order->execute([$orderId, $userId]);
    $orderRow = $order->fetch();
    if (!$orderRow) jsonOut(['error' => 'Order not found'], 404);

    // Already confirmed
    if ($orderRow['payment_status'] === 'paid') {
        jsonOut(['payment_status' => 'SUCCESSFUL', 'order_id' => $orderId]);
    }

    $token = campay_token();
    if (!$token) jsonOut(['error' => 'Gateway unreachable'], 502);

    $txn = campay_status($token, $reference);

    if (!empty($txn['error'])) {
        jsonOut(['error' => $txn['error']], 502);
    }

    $campayStatus = strtoupper($txn['status'] ?? 'PENDING');

    if ($campayStatus === 'SUCCESSFUL') {
        $pdo->prepare("UPDATE orders SET payment_status='paid', status='processing' WHERE id=? AND user_id=?")
            ->execute([$orderId, $userId]);
        $pdo->prepare("DELETE FROM cart WHERE user_id=?")->execute([$userId]);
    } elseif ($campayStatus === 'FAILED') {
        $pdo->prepare("UPDATE orders SET payment_status='failed' WHERE id=? AND user_id=?")
            ->execute([$orderId, $userId]);
    }

    jsonOut(['payment_status' => $campayStatus, 'order_id' => $orderId]);
}

jsonOut(['error' => 'Unknown action'], 400);
