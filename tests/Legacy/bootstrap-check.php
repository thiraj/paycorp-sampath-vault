<?php

/**
 * Legacy framework bootstrap check: Laravel 5.5 through 7.
 *
 * Those framework versions cannot be exercised through orchestra/testbench,
 * because testbench 3.5 pins phpunit ^6.5 while this suite needs assertions
 * added in phpunit 9.1. That is an incompatibility between two dev tools, not
 * a limitation of the package -- so rather than leave the lower half of the
 * declared `illuminate/support` range unverified, this script boots the
 * provider against a real Illuminate container with no testbench and no
 * phpunit, and asserts the three framework APIs the package actually touches:
 *
 *   1. Illuminate\Support\ServiceProvider  -- register(), boot(), publishes()
 *   2. the config repository               -- mergeConfigFrom() landing values
 *   3. Illuminate\Support\Facades\Facade   -- the facade resolving the binding
 *
 * It then signs a frozen golden vector and compares the digest byte for byte,
 * which is the only assertion that really matters: if the signature a legacy
 * framework produces differs from the one the modern matrix produces, the
 * gateway rejects the payment.
 *
 * Exit code 0 means every check passed. Run it with:
 *   php tests/Legacy/bootstrap-check.php
 */

require __DIR__ . '/../../vendor/autoload.php';

use createch\PaycorpSampathVault\Configuration\GatewayConfiguration;
use createch\PaycorpSampathVault\Contracts\HostedPaymentGatewayInterface;
use createch\PaycorpSampathVault\Contracts\SignerInterface;
use createch\PaycorpSampathVault\PaycorpSampathRealTimePayment;
use createch\PaycorpSampathVault\PaycorpSampathVault;
use createch\PaycorpSampathVault\PaycorpSampathVaultFacade;
use createch\PaycorpSampathVault\PaycorpSampathVaultServiceProvider;
use createch\PaycorpSampathVault\Security\Latin1Encoder;
use createch\PaycorpSampathVault\Security\Sha256HmacSigner;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

$failures = array();
$checks = 0;

/**
 * @param  string  $label
 * @param  bool  $condition
 * @return void
 */
function check($label, $condition)
{
    global $failures, $checks;

    $checks++;

    if ($condition) {
        echo "  ok    {$label}\n";

        return;
    }

    $failures[] = $label;
    echo "  FAIL  {$label}\n";
}

/**
 * The smallest object that satisfies what ServiceProvider asks of $app across
 * Laravel 5.5 to 7: array access for 'config', runningInConsole(), and (from
 * 5.8) configurationIsCached(). Deliberately not an Application: pulling in
 * laravel/framework would reintroduce the testbench/phpunit conflict this
 * script exists to avoid.
 */
class LegacyApp extends Container
{
    /** @return bool */
    public function runningInConsole()
    {
        return true;
    }

    /** @return bool */
    public function configurationIsCached()
    {
        return false;
    }

    /**
     * @param  string  $path
     * @return string
     */
    public function configPath($path = '')
    {
        return '/tmp/config' . ($path === '' ? '' : '/' . $path);
    }

    /** @return string */
    public function basePath($path = '')
    {
        return '/tmp' . ($path === '' ? '' : '/' . $path);
    }

    /** @return string */
    public function version()
    {
        return 'legacy-bootstrap-check';
    }
}

echo 'PHP ' . PHP_VERSION . "\n";

$illuminate = defined('Illuminate\Foundation\Application::VERSION')
    ? constant('Illuminate\Foundation\Application::VERSION')
    : 'illuminate/support only';
echo "Laravel {$illuminate}\n\n";

// ---------------------------------------------------------------------------
// 1. The provider registers against a real Illuminate container.
// ---------------------------------------------------------------------------
echo "service provider\n";

$app = new LegacyApp();
$app->instance('config', new Repository(array()));

// Laravel 5.5-5.8's config_path() helper resolves app('path.config') rather
// than calling $app->configPath(), so the binding has to exist or publishes()
// dies with "Class path.config does not exist". A real application binds these
// in Application::bindPathsInContainer().
$app->instance('path.config', '/tmp/paycorp-bootstrap-check/config');
$app->instance('path.base', '/tmp/paycorp-bootstrap-check');
$app->instance('path', '/tmp/paycorp-bootstrap-check/app');

