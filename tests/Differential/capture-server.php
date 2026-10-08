<?php

/**
 * Capture server for the 1.x / 2.x wire-parity harness.
 *
 * Run as the router for PHP's built-in server:
 *   CAPTURE_DIR=/tmp/caps php -S 127.0.0.1:8599 tests/Differential/capture-server.php
 *
 * Both versions post through real curl, so what lands here is the actual byte
 * stream each one puts on the wire -- body and headers included. Each request
 * is written to CAPTURE_DIR as NNN.json in arrival order; the two runs perform
 * the same operations in the same order, so index N on each side is the same
 * operation.
 *
 * The response is a superset envelope: every field any of the nine response
 * helpers reads, so each call completes and returns normally on both versions.
 * 1.x only checks that the body contains the string "responseData".
 */

$dir = getenv('CAPTURE_DIR');

if ($dir === false || $dir === '') {
    http_response_code(500);
    echo '{"error":"CAPTURE_DIR is not set"}';
    return;
}

if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$headers = array();

foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $headers[substr($key, 5)] = $value;
    }
}

if (isset($_SERVER['CONTENT_TYPE'])) {
    $headers['CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
}

ksort($headers);

$existing = glob($dir . '/*.json');
$index = $existing === false ? 0 : count($existing);

file_put_contents(
    sprintf('%s/%03d.json', $dir, $index + 1),
    json_encode(
        array(
            'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
            'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'headers' => $headers,
            'body' => file_get_contents('php://input'),
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    )
);

header('Content-Type: application/json');

echo json_encode(array(
    'responseData' => array(
        // payment: init / realTime / complete
        'reqid' => 'REQ-PARITY-1',
        'paymentPageUrl' => 'https://gateway.invalid/pay/REQ-PARITY-1',
        'txnReference' => 'TXN-PARITY-1',
        'responseCode' => '00',
        'responseText' => 'APPROVED',
        'settlementDate' => '20261008',
        'authCode' => '654321',
        'clientId' => '99990001',
        'transactionType' => 'PURCHASE',
        'clientRef' => 'ORDER-PARITY',
        'comment' => 'parity harness',
        'token' => 'TOK-PARITY-1',
        'extraData' => array(),
        'creditCard' => array(
            'type' => 'VISA',
            'holderName' => 'PARITY HARNESS',
            'number' => '411111XXXXXX1111',
            'expiry' => '12/28',
        ),
        // vault: store / retrieve / update / verify / delete
        'cardToken' => 'TOK-PARITY-1',
        'cardHolderName' => 'PARITY HARNESS',
        'cardNumber' => '411111XXXXXX1111',
        'cardExpiry' => '12/28',
        'cardType' => 'VISA',
        'tokenResponseCode' => '00',
        'tokenResponseText' => 'SUCCESS',
        'expiryDate' => '12/28',
        'status' => 'ACTIVE',
    ),
));
