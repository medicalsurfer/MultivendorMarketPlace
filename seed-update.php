<?php
/**
 * MLC Marketplace — Product Seed Update
 * Fixes incorrect product data and adds new products across all categories.
 * Safe to run multiple times (idempotent).
 */
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

$log = [];

// ═══════════════════════════════════════════════════════════
// 1. FIX EXISTING PRODUCTS — wrong brands / duplicate images
// ═══════════════════════════════════════════════════════════
$fixes = [
    // Desk lamp had "New Balance" as brand — should be Philips
    ["UPDATE products SET brand='Philips' WHERE name='Minimalist Desk Lamp LED' AND brand='New Balance'"],
    // Gaming keyboard had "Asics" (a running shoe brand) — should be Razer
    ["UPDATE products SET brand='Razer' WHERE name='Mechanical Gaming Keyboard' AND brand='Asics'"],
    // Gaming headset had "Samsung" — change to Razer for consistency
    ["UPDATE products SET brand='Razer' WHERE name='Pro Gaming Headset RGB' AND brand='Samsung'"],
    // Nike Air Force 1 had the same image as Nike Training Shoes — fix with distinct shoe photo
    ["UPDATE products SET image='https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=400' WHERE name='Nike Air Force 1 White'"],
    // Archery set image fix — use a cleaner archery photo
    ["UPDATE products SET image='https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400' WHERE name='Club Kit 1 Recurve Archery Set'"],
];

foreach ($fixes as [$sql]) {
    $rows = $pdo->exec($sql);
    $log[] = ['fix', $sql, $rows . ' row(s) updated'];
}

