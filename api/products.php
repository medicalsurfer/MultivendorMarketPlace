<?php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$pdo = getDB();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'search':
        $q = '%' . trim($_GET['q'] ?? '') . '%';
        $stmt = $pdo->prepare("SELECT p.id, p.name, p.price, p.image FROM products p WHERE p.name LIKE ? LIMIT 8");
        $stmt->execute([$q]);
        $results = $stmt->fetchAll();
        // Fix image URLs with getImageUrl
        foreach ($results as &$result) {
            $result['image'] = getImageUrl($result['image']);
        }
        echo json_encode($results);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
