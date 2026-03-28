<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '11655Usscsao');
define('DB_NAME', 'multivendor_db');

// ── Auto-detect base URL (works for built-in server + XAMPP subdirectory) ──
if (!defined('BASE_URL')) {
    $docRoot  = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? getcwd())), '/');
    $projRoot = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..')), '/');
    $rel      = ($docRoot && strpos($projRoot, $docRoot) === 0)
                ? substr($projRoot, strlen($docRoot))
                : '';
    define('BASE_URL', rtrim($rel, '/'));   // '' for root, '/MultiVendorMarketplace' for XAMPP sub
}

// ── Currency helper (1 USD ≈ 655 XAF / FCFA) ─────────────
if (!function_exists('fcfa')) {
    function fcfa(float $amount): string {
        $xaf = (int)round($amount * 655);
        return number_format($xaf, 0, ',', "\u{00A0}") . '&nbsp;FCFA';
    }
    // Plain text version (no HTML entities) for use in JS data
    function fcfa_raw(float $amount): string {
        $xaf = (int)round($amount * 655);
        return number_format($xaf, 0, ',', ' ') . ' FCFA';
    }
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // If DB doesn't exist yet, forward to setup
            header('Location: ' . BASE_URL . '/setup.php');
            exit;
        }
    }
    return $pdo;
}
