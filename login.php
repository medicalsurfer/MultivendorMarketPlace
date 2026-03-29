<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Login — Venmark';
$B = BASE_URL;

if (isLoggedIn()) { header('Location: ' . $B . '/index.php'); exit; }

$error    = '';
$redirect = $_GET['redirect'] ?? ($B . '/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']       ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['avatar']     = $user['avatar'];
            header('Location: ' . ($redirect ?: $B . '/index.php'));
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ── Reset ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif}
a{text-decoration:none;color:inherit}
button{cursor:pointer;font-family:inherit}
input{font-family:inherit}

/* ── Full-page layout ── */
.login-page {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
    position: relative;
    overflow: hidden;
}

/* ── LEFT PANEL ── */
.login-left {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 60px 64px;
    background: linear-gradient(135deg, #0f0720 0%, #2d0b5e 40%, #7C3AED 100%);
    overflow: hidden;
}

/* animated gradient mesh */
.login-left::before {
    content:'';
    position:absolute;inset:0;
    background: linear-gradient(120deg,#0f0720,#2d0b5e,#7C3AED,#3b1090,#0f0720);
    background-size:400% 400%;
    animation: meshShift 10s ease infinite;
    z-index:0;
}
@keyframes meshShift {
    0%,100%{background-position:0% 50%}
    50%{background-position:100% 50%}
}

/* floating orbs */
.orb {
    position:absolute;border-radius:50%;filter:blur(60px);
    animation:orbFloat var(--dur,12s) ease-in-out infinite;
    opacity:0.45;pointer-events:none;z-index:1;
}
.orb1{width:380px;height:380px;background:#7C3AED;top:-80px;right:-80px;--dur:14s}
.orb2{width:260px;height:260px;background:#A855F7;bottom:60px;left:-60px;--dur:10s;animation-delay:-4s}
.orb3{width:180px;height:180px;background:#6D28D9;top:40%;left:40%;--dur:8s;animation-delay:-2s}
@keyframes orbFloat {
    0%,100%{transform:translate(0,0) scale(1)}
    33%{transform:translate(-24px,30px) scale(1.06)}
    66%{transform:translate(18px,-20px) scale(0.95)}
}

/* noise texture overlay */
.login-left::after {
    content:'';position:absolute;inset:0;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    opacity:0.5;z-index:1;pointer-events:none;
}

.login-left-content {
    position:relative;z-index:2;
    animation: slideInLeft .8s cubic-bezier(.22,1,.36,1) both;
}
@keyframes slideInLeft{from{opacity:0;transform:translateX(-40px)}to{opacity:1;transform:none}}

.left-logo { margin-bottom:48px; display:inline-block; }

.left-headline {
    font-size:clamp(2rem,3.5vw,3rem);
    font-weight:900;
    color:white;
    line-height:1.1;
    letter-spacing:-1.5px;
    margin-bottom:20px;
}
.left-headline span {
    background:linear-gradient(90deg,#E9D5FF,#A855F7);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;
    background-clip:text;
}

.left-sub {
    font-size:1.05rem;
    color:rgba(255,255,255,0.65);
    line-height:1.65;
    max-width:360px;
    margin-bottom:48px;
}

/* Stats row */
.left-stats {
    display:flex;gap:32px;flex-wrap:wrap;margin-bottom:48px;
}
.left-stat {display:flex;flex-direction:column;gap:4px}
.left-stat-num{font-size:1.6rem;font-weight:800;color:white;line-height:1}
.left-stat-label{font-size:.72rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.8px}
.left-stat-divider{width:1px;height:40px;background:rgba(255,255,255,.15);align-self:center}

/* Testimonial */
.left-testimonial {
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.14);
    border-radius:16px;
    padding:20px 22px;
    backdrop-filter:blur(12px);
    max-width:380px;
}
.left-testimonial p {
    font-size:.9rem;color:rgba(255,255,255,.8);
    line-height:1.6;font-style:italic;margin-bottom:14px;
}
.left-testimonial-author{display:flex;align-items:center;gap:10px}
.left-avatar{
    width:34px;height:34px;border-radius:50%;
    background:linear-gradient(135deg,#7C3AED,#A855F7);
    display:flex;align-items:center;justify-content:center;
    font-size:.85rem;font-weight:700;color:white;flex-shrink:0;
}
.left-author-info strong{display:block;font-size:.82rem;color:white;font-weight:700}
.left-author-info span{font-size:.72rem;color:rgba(255,255,255,.5)}

/* Decorative floating cards */
.deco-cards {
    position:absolute;right:40px;top:50%;transform:translateY(-50%);
    display:flex;flex-direction:column;gap:12px;z-index:2;
    animation: decoFloat 6s ease-in-out infinite;
}
@keyframes decoFloat{0%,100%{transform:translateY(-50%) translateX(0)}50%{transform:translateY(calc(-50% - 12px)) translateX(-6px)}}
.deco-card{
    background:rgba(255,255,255,.1);
    border:1px solid rgba(255,255,255,.2);
    border-radius:14px;padding:14px 18px;backdrop-filter:blur(16px);
    color:white;display:flex;align-items:center;gap:12px;width:200px;
}
.deco-card-icon{
    width:36px;height:36px;border-radius:10px;
    background:rgba(255,255,255,.18);
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.deco-card-text strong{display:block;font-size:.8rem;font-weight:700}
.deco-card-text span{font-size:.7rem;opacity:.65}
.deco-card:nth-child(2){transform:translateX(24px)}
.deco-card:nth-child(3){transform:translateX(8px)}

/* ── RIGHT PANEL ── */
.login-right {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 40px;
    background: linear-gradient(145deg, #f5f0ff 0%, #faf8ff 35%, #eef2ff 70%, #f8f5ff 100%);
    background-size: 400% 400%;
    animation: rightBgShift 14s ease infinite;
    position: relative;
    overflow: hidden;
}
@keyframes rightBgShift {
    0%,100% { background-position: 0% 50%; }
    50%      { background-position: 100% 50%; }
}

/* Subtle dot grid */
.login-right::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(124,58,237,0.07) 1px, transparent 1px);
    background-size: 28px 28px;
    pointer-events: none;
    z-index: 0;
}

/* Right panel soft orbs */
.right-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(70px);
    pointer-events: none;
    z-index: 0;
}
.right-orb1 {
    width: 320px; height: 320px;
    background: rgba(124,58,237,0.10);
    top: -80px; right: -80px;
    animation: rightOrb1 12s ease-in-out infinite;
}
.right-orb2 {
    width: 200px; height: 200px;
    background: rgba(168,85,247,0.09);
    bottom: -40px; left: -40px;
    animation: rightOrb1 9s ease-in-out infinite reverse;
}
.right-orb3 {
    width: 150px; height: 150px;
    background: rgba(99,102,241,0.07);
    top: 50%; left: 50%;
    animation: rightOrb1 16s ease-in-out infinite 3s;
}
@keyframes rightOrb1 {
    0%,100% { transform: translate(0,0) scale(1); }
    40%     { transform: translate(-20px, 25px) scale(1.08); }
    70%     { transform: translate(15px,-18px) scale(0.93); }
}

.login-card {
    width: 100%;
    max-width: 400px;
    position: relative;
    z-index: 1;
    animation: cardIn .75s cubic-bezier(.22,1,.36,1) both .1s;
}
@keyframes cardIn { from{opacity:0;transform:translateY(24px) scale(0.98)} to{opacity:1;transform:none} }

.card-glass {
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(24px) saturate(160%);
    -webkit-backdrop-filter: blur(24px) saturate(160%);
    border: 1.5px solid rgba(255,255,255,0.9);
    border-radius: 24px;
    padding: 40px 36px;
    box-shadow:
        0 8px 40px rgba(124,58,237,0.10),
        0 1px 0 rgba(255,255,255,0.9) inset,
        0 -1px 0 rgba(124,58,237,0.06) inset;
}

/* Staggered field entrance */
.card-header  { animation: fieldIn .6s cubic-bezier(.22,1,.36,1) both .15s; }
.field:nth-child(1) { animation: fieldIn .6s cubic-bezier(.22,1,.36,1) both .25s; }
.field:nth-child(2) { animation: fieldIn .6s cubic-bezier(.22,1,.36,1) both .35s; }
.btn-login    { animation: fieldIn .6s cubic-bezier(.22,1,.36,1) both .42s; }
.divider      { animation: fieldIn .6s cubic-bezier(.22,1,.36,1) both .48s; }
.demo-box     { animation: fieldIn .6s cubic-bezier(.22,1,.36,1) both .54s; }
.auth-switch  { animation: fieldIn .6s cubic-bezier(.22,1,.36,1) both .60s; }
@keyframes fieldIn { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:none} }

/* Input glass style */
.field-wrap input {
    background: rgba(255,255,255,0.8) !important;
    border: 1.5px solid rgba(124,58,237,0.12) !important;
    backdrop-filter: blur(8px);
    transition: border-color .2s, box-shadow .2s, background .2s !important;
}
.field-wrap input:focus {
    background: rgba(255,255,255,0.97) !important;
    border-color: #7C3AED !important;
    box-shadow: 0 0 0 4px rgba(124,58,237,0.12), 0 2px 12px rgba(124,58,237,0.08) !important;
}

/* Demo box glass */
.demo-box {
    background: rgba(255,255,255,0.6) !important;
    border: 1.5px solid rgba(124,58,237,0.10) !important;
    backdrop-filter: blur(8px);
}
.demo-chip {
    background: rgba(255,255,255,0.8) !important;
}

/* Mobile override */
@media (max-width: 768px) {
    .login-right { padding: 40px 20px; overflow-y: auto; }
    .card-glass  { padding: 28px 20px; border-radius: 20px; }
}

/* ── Card header ── */
.card-header { margin-bottom: 36px; }
.card-header .logo-link { display: inline-block; margin-bottom: 28px; }
.card-header h1 {
    font-size: 1.75rem;
    font-weight: 800;
    color: #111827;
    letter-spacing: -.6px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.card-header p { color: #6B7280; font-size: .9rem; line-height: 1.55; }

/* ── Static label + input ── */
.field {
    margin-bottom: 18px;
}
.field-label {
    display: block;
    font-size: .78rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
    letter-spacing: .2px;
}
.field-wrap {
    position: relative;
}
.field-wrap input {
    width: 100%;
    padding: 13px 16px;
    border: 1.5px solid #E5E7EB;
    border-radius: 12px;
    font-size: .92rem;
    font-weight: 400;
    background: #ffffff;
    color: #111827;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    box-sizing: border-box;
    height: 50px;
}
.field-wrap input::placeholder { color: #9CA3AF; }
.field-wrap input:focus {
    border-color: #7C3AED;
    box-shadow: 0 0 0 3px rgba(124,58,237,.12);
}
.field-eye {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #9CA3AF;
    display: flex;
    align-items: center;
    padding: 4px;
    border-radius: 6px;
    transition: color .2s;
    cursor: pointer;
}
.field-eye:hover { color: #7C3AED; }
.field-wrap input.has-eye { padding-right: 44px; }

/* ── Submit button ── */
.btn-login {
    width: 100%;
    padding: 14px;
    background: #7C3AED;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: .95rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .22s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 6px;
    letter-spacing: .1px;
}
.btn-login:hover { background: #6D28D9; box-shadow: 0 6px 20px rgba(124,58,237,.35); transform: translateY(-1px); }
.btn-login:active { transform: translateY(0); box-shadow: none; }

/* ── Divider ── */
.divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 22px 0;
    color: #9CA3AF;
    font-size: .78rem;
    font-weight: 500;
}
.divider::before,.divider::after { content:''; flex:1; height:1px; background:#F3F4F6; }

/* ── Demo box ── */
.demo-box {
    background: #FAFAFA;
    border: 1.5px solid #F3F4F6;
    border-radius: 14px;
    padding: 16px 18px;
    margin-top: 4px;
}
.demo-box strong {
    display: block;
    font-size: .8rem;
    font-weight: 700;
    color: #374151;
    margin-bottom: 10px;
}
.demo-row { display: flex; gap: 10px; flex-wrap: wrap; }
.demo-chip {
    flex: 1;
    min-width: 110px;
    background: #ffffff;
    color: #374151;
    font-size: .82rem;
    font-weight: 600;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1.5px solid #E5E7EB;
    cursor: pointer;
    transition: all .2s;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
}
.demo-chip:hover {
    border-color: #7C3AED;
    color: #7C3AED;
    background: #F5F3FF;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(124,58,237,.12);
}
.demo-chip svg { flex-shrink: 0; }

/* ── Switch link ── */
.auth-switch {
    text-align: center;
    margin-top: 22px;
    font-size: .85rem;
    color: #6B7280;
}
.auth-switch a { color: #7C3AED; font-weight: 700; transition: color .2s; }
.auth-switch a:hover { color: #5B21B6; }

/* ── Alert ── */
.alert {
    border-radius: 10px;
    padding: 11px 14px;
    font-size: .85rem;
    font-weight: 500;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 9px;
}
.alert-error   { background: #FEF2F2; border: 1px solid #FECACA; color: #DC2626; }
.alert-success { background: #F0FDF4; border: 1px solid #BBF7D0; color: #16A34A; }

/* ── Mobile ── */
@media (max-width: 768px) {
    .login-page  { grid-template-columns: 1fr; }
    .login-left  { display: none; }
    .login-right { padding: 40px 24px; background: #ffffff; min-height: 100vh; }
    .login-card  { max-width: 100%; }
}
</style>
</head>
<body>

<div class="login-page">

    <!-- ── LEFT PANEL ── -->
    <div class="login-left">
        <div class="orb orb1"></div>
        <div class="orb orb2"></div>
        <div class="orb orb3"></div>

        <div class="login-left-content">
            <!-- Logo -->
            <a href="<?= $B ?>/index.php" class="left-logo">
                <svg width="150" height="36" viewBox="0 0 160 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="llIconG" x1="0" y1="0" x2="32" y2="36" gradientUnits="userSpaceOnUse">
                            <stop offset="0%" stop-color="#C4B5FD"/><stop offset="100%" stop-color="#7C3AED"/>
                        </linearGradient>
                    </defs>
                    <rect x="0" y="4" width="32" height="32" rx="9" fill="url(#llIconG)"/>
                    <path d="M7.5 13L16 27L24.5 13" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="16" cy="30.5" r="1.8" fill="white" opacity="0.9"/>
                    <text x="42" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="white">Ven</text>
                    <text x="82" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="rgba(255,255,255,0.65)">mark</text>
                </svg>
            </a>

            <!-- Headline -->
            <h1 class="left-headline">
                Your marketplace,<br>
                <span>your rules.</span>
            </h1>
            <p class="left-sub">
                Join thousands of shoppers and vendors on Venmark — the smartest way to buy and sell in Cameroon.
            </p>

            <!-- Stats -->
            <div class="left-stats">
                <?php
                    $pdo = getDB();
                    $productCount = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                    $vendorCount  = (int)$pdo->query("SELECT COUNT(*) FROM vendors")->fetchColumn();
                ?>
                <div class="left-stat">
                    <span class="left-stat-num"><?= $productCount ?>+</span>
                    <span class="left-stat-label">Products</span>
                </div>
                <div class="left-stat-divider"></div>
                <div class="left-stat">
                    <span class="left-stat-num"><?= $vendorCount ?>+</span>
                    <span class="left-stat-label">Vendors</span>
                </div>
                <div class="left-stat-divider"></div>
                <div class="left-stat">
                    <span class="left-stat-num">100%</span>
                    <span class="left-stat-label">Secure</span>
                </div>
            </div>

            <!-- Testimonial -->
            <div class="left-testimonial">
                <p>"Venmark made it so easy to find exactly what I needed. The deals are incredible and delivery was super fast!"</p>
                <div class="left-testimonial-author">
                    <div class="left-avatar">JD</div>
                    <div class="left-author-info">
                        <strong>Jean Dupont</strong>
                        <span>Verified Customer · Yaoundé</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating deco cards -->
        <div class="deco-cards">
            <div class="deco-card">
                <div class="deco-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="18" height="18"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div class="deco-card-text">
                    <strong>Secure</strong>
                    <span>256-bit SSL</span>
                </div>
            </div>
            <div class="deco-card">
                <div class="deco-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="18" height="18"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </div>
                <div class="deco-card-text">
                    <strong>Fast Delivery</strong>
                    <span>24–48 hours</span>
                </div>
            </div>
            <div class="deco-card">
                <div class="deco-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="18" height="18"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                </div>
                <div class="deco-card-text">
                    <strong>Easy Returns</strong>
                    <span>30-day policy</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── RIGHT PANEL ── -->
    <div class="login-right">
        <div class="login-card">
            <div class="card-glass">
                <div class="card-header">
                    <a href="<?= $B ?>/index.php" class="logo-link">
                        <svg width="130" height="32" viewBox="0 0 160 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="lgI2" x1="0" y1="0" x2="32" y2="36" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#6D28D9"/></linearGradient>
                                <linearGradient id="lgT2" x1="0" y1="0" x2="1" y2="0"><stop offset="0%" stop-color="#7C3AED"/><stop offset="100%" stop-color="#A855F7"/></linearGradient>
                            </defs>
                            <rect x="0" y="4" width="32" height="32" rx="9" fill="url(#lgI2)"/>
                            <path d="M7.5 13L16 27L24.5 13" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="16" cy="30.5" r="1.8" fill="white" opacity="0.9"/>
                            <text x="42" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="#7C3AED">Ven</text>
                            <text x="82" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="url(#lgT2)">mark</text>
                        </svg>
                    </a>
                    <h1>Welcome back
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="28" height="28" style="vertical-align:middle;margin-left:6px;color:#7C3AED" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                    </h1>
                    <p>Sign in to continue your shopping journey</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="3"/></svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                        Account created! Please sign in.
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                    <!-- Email -->
                    <div class="field">
                        <label class="field-label" for="emailInput">Email address</label>
                        <div class="field-wrap">
                            <input type="email" name="email" id="emailInput"
                                   placeholder="you@example.com"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   required autocomplete="email">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="field">
                        <label class="field-label" for="pwInput">Password</label>
                        <div class="field-wrap">
                            <input type="password" name="password" id="pwInput"
                                   class="has-eye" placeholder="••••••••"
                                   required autocomplete="current-password">
                            <button type="button" class="field-eye" id="eyeBtn"
                                    title="Show/hide password" onclick="togglePw()">
                                <svg id="eyeOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg id="eyeClosed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">
                        <span id="loginBtnText">Sign In</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16" stroke-linecap="round" stroke-linejoin="round" id="loginArrow"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </form>

                <div class="divider">or</div>

                <!-- Demo accounts -->
                <div class="demo-box">
                    <strong>Try a demo account</strong>
                    <div class="demo-row">
                        <button class="demo-chip" onclick="fillDemo('democustomer@gmail.com','customer123')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Customer
                        </button>
                        <button class="demo-chip" onclick="fillDemo('demovendor@gmail.com','vendor123')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9h14l-2-9"/><circle cx="10" cy="22" r="1"/><circle cx="17" cy="22" r="1"/></svg>
                            Vendor
                        </button>
                    </div>
                </div>

                <div class="auth-switch">
                    Don't have an account? <a href="<?= $B ?>/register.php">Create one free</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePw() {
    const pw = document.getElementById('pwInput');
    const open = document.getElementById('eyeOpen');
    const closed = document.getElementById('eyeClosed');
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    open.style.display   = show ? 'none'  : '';
    closed.style.display = show ? ''      : 'none';
}

function fillDemo(email, pw) {
    document.getElementById('emailInput').value = email;
    document.getElementById('pwInput').value = pw;
    document.getElementById('emailInput').focus();
}

document.getElementById('loginForm').addEventListener('submit', function() {
    const btn  = document.getElementById('loginBtn');
    const text = document.getElementById('loginBtnText');
    const arrow = document.getElementById('loginArrow');
    btn.disabled = true;
    text.textContent = 'Signing in…';
    arrow.innerHTML = '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="31.4" stroke-dashoffset="31.4" style="animation:spin .7s linear infinite"><animate attributeName="stroke-dashoffset" values="31.4;0;31.4" dur=".8s" repeatCount="indefinite"/></circle>';
});
</script>

<?php
// Minimal footer — just scripts
$B = BASE_URL;
echo "<script>window.MLC_BASE = '$B';</script>";
echo "<script src=\"$B/assets/js/main.js\"></script>";
?>
</body>
</html>
