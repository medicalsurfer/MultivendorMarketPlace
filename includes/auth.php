<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $base = defined('BASE_URL') ? BASE_URL : '';
        header('Location: ' . $base . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function isVendor(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'vendor';
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function currentUser(): array {
    return [
        'id'     => $_SESSION['user_id']    ?? null,
        'name'   => $_SESSION['user_name']  ?? '',
        'email'  => $_SESSION['user_email'] ?? '',
        'role'   => $_SESSION['role']       ?? 'customer',
        'avatar' => $_SESSION['avatar']     ?? null,
    ];
}

function getCartCount(): int {
    if (!isLoggedIn()) return 0;
    require_once __DIR__ . '/../config/database.php';
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException) { return 0; }
}

function getFavCount(): int {
    if (!isLoggedIn()) return 0;
    require_once __DIR__ . '/../config/database.php';
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException) { return 0; }
}
