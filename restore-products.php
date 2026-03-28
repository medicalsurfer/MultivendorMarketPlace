<?php
/**
 * Venmark — Full Product Restore
 * Re-inserts all products (from both setup.php and seed-update.php).
 * Safe to run multiple times — uses INSERT IGNORE (skips duplicates by name).
 */
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

$log   = [];
$added = 0;

// Ensure vendor_id=1 exists (the MLC Market generic vendor used by seed-update products)
$pdo->exec("
    INSERT IGNORE INTO users (id, name, email, password, role)
    VALUES (1, 'MLC Market', 'mlc@mlc.com', '" . password_hash('mlc123', PASSWORD_DEFAULT) . "', 'vendor')
");
$pdo->exec("
    INSERT IGNORE INTO vendors (id, user_id, store_name, description)
    VALUES (1, 1, 'MLC Market', 'General marketplace vendor')
");

$allProducts = [
    // ─────────────────── Sport (category_id = 8) ───────────────────
    [1, 8, 'Smart Watch WH22-6 Fitness Tracker',
     'Advanced fitness tracking smartwatch with heart rate monitor and GPS.',
     454.00, null, 50, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400',
     'Xiaomi', 4.5, 23, 1, 'both'],

    [4, 8, 'Tennis Rackets for Beginners',
     'Lightweight aluminium tennis rackets perfect for beginners.',
     30.99, null, 120, 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=400',
     'Demix', 4.2, 45, 0, 'standard'],

    [4, 8, 'Premium Boxing Gloves Pro Series',
     'Professional boxing gloves with superior padding and wrist support.',
     196.84, 275.57, 35, 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?w=400',
     'Adidas', 4.7, 67, 0, 'pickup'],

    [4, 8, 'Club Kit 1 Recurve Archery Set',
     'Complete recurve archery set for club and recreational use.',
     48.99, null, 20, 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400',
     'Columbia', 3.8, 12, 0, 'standard'],

    [1, 8, 'Nike White Therma-Fit Pullover Hoodie',
     'Premium training hoodie with Therma-FIT technology for cold weather.',
     154.99, null, 80, 'https://images.unsplash.com/photo-1556821840-3a63f15732ce?w=400',
     'Nike', 5.0, 89, 0, 'both'],

    [1, 8, 'Lightweight White Nike Training Shoes',
     'Ultra-light Nike training shoes for maximum performance.',
     210.00, null, 60, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400',
     'Nike', 4.6, 134, 1, 'both'],

    [1, 8, 'Bowflex SelectTech 552 Dumbbell Set',
     'Adjustable dumbbells replacing 15 sets (5–52 lb each). Quick-dial weight selector.',
     429.99, 499.99, 10, 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=400',
     'Bowflex', 4.8, 167, 1, 'both'],

    [1, 8, 'Speed Cable Jump Rope',
     'Precision ball-bearing cable rope with foam handles. Ideal for HIIT and CrossFit.',
     19.99, null, 200, 'https://images.unsplash.com/photo-1598971457999-ca4ef48a9a71?w=400',
     'WOD Nation', 4.5, 93, 0, 'both'],

    // ─────────────────── Electronics (category_id = 11) ────────────
    [3, 11, 'Macbook Air 13 256Gb',
     'Apple MacBook Air with M1 chip, stunning Retina display.',
     935.90, null, 15, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400',
     'Apple', 4.9, 210, 1, 'standard'],

    [3, 11, 'Buds 4 Lite Black',
     'True wireless earbuds with active noise cancellation.',
     41.25, 55.90, 200, 'https://images.unsplash.com/photo-1606220588913-b3aacb4d2f46?w=400',
     'Xiaomi', 4.3, 88, 0, 'both'],

    [3, 11, 'PlayStation 5 825GB',
     'Next-gen gaming console with ultra-high-speed SSD.',
     684.60, null, 8, 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=400',
     'Sony', 5.0, 445, 1, 'standard'],

    [3, 11, 'Galaxy Watch4 40mm',
     'Advanced smartwatch with health monitoring and LTE support.',
     168.50, null, 45, 'https://images.unsplash.com/photo-1544117519-31a4b719223d?w=400',
     'Samsung', 4.4, 76, 0, 'both'],

    [1, 11, 'Apple iPad Pro 11" M2 256GB',
     'Supercharged by M2. Stunning Liquid Retina display, ProMotion 120Hz, Thunderbolt port.',
     799.00, null, 18, 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=400',
     'Apple', 4.9, 178, 1, 'standard'],

    [1, 11, 'Sony Alpha A7 III Full-Frame Camera',
     '24.2MP BSI CMOS sensor, 693-point hybrid AF, 4K video, 10fps burst. The photographer\'s choice.',
     1999.00, null, 6, 'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=400',
     'Sony', 4.9, 312, 0, 'standard'],

    // ─────────────────── Fashion (category_id = 4) ─────────────────
    [2, 4, 'Adidas Classic Tracksuit',
     'Iconic three-stripe tracksuit in premium cotton blend.',
     89.99, 120.00, 100, 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=400',
     'Adidas', 4.6, 55, 0, 'both'],

    [1, 4, 'Nike Air Force 1 White',
     'Timeless classic Nike sneakers in crisp white leather.',
     110.00, null, 70, 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=400',
     'Nike', 4.8, 320, 1, 'standard'],

    [1, 4, "Levi's 501 Original Fit Jeans",
     "Iconic straight-leg jeans in classic medium wash denim. The original jean since 1873.",
     79.99, null, 90, 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=400',
     "Levi's", 4.6, 234, 0, 'both'],

    [1, 4, 'Oversized Heavyweight Graphic Tee',
     '400gsm 100% cotton oversized tee. Garment-dyed for a lived-in vintage feel.',
     34.99, null, 150, 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=400',
     'Gildan', 4.3, 78, 0, 'both'],

    // ─────────────────── Home (category_id = 7) ────────────────────
    [3, 7, 'Minimalist Desk Lamp LED',
     'Modern LED desk lamp with adjustable brightness and color temperature.',
     45.00, 60.00, 150, 'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?w=400',
     'Philips', 4.2, 33, 0, 'standard'],

    [3, 7, 'Premium Bluetooth Speaker',
     'Portable waterproof speaker with 360° sound and 24h battery.',
     79.99, null, 85, 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400',
     'Sony', 4.5, 98, 0, 'both'],

    [1, 7, 'Mid-Century Velvet Accent Chair',
     'Stylish fluted velvet armchair with solid oak legs. Available in sage green and dusty pink.',
     349.99, 429.99, 8, 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=400',
     'IKEA', 4.6, 45, 0, 'standard'],

    [1, 7, 'Soy Candle Gift Set 4pc',
     'Hand-poured coconut-soy candles in amber glass jars. Cedarwood, sandalwood, amber & vanilla.',
     44.99, null, 75, 'https://images.unsplash.com/photo-1603905756915-f18d39f4ef83?w=400',
     'Brooklyn Candle', 4.7, 88, 0, 'both'],

    // ─────────────────── Gaming (category_id = 10) ─────────────────
    [3, 10, 'Pro Gaming Headset RGB',
     'Surround sound gaming headset with noise-cancelling mic and RGB.',
     65.00, 85.00, 40, 'https://images.unsplash.com/photo-1599669454699-248893623440?w=400',
     'Razer', 4.3, 67, 0, 'standard'],

    [3, 10, 'Mechanical Gaming Keyboard',
     'Full RGB mechanical keyboard with tactile switches for gaming.',
     129.99, null, 30, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=400',
     'Razer', 4.7, 112, 1, 'both'],

    [1, 10, 'Nintendo Switch OLED Model',
     'Enhanced 7" OLED screen, wide adjustable stand, 64GB internal storage, improved audio.',
     349.99, null, 25, 'https://images.unsplash.com/photo-1617096200347-cb04ae810b1d?w=400',
     'Nintendo', 4.9, 567, 1, 'standard'],

    [1, 10, 'Razer DeathAdder V3 Pro Gaming Mouse',
     'Ultra-lightweight ergonomic mouse. 30,000 DPI Focus Pro sensor, 90-hour battery.',
     149.99, null, 40, 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=400',
     'Razer', 4.8, 234, 0, 'both'],

    // ─────────────────── Crypto (category_id = 3) ──────────────────
    [1, 3, 'Ledger Nano X Hardware Wallet',
     'The gold-standard cold storage wallet. Supports 5,500+ coins via Bluetooth. FIPS-certified secure element.',
     149.00, null, 25, 'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=400',
     'Ledger', 4.8, 156, 1, 'standard'],

    [1, 3, 'Trezor Model T Crypto Wallet',
     'Open-source hardware wallet with full-colour touchscreen. Supports 1,800+ assets.',
     219.00, 249.00, 14, 'https://images.unsplash.com/photo-1621416894569-0f39ed31d247?w=400',
     'Trezor', 4.7, 89, 0, 'standard'],

    [1, 3, 'Crypto "HODL" Premium Hoodie',
     'Heavyweight 400gsm cotton hoodie with embroidered BTC logo. Unisex fit.',
     54.99, null, 80, 'https://images.unsplash.com/photo-1556821840-3a63f15732ce?w=400',
     'CryptoCloths', 4.4, 37, 0, 'both'],

    // ─────────────────── Art (category_id = 6) ─────────────────────
    [1, 6, 'Winsor & Newton Watercolour Set 48pc',
     'Artist-grade watercolours with exceptional lightfastness. Includes mixing palette and 3 brushes.',
     49.99, 64.99, 55, 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=400',
     'Winsor & Newton', 4.6, 42, 0, 'standard'],

    [1, 6, 'Wacom Intuos Pro Medium Drawing Tablet',
     'Pro-level drawing tablet with 8,192 levels of pressure sensitivity and multi-touch.',
     249.99, null, 22, 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=400',
     'Wacom', 4.9, 203, 1, 'standard'],

    [1, 6, 'Mont Marte Oil Painting Starter Kit',
     '24 oil colours + 5 stretched canvases + 6 brushes. Everything to start oil painting today.',
     59.99, 79.99, 45, 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=400',
     'Mont Marte', 4.3, 28, 0, 'both'],

    [1, 6, 'Professional Sketch Pencil Set 26pc',
     'Graphite pencils from 9H to 9B in a premium tin. Ideal for portrait and landscape sketching.',
     24.99, null, 120, 'https://images.unsplash.com/photo-1632516643720-e7f5d7d6ecc9?w=400',
     'Faber-Castell', 4.7, 88, 0, 'both'],

    // ─────────────────── Health & Wellness (category_id = 5) ────────
    [1, 5, 'Lululemon The Mat 5mm Yoga Mat',
     'Extra-thick grip yoga mat with natural rubber base and body-length alignment lines.',
     98.00, null, 60, 'https://images.unsplash.com/photo-1601925228441-a6fe7e1e5d6b?w=400',
     'Lululemon', 4.8, 178, 1, 'both'],

    [1, 5, 'Optimum Nutrition Gold Standard Whey 2kg',
     '24g of blended protein per serving. Informed Choice certified. Vanilla ice cream flavour.',
     64.99, 79.99, 80, 'https://images.unsplash.com/photo-1593095948071-474c5cc2989d?w=400',
     'Optimum Nutrition', 4.6, 312, 0, 'standard'],

    [1, 5, 'Withings Body+ Smart Scale',
     'Wi-Fi body composition scale — weight, BMI, body fat, water %, muscle and bone mass.',
     79.99, null, 40, 'https://images.unsplash.com/photo-1520170350707-b2da59970118?w=400',
     'Withings', 4.5, 94, 0, 'both'],

    [1, 5, 'TheraBand Resistance Bands Set 5pc',
     'Five progressive resistance levels from extra-light to heavy. Latex-free. Includes exercise guide.',
     29.99, null, 150, 'https://images.unsplash.com/photo-1598289431512-b97b0917affc?w=400',
     'TheraBand', 4.5, 67, 0, 'both'],

    // ─────────────────── Music (category_id = 9) ───────────────────
    [1, 9, 'Yamaha F310 Acoustic Guitar',
     'Full-size dreadnought acoustic with solid spruce top and natural gloss finish. Ideal for beginners.',
     149.99, null, 30, 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?w=400',
     'Yamaha', 4.7, 89, 1, 'standard'],

    [1, 9, 'Audio-Technica AT-LP120XUSB Turntable',
     'Direct-drive turntable with built-in phono preamp and USB output for vinyl digitising.',
     299.00, null, 12, 'https://images.unsplash.com/photo-1530026186672-2cd00ffc50fe?w=400',
     'Audio-Technica', 4.8, 156, 0, 'standard'],

    [1, 9, 'Sony WH-1000XM5 Wireless Headphones',
     'Industry-leading noise cancelling with 8 microphones. 30-hour battery, foldable design.',
     349.99, 399.99, 20, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400',
     'Sony', 4.9, 445, 1, 'standard'],

    [1, 9, 'Korg Mini Keyboard 37 Keys',
     'Compact synthesiser with 200 preset sounds and built-in speaker. USB + MIDI capable.',
     129.99, null, 18, 'https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?w=400',
     'Korg', 4.5, 63, 0, 'both'],
];

$check  = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name = ?");
$insert = $pdo->prepare(
    "INSERT INTO products
        (vendor_id, category_id, name, description, price, original_price,
         stock, image, brand, rating, review_count, is_top_item, delivery_type)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
);

foreach ($allProducts as $p) {
    $check->execute([$p[2]]);
    if ($check->fetchColumn() == 0) {
        $insert->execute($p);
        $log[] = ['added', $p[2]];
        $added++;
    } else {
        $log[] = ['exists', $p[2]];
    }
}

$total = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Product Restore — Venmark</title>
<style>
    body{font-family:system-ui,sans-serif;background:#f0f4ff;margin:0;padding:32px;color:#1f2937}
    h1{font-size:1.6rem;font-weight:800;margin-bottom:4px}
    p{color:#6b7280;margin-bottom:24px}
    .cards{display:flex;gap:16px;margin-bottom:28px;flex-wrap:wrap}
    .card{background:white;border-radius:12px;padding:20px 28px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
    .card strong{font-size:2rem;font-weight:800;color:#2563EB;display:block}
    .card span{font-size:.85rem;color:#6b7280}
    table{width:100%;border-collapse:collapse;background:white;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
    th{background:#2563EB;color:white;padding:10px 16px;text-align:left;font-size:.8rem;text-transform:uppercase}
    td{padding:9px 16px;font-size:.875rem;border-bottom:1px solid #f3f4f6}
    tr:last-child td{border-bottom:none}
    .badge{display:inline-block;padding:2px 9px;border-radius:50px;font-size:.75rem;font-weight:600}
    .added{background:#dbeafe;color:#1e40af}
    .exists{background:#f0fdf4;color:#166534}
    .btn{display:inline-block;margin-top:24px;padding:12px 24px;background:#2563EB;color:white;border-radius:50px;font-weight:600;text-decoration:none}
</style>
</head>
<body>
<h1>✅ Product Restore Complete</h1>
<p>All products have been re-inserted into the database.</p>
<div class="cards">
    <div class="card"><strong><?= $added ?></strong><span>Products added</span></div>
    <div class="card"><strong><?= count($log) - $added ?></strong><span>Already existed</span></div>
    <div class="card"><strong><?= $total ?></strong><span>Total in database</span></div>
</div>
<table>
    <thead><tr><th>Status</th><th>Product</th></tr></thead>
    <tbody>
    <?php foreach ($log as [$status, $name]): ?>
        <tr>
            <td><span class="badge <?= $status ?>"><?= $status ?></span></td>
            <td><?= htmlspecialchars($name) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<a href="index.php" class="btn">View Marketplace →</a>
</body>
</html>
