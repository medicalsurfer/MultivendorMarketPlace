<?php
// Database setup — creates DB, tables, and seeds demo data
$host = 'localhost';
$user = 'root';
$pass = '11655Usscsao';
$dbname = 'multivendor_db';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Drop and recreate database for a clean slate
    $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
    $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // ── Users ──────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('customer','vendor','admin') DEFAULT 'customer',
        avatar VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // ── Vendors ────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS vendors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        store_name VARCHAR(150) NOT NULL,
        description TEXT,
        logo VARCHAR(255) DEFAULT NULL,
        is_approved TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // ── Categories ─────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        icon VARCHAR(50) DEFAULT '🛍️'
    ) ENGINE=InnoDB");

    // ── Products ───────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vendor_id INT NOT NULL,
        category_id INT NOT NULL,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        original_price DECIMAL(10,2) DEFAULT NULL,
        stock INT DEFAULT 0,
        image VARCHAR(255) DEFAULT NULL,
        brand VARCHAR(100) DEFAULT NULL,
        rating DECIMAL(2,1) DEFAULT 0.0,
        review_count INT DEFAULT 0,
        is_top_item TINYINT(1) DEFAULT 0,
        delivery_type ENUM('standard','pickup','both') DEFAULT 'both',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    ) ENGINE=InnoDB");

    // ── Cart ───────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        UNIQUE KEY unique_cart (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // ── Orders ─────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
        delivery_type ENUM('standard','pickup') DEFAULT 'standard',
        address TEXT,
        payment_status ENUM('unpaid','pending','paid','failed') NOT NULL DEFAULT 'unpaid',
        payment_ref    VARCHAR(120) NULL,
        payer_phone    VARCHAR(30)  NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // ── Order Items ────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB");

    // ── Favorites ──────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        UNIQUE KEY unique_fav (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // ── Reviews ────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    echo "<h2>✅ Tables created successfully!</h2>";

    // ── Seed Data ──────────────────────────────────────────────────

    // Admin user
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (name, email, password, role) VALUES
        ('Admin', 'admin@mlc.com', '$hash', 'admin')");

    // Demo vendor users
    $vendorHash = password_hash('vendor123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (name, email, password, role) VALUES
        ('Nike Store', 'nike@mlc.com', '$vendorHash', 'vendor'),
        ('Adidas Store', 'adidas@mlc.com', '$vendorHash', 'vendor'),
        ('TechWorld', 'tech@mlc.com', '$vendorHash', 'vendor'),
        ('SportsPro', 'sports@mlc.com', '$vendorHash', 'vendor')");

    // Demo customer
    $custHash = password_hash('customer123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (name, email, password, role) VALUES
        ('John Doe', 'john@mlc.com', '$custHash', 'customer')");

    // Vendors
    $pdo->exec("INSERT IGNORE INTO vendors (user_id, store_name, description) VALUES
        (2, 'Nike Official Store', 'Authentic Nike products worldwide'),
        (3, 'Adidas Official', 'Original Adidas sportswear and gear'),
        (4, 'TechWorld Electronics', 'Best electronics at best prices'),
        (5, 'SportsPro Equipment', 'Professional sports equipment')");

    // Categories
    $pdo->exec("INSERT IGNORE INTO categories (name, slug, icon) VALUES
        ('All Categories', 'all', '🛍️'),
        ('Deals', 'deals', '🏷️'),
        ('Crypto', 'crypto', '₿'),
        ('Fashion', 'fashion', '👗'),
        ('Health & Wellness', 'health-wellness', '💊'),
        ('Art', 'art', '🎨'),
        ('Home', 'home', '🏠'),
        ('Sport', 'sport', '⚽'),
        ('Music', 'music', '🎵'),
        ('Gaming', 'gaming', '🎮'),
        ('Electronics', 'electronics', '💻')");

    // Products — using picsum for placeholder images
    $products = [
        // Sport
        [1, 8, 'Smart Watch WH22-6 Fitness Tracker', 'Advanced fitness tracking smartwatch with heart rate monitor and GPS.', 454.00, null, 50, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400', 'Xiaomi', 4.5, 23, 1, 'both'],
        [4, 8, 'Tennis Rackets for Beginners', 'Lightweight aluminium tennis rackets perfect for beginners.', 30.99, null, 120, 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=400', 'Demix', 4.2, 45, 0, 'standard'],
        [4, 8, 'Premium Boxing Gloves Pro Series', 'Professional boxing gloves with superior padding and wrist support.', 196.84, 275.57, 35, 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?w=400', 'Adidas', 4.7, 67, 0, 'pickup'],
        [4, 8, 'Club Kit 1 Recurve Archery Set', 'Complete recurve archery set for club and recreational use.', 48.99, null, 20, 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400', 'Columbia', 3.8, 12, 0, 'standard'],
        [1, 8, 'Nike White Therma-Fit Pullover Hoodie', 'Premium training hoodie with Therma-FIT technology for cold weather.', 154.99, null, 80, 'https://images.unsplash.com/photo-1556821840-3a63f15732ce?w=400', 'Nike', 5.0, 89, 0, 'both'],
        [1, 8, 'Lightweight White Nike Training Shoes', 'Ultra-light Nike training shoes for maximum performance.', 210.00, null, 60, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400', 'Nike', 4.6, 134, 1, 'both'],
        // Electronics
        [3, 11, 'Macbook Air 13 256Gb', 'Apple MacBook Air with M1 chip, stunning Retina display.', 935.90, null, 15, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400', 'Apple', 4.9, 210, 1, 'standard'],
        [3, 11, 'Buds 4 Lite Black', 'True wireless earbuds with active noise cancellation.', 41.25, 55.90, 200, 'https://images.unsplash.com/photo-1606220588913-b3aacb4d2f46?w=400', 'Xiaomi', 4.3, 88, 0, 'both'],
        [3, 11, 'PlayStation 5 825GB', 'Next-gen gaming console with ultra-high-speed SSD.', 684.60, null, 8, 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=400', 'Sony', 5.0, 445, 1, 'standard'],
        [3, 11, 'Galaxy Watch4 40mm', 'Advanced smartwatch with health monitoring and LTE support.', 168.50, null, 45, 'https://images.unsplash.com/photo-1544117519-31a4b719223d?w=400', 'Samsung', 4.4, 76, 0, 'both'],
        // Fashion
        [2, 4, 'Adidas Classic Tracksuit', 'Iconic three-stripe tracksuit in premium cotton blend.', 89.99, 120.00, 100, 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=400', 'Adidas', 4.6, 55, 0, 'both'],
        [1, 4, 'Nike Air Force 1 White', 'Timeless classic Nike sneakers in crisp white leather.', 110.00, null, 70, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400', 'Nike', 4.8, 320, 1, 'standard'],
        // Home
        [3, 7, 'Minimalist Desk Lamp LED', 'Modern LED desk lamp with adjustable brightness and color temperature.', 45.00, 60.00, 150, 'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?w=400', 'New Balance', 4.2, 33, 0, 'standard'],
        [3, 7, 'Premium Bluetooth Speaker', 'Portable waterproof speaker with 360° sound and 24h battery.', 79.99, null, 85, 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400', 'Sony', 4.5, 98, 0, 'both'],
        // Gaming
        [3, 10, 'Pro Gaming Headset RGB', 'Surround sound gaming headset with noise-cancelling mic and RGB.', 65.00, 85.00, 40, 'https://images.unsplash.com/photo-1599669454699-248893623440?w=400', 'Samsung', 4.3, 67, 0, 'standard'],
        [3, 10, 'Mechanical Gaming Keyboard', 'Full RGB mechanical keyboard with tactile switches for gaming.', 129.99, null, 30, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=400', 'Asics', 4.7, 112, 1, 'both'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO products (vendor_id, category_id, name, description, price, original_price, stock, image, brand, rating, review_count, is_top_item, delivery_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($products as $p) {
        $stmt->execute($p);
    }

    echo "<h2>✅ Demo data seeded!</h2>";
    echo "<p><strong>Demo accounts:</strong></p>";
    echo "<ul>
        <li>Admin: admin@mlc.com / admin123</li>
        <li>Vendor: nike@mlc.com / vendor123</li>
        <li>Customer: john@mlc.com / customer123</li>
    </ul>";
    echo "<p><a href='index.php' style='background:#7C3AED;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;'>Go to Homepage →</a></p>";

} catch (PDOException $e) {
    echo "<h2>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
