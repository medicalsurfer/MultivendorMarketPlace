<?php
// ═══════════════════════════════════════════════════════════
//   Venmark — Campay Payment Configuration (EXAMPLE)
//   Copy this file to config/payment.php and fill in your keys
//   Free registration: https://campay.net/
// ═══════════════════════════════════════════════════════════

define('CAMPAY_USERNAME', 'YOUR_CAMPAY_USERNAME');
define('CAMPAY_PASSWORD', 'YOUR_CAMPAY_PASSWORD');
define('CAMPAY_ENV',      'sandbox');   // 'sandbox' | 'live'
define('CAMPAY_CURRENCY', 'XAF');

function campay_base(): string {
    return CAMPAY_ENV === 'live'
        ? 'https://campay.net/api/'
        : 'https://demo.campay.net/api/';
}

function campay_token(): ?string {
    $ch = curl_init(campay_base() . 'token/');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode([
            'username' => CAMPAY_USERNAME,
            'password' => CAMPAY_PASSWORD,
        ]),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp, true);
    return $data['token'] ?? null;
}

function campay_collect(string $token, int $amountXaf, string $phone, string $description, string $externalRef): array {
    $ch = curl_init(campay_base() . 'collect/');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Token ' . $token,
        ],
        CURLOPT_POSTFIELDS     => json_encode([
            'amount'             => (string)$amountXaf,
            'currency'           => CAMPAY_CURRENCY,
            'from'               => preg_replace('/\D/', '', $phone),
            'description'        => $description,
            'external_reference' => $externalRef,
        ]),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err) return ['error' => $err];
    return json_decode($resp, true) ?? ['error' => 'Invalid response'];
}

function campay_status(string $token, string $reference): array {
    $ch = curl_init(campay_base() . 'transaction/' . urlencode($reference) . '/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Token ' . $token],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err) return ['error' => $err];
    return json_decode($resp, true) ?? ['error' => 'Invalid response'];
}
