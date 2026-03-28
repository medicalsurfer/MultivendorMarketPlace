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
$productId = (int)($input['product_id'] ?? 0);
$userId = $_SESSION['user_id'];
$pdo = getDB();

// Check if already favorited
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
$stmt->execute([$userId, $productId]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?")->execute([$userId, $productId]);
    $isFav = false;
} else {
    $pdo->prepare("INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?,?)")->execute([$userId, $productId]);
    $isFav = true;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
$stmt->execute([$userId]);
$favCount = (int)$stmt->fetchColumn();

echo json_encode(['success' => true, 'is_favorite' => $isFav, 'fav_count' => $favCount]);