// ═══════════════════════════════════════════════════════════
// 2. ADD NEW PRODUCTS — fill all sparse / empty categories
//    Category IDs (from setup.php order):
//    1=All  2=Deals  3=Crypto  4=Fashion  5=Health  6=Art
//    7=Home  8=Sport  9=Music  10=Gaming  11=Electronics
//    Vendor 1 = MLC Market (generic)
// ═══════════════════════════════════════════════════════════
$newProducts = [

    // ── Crypto (category_id = 3) ──────────────────────────
    [1, 3, 'Ledger Nano X Hardware Wallet',
        'The gold-standard cold storage wallet. Supports 5,500+ coins via Bluetooth. FIPS-certified secure element.',
        149.00, null, 25,
        'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=400',
        'Ledger', 4.8, 156, 1, 'standard'],

    [1, 3, 'Trezor Model T Crypto Wallet',
        'Open-source hardware wallet with full-colour touchscreen. Supports 1,800+ assets.',
        219.00, 249.00, 14,
        'https://images.unsplash.com/photo-1621416894569-0f39ed31d247?w=400',
        'Trezor', 4.7, 89, 0, 'standard'],

    [1, 3, 'Crypto "HODL" Premium Hoodie',
        'Heavyweight 400gsm cotton hoodie with embroidered BTC logo. Unisex fit.',
        54.99, null, 80,
        'https://images.unsplash.com/photo-1556821840-3a63f15732ce?w=400',
        'CryptoCloths', 4.4, 37, 0, 'both'],

    // ── Art (category_id = 6) ─────────────────────────────
    [1, 6, 'Winsor & Newton Watercolour Set 48pc',
        'Artist-grade watercolours with exceptional lightfastness. Includes mixing palette and 3 brushes.',
        49.99, 64.99, 55,
        'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=400',
        'Winsor & Newton', 4.6, 42, 0, 'standard'],

    [1, 6, 'Wacom Intuos Pro Medium Drawing Tablet',
        'Pro-level drawing tablet with 8,192 levels of pressure sensitivity and multi-touch.',
        249.99, null, 22,
        'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=400',
        'Wacom', 4.9, 203, 1, 'standard'],

    [1, 6, 'Mont Marte Oil Painting Starter Kit',
        '24 oil colours + 5 stretched canvases + 6 brushes. Everything to start oil painting today.',
        59.99, 79.99, 45,
        'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=400',
        'Mont Marte', 4.3, 28, 0, 'both'],

    [1, 6, 'Professional Sketch Pencil Set 26pc',
        'Graphite pencils from 9H to 9B in a premium tin. Ideal for portrait and landscape sketching.',
        24.99, null, 120,
        'https://images.unsplash.com/photo-1632516643720-e7f5d7d6ecc9?w=400',
        'Faber-Castell', 4.7, 88, 0, 'both'],

    // ── Health & Wellness (category_id = 5) ──────────────
    [1, 5, 'Lululemon The Mat 5mm Yoga Mat',
        'Extra-thick grip yoga mat with natural rubber base and body-length alignment lines.',
        98.00, null, 60,
        'https://images.unsplash.com/photo-1601925228441-a6fe7e1e5d6b?w=400',
        'Lululemon', 4.8, 178, 1, 'both'],

    [1, 5, 'Optimum Nutrition Gold Standard Whey 2kg',
        '24g of blended protein per serving. Informed Choice certified. Vanilla ice cream flavour.',
        64.99, 79.99, 80,
        'https://images.unsplash.com/photo-1593095948071-474c5cc2989d?w=400',
        'Optimum Nutrition', 4.6, 312, 0, 'standard'],

    [1, 5, 'Withings Body+ Smart Scale',
        'Wi-Fi body composition scale — weight, BMI, body fat, water %, muscle and bone mass.',
        79.99, null, 40,
        'https://images.unsplash.com/photo-1520170350707-b2da59970118?w=400',
        'Withings', 4.5, 94, 0, 'both'],

    [1, 5, 'TheraBand Resistance Bands Set 5pc',
        'Five progressive resistance levels from extra-light to heavy. Latex-free. Includes exercise guide.',
        29.99, null, 150,
        'https://images.unsplash.com/photo-1598289431512-b97b0917affc?w=400',
        'TheraBand', 4.5, 67, 0, 'both'],

    // ── Music (category_id = 9) ───────────────────────────
    [1, 9, 'Yamaha F310 Acoustic Guitar',
        'Full-size dreadnought acoustic with solid spruce top and natural gloss finish. Ideal for beginners.',
        149.99, null, 30,
        'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?w=400',
        'Yamaha', 4.7, 89, 1, 'standard'],

    [1, 9, 'Audio-Technica AT-LP120XUSB Turntable',
        'Direct-drive turntable with built-in phono preamp and USB output for vinyl digitising.',
        299.00, null, 12,
        'https://images.unsplash.com/photo-1530026186672-2cd00ffc50fe?w=400',
        'Audio-Technica', 4.8, 156, 0, 'standard'],

    [1, 9, 'Sony WH-1000XM5 Wireless Headphones',
        'Industry-leading noise cancelling with 8 microphones. 30-hour battery, foldable design.',
        349.99, 399.99, 20,
        'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400',
        'Sony', 4.9, 445, 1, 'standard'],

    [1, 9, 'Korg Mini Keyboard 37 Keys',
        'Compact synthesiser with 200 preset sounds and built-in speaker. USB + MIDI capable.',
        129.99, null, 18,
        'https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?w=400',
        'Korg', 4.5, 63, 0, 'both'],

    // ── More Fashion (category_id = 4) ────────────────────
    [1, 4, "Levi's 501 Original Fit Jeans",
        "Iconic straight-leg jeans in classic medium wash denim. The original jean since 1873.",
        79.99, null, 90,
        'https://images.unsplash.com/photo-1542272604-787c3835535d?w=400',
        "Levi's", 4.6, 234, 0, 'both'],

    [1, 4, 'Oversized Heavyweight Graphic Tee',
        '400gsm 100% cotton oversized tee. Garment-dyed for a lived-in vintage feel.',
        34.99, null, 150,
        'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=400',
        'Gildan', 4.3, 78, 0, 'both'],

    // ── More Sport (category_id = 8) ─────────────────────
    [1, 8, 'Bowflex SelectTech 552 Dumbbell Set',
        'Adjustable dumbbells replacing 15 sets (5–52 lb each). Quick-dial weight selector.',
        429.99, 499.99, 10,
        'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=400',
        'Bowflex', 4.8, 167, 1, 'both'],

    [1, 8, 'Speed Cable Jump Rope',
        'Precision ball-bearing cable rope with foam handles. Ideal for HIIT and CrossFit.',
        19.99, null, 200,
        'https://images.unsplash.com/photo-1598971457999-ca4ef48a9a71?w=400',
        'WOD Nation', 4.5, 93, 0, 'both'],

    // ── More Electronics (category_id = 11) ──────────────
    [1, 11, 'Apple iPad Pro 11" M2 256GB',
        'Supercharged by M2. Stunning Liquid Retina display, ProMotion 120Hz, Thunderbolt port.',
        799.00, null, 18,
        'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=400',
        'Apple', 4.9, 178, 1, 'standard'],

    [1, 11, 'Sony Alpha A7 III Full-Frame Camera',
        '24.2MP BSI CMOS sensor, 693-point hybrid AF, 4K video, 10fps burst. The photographer\'s choice.',
        1999.00, null, 6,
        'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=400',
        'Sony', 4.9, 312, 0, 'standard'],

    // ── More Home (category_id = 7) ───────────────────────
    [1, 7, 'Mid-Century Velvet Accent Chair',
        'Stylish fluted velvet armchair with solid oak legs. Available in sage green and dusty pink.',
        349.99, 429.99, 8,
        'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=400',
        'IKEA', 4.6, 45, 0, 'standard'],

    [1, 7, 'Soy Candle Gift Set 4pc',
        'Hand-poured coconut-soy candles in amber glass jars. Cedarwood, sandalwood, amber & vanilla.',
        44.99, null, 75,
        'https://images.unsplash.com/photo-1603905756915-f18d39f4ef83?w=400',
        'Brooklyn Candle', 4.7, 88, 0, 'both'],

    // ── More Gaming (category_id = 10) ────────────────────
    [1, 10, 'Nintendo Switch OLED Model',
        'Enhanced 7" OLED screen, wide adjustable stand, 64GB internal storage, improved audio.',
        349.99, null, 25,
        'https://images.unsplash.com/photo-1617096200347-cb04ae810b1d?w=400',
        'Nintendo', 4.9, 567, 1, 'standard'],

    [1, 10, 'Razer DeathAdder V3 Pro Gaming Mouse',
        'Ultra-lightweight ergonomic mouse. 30,000 DPI Focus Pro sensor, 90-hour battery.',
        149.99, null, 40,
        'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=400',
        'Razer', 4.8, 234, 0, 'both'],
];

