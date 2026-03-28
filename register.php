<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Register — Venmark';
$B = BASE_URL;

if (isLoggedIn()) { header('Location: ' . $B . '/index.php'); exit; }

$error = '';
$role  = ($_GET['role'] ?? '') === 'vendor' ? 'vendor' : 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name']               ?? '');
    $email     = trim($_POST['email']              ?? '');
    $password  = $_POST['password']                ?? '';
    $confirm   = $_POST['confirm_password']        ?? '';
    $role      = in_array($_POST['role'] ?? '', ['customer','vendor']) ? $_POST['role'] : 'customer';
    $storeName = trim($_POST['store_name']         ?? '');

    if (!$name || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($role === 'vendor' && !$storeName) {
        $error = 'Store name is required for vendors.';
    } else {
        $pdo   = getDB();
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)")
                ->execute([$name, $email, $hash, $role]);
            $userId = $pdo->lastInsertId();

            if ($role === 'vendor') {
                $desc = trim($_POST['store_description'] ?? '');
                $pdo->prepare("INSERT INTO vendors (user_id, store_name, description) VALUES (?,?,?)")
                    ->execute([$userId, $storeName, $desc]);
            }
            header('Location: ' . $B . '/login.php?registered=1');
            exit;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
    <div class="auth-card" style="max-width:520px;">
        <div class="auth-logo">
            <a href="<?= $B ?>/index.php">
                <svg width="130" height="32" viewBox="0 0 160 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="lgI3" x1="0" y1="0" x2="32" y2="36" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#6D28D9"/></linearGradient>
                        <linearGradient id="lgT3" x1="0" y1="0" x2="1" y2="0"><stop offset="0%" stop-color="#7C3AED"/><stop offset="100%" stop-color="#A855F7"/></linearGradient>
                    </defs>
                    <rect x="0" y="4" width="32" height="32" rx="9" fill="url(#lgI3)"/>
                    <path d="M7.5 13L16 27L24.5 13" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="16" cy="30.5" r="1.8" fill="white" opacity="0.9"/>
                    <text x="42" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="#7C3AED">Ven</text>
                    <text x="82" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="url(#lgT3)">mark</text>
                </svg>
            </a>
        </div>
        <h1>Create Account</h1>
        <p class="auth-sub">Join thousands of shoppers and vendors on Venmark.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>I want to</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="role-btn <?= $role==='customer'?'active':'' ?>"
                         onclick="setRole('customer')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        Shop
                    </div>
                    <div class="role-btn <?= $role==='vendor'?'active':'' ?>"
                         onclick="setRole('vendor')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Sell
                    </div>
                </div>
                <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($role) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" placeholder="John Doe"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <div class="input-icon-wrap">
                        <input type="password" name="password" placeholder="Min. 6 characters" required>
                        <button type="button" class="toggle-pw" title="Show/hide">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" placeholder="Repeat password" required>
                </div>
            </div>

            <div id="vendorFields" style="display:<?= $role==='vendor'?'block':'none' ?>">
                <div class="form-group">
                    <label>Store Name *</label>
                    <input type="text" name="store_name" placeholder="My Awesome Store"
                           value="<?= htmlspecialchars($_POST['store_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Store Description</label>
                    <textarea name="store_description" rows="3"
                              placeholder="Tell customers about your store…"><?= htmlspecialchars($_POST['store_description'] ?? '') ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">Create Account</button>
        </form>

        <div class="auth-switch">
            Already have an account? <a href="<?= $B ?>/login.php">Sign in</a>
        </div>
    </div>
</main>

<script>
function setRole(r) {
    document.getElementById('roleInput').value = r;
    document.getElementById('vendorFields').style.display = r === 'vendor' ? 'block' : 'none';
    document.querySelectorAll('.role-btn').forEach(el => el.classList.remove('active'));
    event.currentTarget.classList.add('active');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
