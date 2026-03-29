<?php
/**
 * Safe migration script — run ONCE after git pull on the server.
 * Does NOT drop or recreate tables. Only adds missing columns and updates data.
 * DELETE this file from the server after running.
 */

require_once __DIR__ . '/config/database.php';

// Basic protection — remove this check if running from CLI
if (php_sapi_name() !== 'cli') {
    $secret = $_GET['secret'] ?? '';
    if ($secret !== 'venmark_migrate_2024') {
        http_response_code(403);
        die('Forbidden. Add ?secret=venmark_migrate_2024 to the URL.');
    }
}

$pdo = getDB();
$log = [];

function run(PDO $pdo, string $sql, string $label, array &$log): void {
    try {
        $pdo->exec($sql);
        $log[] = "✅ $label";
    } catch (PDOException $e) {
        $log[] = "⚠️  $label — " . $e->getMessage();
    }
}

// ── 1. Ensure orders table has payment columns ───────────────────────────────
// These were added in the initial schema but may be missing on older installs.
$cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('payment_status', $cols)) {
    run($pdo,
        "ALTER TABLE orders ADD COLUMN payment_status ENUM('unpaid','pending','paid','failed') NOT NULL DEFAULT 'unpaid' AFTER address",
        'Add orders.payment_status', $log);
} else {
    $log[] = "— orders.payment_status already exists";
}

if (!in_array('payment_ref', $cols)) {
    run($pdo,
        "ALTER TABLE orders ADD COLUMN payment_ref VARCHAR(120) NULL AFTER payment_status",
        'Add orders.payment_ref', $log);
} else {
    $log[] = "— orders.payment_ref already exists";
}

if (!in_array('payer_phone', $cols)) {
    run($pdo,
        "ALTER TABLE orders ADD COLUMN payer_phone VARCHAR(30) NULL AFTER payment_ref",
        'Add orders.payer_phone', $log);
} else {
    $log[] = "— orders.payer_phone already exists";
}

// ── 2. Update product images to local paths ──────────────────────────────────
// Products were migrated from Unsplash URLs to local /assets/images/products/product_N.jpg
// This only updates rows that still point to Unsplash.

$imageMap = [];
for ($i = 1; $i <= 41; $i++) {
    $imageMap[$i] = "/assets/images/products/product_{$i}.jpg";
}

$stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ? AND (image LIKE '%unsplash%' OR image LIKE '%picsum%' OR image IS NULL OR image = '')");
$updated = 0;
foreach ($imageMap as $id => $path) {
    $stmt->execute([$path, $id]);
    $updated += $stmt->rowCount();
}
$log[] = "✅ Updated $updated product image path(s) to local files";

// ── 3. Ensure uploads directory exists ──────────────────────────────────────
$uploadDir = __DIR__ . '/uploads/products/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    $log[] = "✅ Created uploads/products/ directory";
} else {
    $log[] = "— uploads/products/ already exists";
}

// ── Done ─────────────────────────────────────────────────────────────────────
if (php_sapi_name() === 'cli') {
    foreach ($log as $line) {
        echo $line . "\n";
    }
} else {
    echo "<pre style='font-family:monospace;font-size:15px;padding:20px;background:#f8f5ff;border-radius:12px;'>";
    echo "<strong style='font-size:18px'>Venmark Migration Log</strong>\n\n";
    foreach ($log as $line) echo htmlspecialchars($line) . "\n";
    echo "\n<strong>Done. Delete migrate.php from the server now.</strong>";
    echo "</pre>";
}
