<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Create Account — Venmark';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif}
a{text-decoration:none;color:inherit}
button{cursor:pointer;font-family:inherit}
input,textarea{font-family:inherit}

/* ── Layout ── */
.reg-page {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
    overflow: hidden;
}

/* ── LEFT PANEL ── */
.reg-left {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 60px 64px;
    background: linear-gradient(135deg, #0f0720 0%, #2d0b5e 40%, #7C3AED 100%);
    overflow: hidden;
}
.reg-left::before {
    content:'';
    position:absolute;inset:0;
    background: linear-gradient(120deg,#0f0720,#2d0b5e,#7C3AED,#3b1090,#0f0720);
    background-size:400% 400%;
    animation: meshShift 10s ease infinite;
    z-index:0;
}
@keyframes meshShift{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}

.orb{position:absolute;border-radius:50%;filter:blur(60px);animation:orbFloat var(--dur,12s) ease-in-out infinite;opacity:0.45;pointer-events:none;z-index:1;}
.orb1{width:380px;height:380px;background:#7C3AED;top:-80px;right:-80px;--dur:14s}
.orb2{width:260px;height:260px;background:#A855F7;bottom:60px;left:-60px;--dur:10s;animation-delay:-4s}
.orb3{width:180px;height:180px;background:#6D28D9;top:40%;left:40%;--dur:8s;animation-delay:-2s}
@keyframes orbFloat{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(-24px,30px) scale(1.06)}66%{transform:translate(18px,-20px) scale(0.95)}}

.reg-left::after{content:'';position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");opacity:0.5;z-index:1;pointer-events:none;}

.reg-left-content{position:relative;z-index:2;animation:slideInLeft .8s cubic-bezier(.22,1,.36,1) both;}
@keyframes slideInLeft{from{opacity:0;transform:translateX(-40px)}to{opacity:1;transform:none}}

.left-logo{margin-bottom:44px;display:inline-block;}

.left-headline{font-size:clamp(1.9rem,3.2vw,2.8rem);font-weight:900;color:white;line-height:1.12;letter-spacing:-1.5px;margin-bottom:18px;}
.left-headline span{background:linear-gradient(90deg,#E9D5FF,#A855F7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}

.left-sub{font-size:1rem;color:rgba(255,255,255,0.65);line-height:1.65;max-width:340px;margin-bottom:40px;}

/* Perks list */
.reg-perks{display:flex;flex-direction:column;gap:14px;margin-bottom:44px;}
.reg-perk{display:flex;align-items:center;gap:14px;}
.reg-perk-icon{width:40px;height:40px;border-radius:11px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.reg-perk-text strong{display:block;font-size:.88rem;font-weight:700;color:white;}
.reg-perk-text span{font-size:.78rem;color:rgba(255,255,255,.55);}

/* Trust badge */
.reg-trust{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:14px;padding:14px 18px;backdrop-filter:blur(12px);max-width:360px;}
.reg-trust-avatars{display:flex;}
.reg-trust-avatars span{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#7C3AED,#A855F7);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:white;margin-left:-8px;}
.reg-trust-avatars span:first-child{margin-left:0;}
.reg-trust-text strong{display:block;font-size:.82rem;font-weight:700;color:white;}
.reg-trust-text span{font-size:.72rem;color:rgba(255,255,255,.55);}

/* ── RIGHT PANEL ── */
.reg-right{display:flex;align-items:center;justify-content:center;padding:40px 40px;background:#ffffff;overflow-y:auto;}

.reg-card{width:100%;max-width:440px;animation:cardIn .7s cubic-bezier(.22,1,.36,1) both .1s;}
@keyframes cardIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}

/* Header */
.reg-header{margin-bottom:28px;}
.reg-header .logo-link{display:inline-block;margin-bottom:24px;}
.reg-header h1{font-size:1.65rem;font-weight:800;color:#111827;letter-spacing:-.6px;margin-bottom:6px;}
.reg-header p{color:#6B7280;font-size:.88rem;line-height:1.5;}

/* Account type toggle */
.type-label{display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:8px;letter-spacing:.2px;}
.type-toggle{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px;}
.type-btn{padding:13px 16px;border:1.5px solid #E5E7EB;border-radius:12px;background:#ffffff;color:#6B7280;font-size:.88rem;font-weight:600;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;}
.type-btn:hover{border-color:#7C3AED;color:#7C3AED;background:#F5F3FF;}
.type-btn.active{border-color:#7C3AED;background:#7C3AED;color:#ffffff;box-shadow:0 4px 14px rgba(124,58,237,.3);}
.type-btn.active svg{stroke:white;}

/* Form row */
.form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}

/* Fields */
.field{margin-bottom:16px;}
.field-label{display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:6px;letter-spacing:.2px;}
.field-wrap{position:relative;}
.field-wrap input,
.field-wrap textarea{width:100%;padding:12px 16px;border:1.5px solid #E5E7EB;border-radius:12px;font-size:.9rem;background:#ffffff;color:#111827;outline:none;transition:border-color .2s,box-shadow .2s;box-sizing:border-box;}
.field-wrap input{height:48px;}
.field-wrap textarea{height:88px;resize:none;padding-top:12px;}
.field-wrap input::placeholder,
.field-wrap textarea::placeholder{color:#9CA3AF;}
.field-wrap input:focus,
.field-wrap textarea:focus{border-color:#7C3AED;box-shadow:0 0 0 3px rgba(124,58,237,.12);}
.field-wrap input.has-eye{padding-right:44px;}
.field-eye{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9CA3AF;display:flex;align-items:center;padding:4px;border-radius:6px;transition:color .2s;cursor:pointer;}
.field-eye:hover{color:#7C3AED;}

/* Vendor fields slide */
#vendorFields{overflow:hidden;transition:max-height .35s ease,opacity .3s ease;max-height:0;opacity:0;}
#vendorFields.open{max-height:300px;opacity:1;}

/* Divider */
.vendor-divider{display:flex;align-items:center;gap:10px;margin:4px 0 16px;font-size:.75rem;font-weight:600;color:#7C3AED;letter-spacing:.3px;}
.vendor-divider::before,.vendor-divider::after{content:'';flex:1;height:1px;background:#EDE9FE;}

/* Submit */
.btn-register{width:100%;padding:14px;background:#7C3AED;color:white;border:none;border-radius:12px;font-size:.95rem;font-weight:700;cursor:pointer;transition:all .22s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;letter-spacing:.1px;}
.btn-register:hover{background:#6D28D9;box-shadow:0 6px 20px rgba(124,58,237,.35);transform:translateY(-1px);}
.btn-register:active{transform:none;box-shadow:none;}

/* Alert */
.alert{border-radius:10px;padding:11px 14px;font-size:.85rem;font-weight:500;margin-bottom:18px;display:flex;align-items:center;gap:9px;}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;color:#DC2626;}

/* Switch */
.auth-switch{text-align:center;margin-top:20px;font-size:.85rem;color:#6B7280;}
.auth-switch a{color:#7C3AED;font-weight:700;transition:color .2s;}
.auth-switch a:hover{color:#5B21B6;}

/* Terms */
.terms-note{font-size:.75rem;color:#9CA3AF;text-align:center;margin-top:12px;line-height:1.5;}
.terms-note a{color:#7C3AED;}

/* Mobile */
@media (max-width:768px){
    .reg-page{grid-template-columns:1fr;}
    .reg-left{display:none;}
    .reg-right{padding:40px 24px;min-height:100vh;}
    .reg-card{max-width:100%;}
    .form-row-2{grid-template-columns:1fr;}
}
</style>
</head>
<body>

<div class="reg-page">

    <!-- LEFT PANEL -->
    <div class="reg-left">
        <div class="orb orb1"></div>
        <div class="orb orb2"></div>
        <div class="orb orb3"></div>

        <div class="reg-left-content">
            <a href="<?= $B ?>/index.php" class="left-logo">
                <svg width="150" height="36" viewBox="0 0 160 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="rlIconG" x1="0" y1="0" x2="32" y2="36" gradientUnits="userSpaceOnUse">
                            <stop offset="0%" stop-color="#C4B5FD"/><stop offset="100%" stop-color="#7C3AED"/>
                        </linearGradient>
                    </defs>
                    <rect x="0" y="4" width="32" height="32" rx="9" fill="url(#rlIconG)"/>
                    <path d="M7.5 13L16 27L24.5 13" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="16" cy="30.5" r="1.8" fill="white" opacity="0.9"/>
                    <text x="42" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="white">Ven</text>
                    <text x="82" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="rgba(255,255,255,0.65)">mark</text>
                </svg>
            </a>

            <h1 class="left-headline">
                Start your journey<br>
                <span>with Venmark.</span>
            </h1>
            <p class="left-sub">
                Join a growing community of shoppers and sellers in Cameroon. Free to join, easy to use.
            </p>

            <div class="reg-perks">
                <div class="reg-perk">
                    <div class="reg-perk-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    </div>
                    <div class="reg-perk-text">
                        <strong>Free to join</strong>
                        <span>No setup fees, no hidden charges</span>
                    </div>
                </div>
                <div class="reg-perk">
                    <div class="reg-perk-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <div class="reg-perk-text">
                        <strong>Fast delivery</strong>
                        <span>Get products to your door in 24–48h</span>
                    </div>
                </div>
                <div class="reg-perk">
                    <div class="reg-perk-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div class="reg-perk-text">
                        <strong>Secure payments</strong>
                        <span>MTN MoMo &amp; Orange Money protected</span>
                    </div>
                </div>
                <div class="reg-perk">
                    <div class="reg-perk-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    </div>
                    <div class="reg-perk-text">
                        <strong>Easy returns</strong>
                        <span>30-day hassle-free return policy</span>
                    </div>
                </div>
            </div>

            <div class="reg-trust">
                <div class="reg-trust-avatars">
                    <span>JD</span><span>MC</span><span>AB</span><span>+</span>
                </div>
                <div class="reg-trust-text" style="margin-left:6px;">
                    <strong>2,000+ members</strong>
                    <span>Already shopping &amp; selling on Venmark</span>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="reg-right">
        <div class="reg-card">
            <div class="reg-header">
                <a href="<?= $B ?>/index.php" class="logo-link">
                    <svg width="130" height="32" viewBox="0 0 160 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="rrIconG" x1="0" y1="0" x2="32" y2="36" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#6D28D9"/></linearGradient>
                            <linearGradient id="rrTextG" x1="0" y1="0" x2="1" y2="0"><stop offset="0%" stop-color="#7C3AED"/><stop offset="100%" stop-color="#A855F7"/></linearGradient>
                        </defs>
                        <rect x="0" y="4" width="32" height="32" rx="9" fill="url(#rrIconG)"/>
                        <path d="M7.5 13L16 27L24.5 13" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="16" cy="30.5" r="1.8" fill="white" opacity="0.9"/>
                        <text x="42" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="#7C3AED">Ven</text>
                        <text x="82" y="28" font-family="Inter,-apple-system,sans-serif" font-weight="800" font-size="21" letter-spacing="-0.5" fill="url(#rrTextG)">mark</text>
                    </svg>
                </a>
                <h1>Create your account</h1>
                <p>Free forever. No credit card required.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="3"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="regForm">
                <!-- Account type -->
                <span class="type-label">I want to</span>
                <div class="type-toggle">
                    <button type="button" class="type-btn <?= $role==='customer'?'active':'' ?>" id="btnCustomer" onclick="setRole('customer')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        Shop
                    </button>
                    <button type="button" class="type-btn <?= $role==='vendor'?'active':'' ?>" id="btnVendor" onclick="setRole('vendor')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Sell
                    </button>
                </div>
                <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($role) ?>">

                <!-- Name + Email -->
                <div class="form-row-2">
                    <div class="field">
                        <label class="field-label" for="nameInput">Full name</label>
                        <div class="field-wrap">
                            <input type="text" name="name" id="nameInput" placeholder="John Doe"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="field">
                        <label class="field-label" for="emailInput">Email address</label>
                        <div class="field-wrap">
                            <input type="email" name="email" id="emailInput" placeholder="you@example.com"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Password + Confirm -->
                <div class="form-row-2">
                    <div class="field">
                        <label class="field-label" for="pwInput">Password</label>
                        <div class="field-wrap">
                            <input type="password" name="password" id="pwInput"
                                   class="has-eye" placeholder="Min. 6 characters" required>
                            <button type="button" class="field-eye" onclick="togglePw('pwInput','eyeOpen1','eyeClosed1')">
                                <svg id="eyeOpen1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg id="eyeClosed1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="field">
                        <label class="field-label" for="confirmInput">Confirm password</label>
                        <div class="field-wrap">
                            <input type="password" name="confirm_password" id="confirmInput"
                                   class="has-eye" placeholder="Repeat password" required>
                            <button type="button" class="field-eye" onclick="togglePw('confirmInput','eyeOpen2','eyeClosed2')">
                                <svg id="eyeOpen2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg id="eyeClosed2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Vendor fields -->
                <div id="vendorFields" class="<?= $role==='vendor'?'open':'' ?>">
                    <div class="vendor-divider">Store details</div>
                    <div class="field">
                        <label class="field-label" for="storeNameInput">Store name</label>
                        <div class="field-wrap">
                            <input type="text" name="store_name" id="storeNameInput"
                                   placeholder="My Awesome Store"
                                   value="<?= htmlspecialchars($_POST['store_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="field">
                        <label class="field-label" for="storeDescInput">Store description <span style="color:#9CA3AF;font-weight:400;">(optional)</span></label>
                        <div class="field-wrap">
                            <textarea name="store_description" id="storeDescInput"
                                      placeholder="Tell customers what you sell…"><?= htmlspecialchars($_POST['store_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-register" id="regBtn">
                    <span id="regBtnText">Create Account</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16" stroke-linecap="round" stroke-linejoin="round" id="regArrow"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>

            <div class="terms-note">
                By creating an account you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
            </div>

            <div class="auth-switch">
                Already have an account? <a href="<?= $B ?>/login.php">Sign in</a>
            </div>
        </div>
    </div>
</div>

<script>
function setRole(r) {
    document.getElementById('roleInput').value = r;
    document.getElementById('btnCustomer').classList.toggle('active', r === 'customer');
    document.getElementById('btnVendor').classList.toggle('active', r === 'vendor');
    const vf = document.getElementById('vendorFields');
    vf.classList.toggle('open', r === 'vendor');
}

function togglePw(inputId, openId, closedId) {
    const pw = document.getElementById(inputId);
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    document.getElementById(openId).style.display   = show ? 'none' : '';
    document.getElementById(closedId).style.display = show ? ''     : 'none';
}

document.getElementById('regForm').addEventListener('submit', function() {
    const btn  = document.getElementById('regBtn');
    const text = document.getElementById('regBtnText');
    btn.disabled = true;
    text.textContent = 'Creating account…';
});
</script>

<?php
$B = BASE_URL;
echo "<script>window.MLC_BASE = '$B';</script>";
echo "<script src=\"$B/assets/js/main.js\"></script>";
?>
</body>
</html>
