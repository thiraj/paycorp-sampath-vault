<?php

/**
 * Standalone smoke test against the Paycorp sandbox, outside Laravel.
 *
 * Replaces the old src/Paycorplib/GatewayIT/* scripts, which shipped inside the
 * installed package with hard-coded credentials -- one set pointing at the
 * production endpoint. This reads everything from the environment instead and is
 * excluded from the Composer distribution.
 *
 * It runs with validateOnly enabled, so the gateway validates the request
 * without moving money.
 *
 * Usage:
 *   SAMPATH_SERVICE_ENDPOINT=https://test-sampath.paycorp.com.au/rest/service/proxy \
 *   SAMPATH_AUTHTOKEN=... \
 *   SAMPATH_HMAC=... \
 *   SAMPATH_TOKENIZE_CLIENT_ID=... \
 *   SAMPATH_RETURN_URL=https://example.test/return \
 *   SAMPATH_CURRENCY=LKR \
 *   php examples/smoke-test.php
 */

require __DIR__ . '/../vendor/autoload.php';

use createch\PaycorpSampathVault\Configuration\ConfigurationFactory;
use createch\PaycorpSampathVault\PaycorpSampathVault;

$configuration = ConfigurationFactory::fromEnvironment()->with(array(
    // Never move money from a smoke test.
    'validate_only' => true,
    'throw_on_error' => true,
));

printf("endpoint : %s\n", $configuration->serviceEndpoint());
printf("currency : %s\n", $configuration->currency());
printf("verify   : %s\n", $configuration->transportOptions()->verifiesPeer() ? 'on' : 'OFF');
echo str_repeat('-', 60), "\n";

$gateway = new PaycorpSampathVault($configuration);

try {
    $result = $gateway->initRequest(array(
        'clientRef' => 'SMOKE-' . date('YmdHis'),
        'comment' => 'validateOnly smoke test',
        'total_amount' => 100,
        'service_fee_amount' => 0,
        'payment_amount' => 100,
    ));

    echo "PAYMENT_INIT result:\n";
    print_r($result);
    exit($result['status'] ? 0 : 1);
} catch (Throwable $e) {
    // Messages are already redacted by the package.
    printf("%s: %s\n", get_class($e), $e->getMessage());
    exit(1);
}
