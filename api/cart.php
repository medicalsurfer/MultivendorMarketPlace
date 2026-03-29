<?php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    $base = defined('BASE_URL') ? BASE_URL : '';
    echo json_encode(['success' => false, 'redirect' => $base . '/login.php']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$userId = $_SESSION['user_id'];
$pdo = getDB();

function cartTotal($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT SUM(c.quantity * p.price) FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ?");
    $stmt->execute([$userId]);
    return (float)$stmt->fetchColumn();
}
function cartCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

switch ($action) {
    case 'add':
        $productId = (int)($input['product_id'] ?? 0);
        $qty = max(1, (int)($input['quantity'] ?? 1));
        // check product exists
        $s = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $s->execute([$productId]);
        if (!$s->fetch()) { echo json_encode(['success' => false, 'error' => 'Product not found']); exit; }
        // upsert
        $s = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
        $s->execute([$userId, $productId, $qty, $qty]);
        echo json_encode(['success' => true, 'cart_count' => cartCount($pdo, $userId)]);
        break;

    case 'update':
        $productId = (int)($input['product_id'] ?? 0);
        $qty = max(1, (int)($input['quantity'] ?? 1));
        $s = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $s->execute([$qty, $userId, $productId]);
        // item total
        $s = $pdo->prepare("SELECT c.quantity * p.price FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ? AND c.product_id = ?");
        $s->execute([$userId, $productId]);
        $itemTotal = (float)$s->fetchColumn();
        echo json_encode(['success' => true, 'cart_count' => cartCount($pdo, $userId), 'item_total' => $itemTotal, 'order_total' => cartTotal($pdo, $userId)]);
        break;

    case 'remove':
        $productId = (int)($input['product_id'] ?? 0);
        $s = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $s->execute([$userId, $productId]);
        echo json_encode(['success' => true, 'cart_count' => cartCount($pdo, $userId), 'order_total' => cartTotal($pdo, $userId)]);
        break;

    case 'get':
        $s = $pdo->prepare("SELECT p.id, p.name, p.image, p.price, c.quantity FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ? ORDER BY c.updated_at DESC");
        $s->execute([$userId]);
        $items = $s->fetchAll();
        // Fix image URLs with getImageUrl
        foreach ($items as &$item) {
            $item['image'] = getImageUrl($item['image']);
        }
        echo json_encode(['success' => true, 'items' => $items]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
