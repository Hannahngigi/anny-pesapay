<?php
// stk.php - sends an M-Pesa STK Push (PIN prompt) to the customer's phone.
// Put this next to qr-pay.html in your XAMPP htdocs folder.

header('Content-Type: application/json');

// Keys live in config.php (never uploaded to GitHub)
$config         = require __DIR__ . '/config.php';
$env            = $config['env'];
$consumerKey    = $config['consumerKey'];
$consumerSecret = $config['consumerSecret'];
$shortcode      = $config['shortcode'];
$till           = $config['till'];
$passkey        = $config['passkey'];
$callbackUrl    = $config['callbackUrl'];

$base = $env === 'sandbox' ? 'https://sandbox.safaricom.co.ke' : 'https://api.safaricom.co.ke';
$type = $env === 'sandbox' ? 'CustomerPayBillOnline' : 'CustomerBuyGoodsOnline';

$input  = json_decode(file_get_contents('php://input'), true);
$phone  = preg_replace('/\D/', '', $input['phone'] ?? '');
$amount = (int)($input['amount'] ?? 0);

// Turn 07XXXXXXXX or 7XXXXXXXX into 2547XXXXXXXX
if (strpos($phone, '0') === 0) $phone = '254' . substr($phone, 1);
if (strpos($phone, '7') === 0 || strpos($phone, '1') === 0) $phone = '254' . $phone;

if (!preg_match('/^254[71]\d{8}$/', $phone) || $amount < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Enter a valid phone number and amount.']);
    exit;
}

function call($url, $headers, $body = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// 1. Get access token
$auth  = base64_encode("$consumerKey:$consumerSecret");
$token = call("$base/oauth/v1/generate?grant_type=client_credentials", ["Authorization: Basic $auth"]);
if (empty($token['access_token'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not authenticate with Safaricom.']);
    exit;
}

// 2. Send the STK push
$timestamp = date('YmdHis');
$password  = base64_encode($shortcode . $passkey . $timestamp);

$result = call("$base/mpesa/stkpush/v1/processrequest", [
    'Authorization: Bearer ' . $token['access_token'],
    'Content-Type: application/json',
], [
    'BusinessShortCode' => $shortcode,
    'Password'          => $password,
    'Timestamp'         => $timestamp,
    'TransactionType'   => $type,
    'Amount'            => $amount,
    'PartyA'            => $phone,
    'PartyB'            => $till,
    'PhoneNumber'       => $phone,
    'CallBackURL'       => $callbackUrl,
    'AccountReference'  => 'QR Pay',
    'TransactionDesc'   => 'Payment',
]);

echo json_encode($result);