Container::setInstance($app);

$provider = new PaycorpSampathVaultServiceProvider($app);
$provider->register();
$provider->boot();

check('register() and boot() complete without error', true);

// mergeConfigFrom must have landed the shipped defaults under the package key.
$merged = $app['config']->get(PaycorpSampathVaultServiceProvider::CONFIG_KEY);
check('mergeConfigFrom() populated the config repository', is_array($merged) && $merged !== array());
check('the transport defaults merged', isset($merged['transport']) && is_array($merged['transport']));

// publishes() is registered under the documented tag.
$paths = PaycorpSampathVaultServiceProvider::pathsToPublish(
    PaycorpSampathVaultServiceProvider::class,
    PaycorpSampathVaultServiceProvider::CONFIG_KEY . '-config'
);
check('publishes() registered the config tag', ! empty($paths));

// ---------------------------------------------------------------------------
// 2. Every binding resolves to the expected type.
// ---------------------------------------------------------------------------
echo "\ncontainer bindings\n";

$app['config']->set(PaycorpSampathVaultServiceProvider::CONFIG_KEY . '.service_endpoint', 'https://gateway.invalid/rest');
$app['config']->set(PaycorpSampathVaultServiceProvider::CONFIG_KEY . '.hmac_secret', 'bootstrap-check-secret');
$app['config']->set(PaycorpSampathVaultServiceProvider::CONFIG_KEY . '.authtoken', '00000000-0000-4000-8000-000000000000');

$bindings = array(
    GatewayConfiguration::class => GatewayConfiguration::class,
    PaycorpSampathVault::class => PaycorpSampathVault::class,
    PaycorpSampathRealTimePayment::class => PaycorpSampathRealTimePayment::class,
    HostedPaymentGatewayInterface::class => PaycorpSampathVault::class,
    SignerInterface::class => Sha256HmacSigner::class,
);

foreach ($bindings as $abstract => $expected) {
    $short = substr($abstract, strrpos($abstract, '\\') + 1);

    try {
        $resolved = $app->make($abstract);
        check("{$short} resolves", $resolved instanceof $expected);
    } catch (\Exception $e) {
        check("{$short} resolves ({$e->getMessage()})", false);
    }
}

check(
    'the container alias still answers to the 1.x string key',
    $app->make(PaycorpSampathVaultServiceProvider::CONTAINER_ALIAS) instanceof PaycorpSampathVault
);

// ---------------------------------------------------------------------------
// 3. The facade resolves through the same container.
// ---------------------------------------------------------------------------
echo "\nfacade\n";

Facade::setFacadeApplication($app);

check(
    'the facade resolves to the hosted payment gateway',
    PaycorpSampathVaultFacade::getFacadeRoot() instanceof PaycorpSampathVault
);
check('version() reports the package version', PaycorpSampathVault::VERSION === $app->make(PaycorpSampathVault::class)->version());

// ---------------------------------------------------------------------------
// 4. The signature bytes are identical to the modern matrix.
// ---------------------------------------------------------------------------
echo "\nsignature parity (frozen golden vectors)\n";

$fixture = json_decode(file_get_contents(__DIR__ . '/../Fixtures/hmac/golden-vectors.json'), true);
check('golden vectors loaded', isset($fixture['vectors']) && count($fixture['vectors']) > 0);

$mismatched = 0;

foreach ($fixture['vectors'] as $i => $vector) {
    $signer = new Sha256HmacSigner(hex2bin($vector['secret_hex']), new Latin1Encoder());
    $actual = $signer->sign(hex2bin($vector['payload_hex']));

    if (! hash_equals($vector['hmac'], $actual)) {
        $mismatched++;
        echo "  vector {$i}: expected {$vector['hmac']}, got {$actual}\n";
    }
}

check(
    count($fixture['vectors']) . ' golden HMAC vectors reproduce byte for byte',
    $mismatched === 0
);

// ---------------------------------------------------------------------------

echo "\n";

if ($failures !== array()) {
    echo count($failures) . ' of ' . $checks . " checks FAILED:\n";

    foreach ($failures as $failure) {
        echo "  - {$failure}\n";
    }

    exit(1);
}

echo "all {$checks} checks passed\n";
exit(0);