// Insert only if product name doesn't already exist
$check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name = ?");
$insert = $pdo->prepare(
    "INSERT INTO products
        (vendor_id, category_id, name, description, price, original_price,
         stock, image, brand, rating, review_count, is_top_item, delivery_type)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
);

$added = 0;
$skipped = 0;
foreach ($newProducts as $p) {
    $check->execute([$p[2]]);
    if ($check->fetchColumn() == 0) {
        $insert->execute($p);
        $log[] = ['added', $p[2], 'OK'];
        $added++;
    } else {
        $log[] = ['skipped', $p[2], 'already exists'];
        $skipped++;
    }
}

// ═══════════════════════════════════════════════════════════
// OUTPUT
// ═══════════════════════════════════════════════════════════
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Seed Update — MLC Marketplace</title>
<style>
    body { font-family: 'Inter', system-ui, sans-serif; background:#f5f3ff; margin:0; padding:32px; color:#1f2937; }
    h1 { font-size:1.5rem; font-weight:800; margin-bottom:8px; }
    p  { color:#6b7280; margin-bottom:24px; }
    table { width:100%; border-collapse:collapse; background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.06); }
    th { background:#7c3aed; color:white; padding:10px 14px; text-align:left; font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; }
    td { padding:10px 14px; font-size:.875rem; border-bottom:1px solid #f3f4f6; }
    tr:last-child td { border-bottom:none; }
    .badge { display:inline-block; padding:2px 8px; border-radius:50px; font-size:.75rem; font-weight:600; }
    .added   { background:#d1fae5; color:#065f46; }
    .skipped { background:#fef3c7; color:#92400e; }
    .fix     { background:#dbeafe; color:#1e40af; }
    .summary { margin-top:24px; display:flex; gap:16px; flex-wrap:wrap; }
    .card { background:white; border-radius:12px; padding:20px 24px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
    .card strong { font-size:1.8rem; font-weight:800; color:#7c3aed; display:block; }
    .card span { font-size:.85rem; color:#6b7280; }
    .btn { display:inline-block; margin-top:24px; padding:12px 24px; background:#7c3aed; color:white; border-radius:50px; font-weight:600; text-decoration:none; font-size:.9rem; }
</style>
</head>
<body>
<h1>Seed Update Complete</h1>
<p>Fixed existing products and added new products across all categories.</p>

<div class="summary">
    <div class="card"><strong><?= $added ?></strong><span>Products added</span></div>
    <div class="card"><strong><?= $skipped ?></strong><span>Already existed (skipped)</span></div>
    <div class="card"><strong><?= count($fixes) ?></strong><span>Fixes applied</span></div>
</div>

<table style="margin-top:24px;">
    <thead><tr><th>Action</th><th>Item</th><th>Result</th></tr></thead>
    <tbody>
    <?php foreach ($log as [$action, $item, $result]): ?>
        <tr>
            <td><span class="badge <?= $action ?>"><?= htmlspecialchars($action) ?></span></td>
            <td><?= htmlspecialchars($item) ?></td>
            <td><?= htmlspecialchars($result) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<a href="index.php" class="btn">View Marketplace →</a>
</body>
</html>
