<?php

/**
 * Legacy side of the wire-parity harness: drives v1.4's public API.
 *
 * Runs in its own process because 1.x and 2.x occupy the same namespace and
 * cannot be autoloaded together. Requires LEGACY_SRC to point at a checkout of
 * the v1.4 src/ tree (the driver script extracts it with `git archive`).
 *
 * 1.x calls env() directly in its constructors, with no Laravel present, so
 * env() is defined here before anything is autoloaded. It must be declared
 * before the first autoload or 1.x's own call site would fatal.
 */

$legacySrc = getenv('LEGACY_SRC');

if ($legacySrc === false || ! is_dir($legacySrc)) {
    fwrite(STDERR, "LEGACY_SRC is not set or not a directory\n");
    exit(1);
}

/**
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
function env($key, $default = null)
{
    $value = getenv($key);

    return $value === false || $value === '' ? $default : $value;
}

spl_autoload_register(function ($class) use ($legacySrc) {
    $prefix = 'createch\\PaycorpSampathVault\\';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = $legacySrc . '/' . $relative . '.php';

    if (is_file($path)) {
        require $path;
    }
});

// 1.x emits undefined-index notices on several paths (that is one of the
// defects 2.x fixes). They are not what this harness measures.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

use createch\PaycorpSampathVault\Paycorplib\GatewayClient\GatewayClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\CreditCard;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\DeleteTokenRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\RetrieveCardRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\StoreCardRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\UpdateCardRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\VerifyTokenRequest;
use createch\PaycorpSampathVault\PaycorpSampathRealTimePayment;
use createch\PaycorpSampathVault\PaycorpSampathVault;

$ops = require __DIR__ . '/operations.php';

/**
 * Run one legacy operation, surviving a failure in its RESPONSE handling.
 *
 * Several 1.x helpers index responses with unquoted array keys. On PHP 7 that
 * is a warning and the key still resolves, which is why 1.x worked; on PHP 8
 * it is a fatal Error. Either way the request has already been signed, sent
 * and captured by the time the response is parsed -- and the request is what
 * this harness measures. Swallowing it here keeps one legacy response defect
 * from truncating the capture set.
 *
 * @param  string    $label
 * @param  callable  $operation
 * @return void
 */
function legacyOperation($label, callable $operation)
{
    try {
        $operation();
    } catch (\Throwable $e) {
        fwrite(STDERR, "  note: {$label} threw in response handling: " . $e->getMessage() . "\n");
    }
}

$vault = new PaycorpSampathVault();
$rawCard = new PaycorpSampathRealTimePayment();

// 1 -- hosted redirect init
legacyOperation('init', function () use ($vault, $ops) {
    $vault->initRequest($ops['init']);
});

// 2 -- real-time payment against a stored token
legacyOperation('realTimeToken', function () use ($vault, $ops) {
    $vault->realTimePayment($ops['realTimeToken']);
});

// 3 -- complete
legacyOperation('complete', function () use ($vault, $ops) {
    $vault->completeRequest($ops['complete']);
});

// 4 -- real-time payment with a raw card
legacyOperation('realTimeRawCard', function () use ($rawCard, $ops) {
    $rawCard->realTimePayment($ops['realTimeRawCard']);
});

// 5-9 -- the vault facade, driven through the same low-level client both
// versions expose, since 1.x never surfaced these on PaycorpSampathVault.
$config = new ClientConfig();
$config->setServiceEndpoint(env('SAMPATH_SERVICE_ENDPOINT', ''));
$config->setAuthToken(env('SAMPATH_AUTHTOKEN', ''));
$config->setHmacSecret(env('SAMPATH_HMAC', ''));

$client = new GatewayClient($config);

$card = new CreditCard();
$card->setType($ops['storeCard']['card_type']);
$card->setHolderName($ops['storeCard']['card_holder_name']);
$card->setNumber($ops['storeCard']['card_number']);
$card->setExpiry($ops['storeCard']['expire_at']);
$card->setSecureId($ops['storeCard']['secure_id']);
$card->setSecureIdSupplied(true);

$store = new StoreCardRequest();
$store->setClientId($ops['storeCard']['clientId']);
$store->setClientRef($ops['storeCard']['clientRef']);
$store->setCreditCard($card);
legacyOperation('storeCard', function () use ($client, $store) {
    $client->getVault()->storeCard($store);
});

$retrieve = new RetrieveCardRequest();
$retrieve->setClientId($ops['retrieveCard']['clientId']);
$retrieve->setToken($ops['retrieveCard']['token']);
legacyOperation('retrieveCard', function () use ($client, $retrieve) {
    $client->getVault()->retrieveCard($retrieve);
});

$update = new UpdateCardRequest();
$update->setClientId($ops['updateCard']['clientId']);
$update->setToken($ops['updateCard']['token']);
$update->setExpiryDate($ops['updateCard']['expiry_date']);
legacyOperation('updateCard', function () use ($client, $update) {
    $client->getVault()->updateCard($update);
});

$verify = new VerifyTokenRequest();
$verify->setClientId($ops['verifyToken']['clientId']);
$verify->setToken($ops['verifyToken']['token']);
legacyOperation('verifyToken', function () use ($client, $verify) {
    $client->getVault()->verifyToken($verify);
});

$delete = new DeleteTokenRequest();
$delete->setClientId($ops['deleteToken']['clientId']);
$delete->setToken($ops['deleteToken']['token']);
legacyOperation('deleteToken', function () use ($client, $delete) {
    $client->getVault()->deleteToken($delete);
});

// 10-12 -- edge inputs: non-ASCII in a signed field, empty optional strings,
// and a zero service fee.
legacyOperation('realTimeNonAscii', function () use ($vault, $ops) {
    $vault->realTimePayment($ops['realTimeNonAscii']);
});

legacyOperation('realTimeEmptyOptionals', function () use ($vault, $ops) {
    $vault->realTimePayment($ops['realTimeEmptyOptionals']);
});

legacyOperation('initZeroFee', function () use ($vault, $ops) {
    $vault->initRequest($ops['initZeroFee']);
});

echo "legacy run complete\n";
