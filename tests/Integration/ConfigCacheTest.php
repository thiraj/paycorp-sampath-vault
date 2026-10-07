<?php

namespace createch\PaycorpSampathVault\Test\Integration;

use createch\PaycorpSampathVault\Configuration\GatewayConfiguration;
use createch\PaycorpSampathVault\Contracts\HttpTransportInterface;
use createch\PaycorpSampathVault\PaycorpSampathVault;
use createch\PaycorpSampathVault\Test\TestCase;
use createch\PaycorpSampathVault\Testing\FakeHttpTransport;

/**
 * REGRESSION GATE FOR THE WORST PRODUCTION BUG IN 1.x.
 *
 * The 1.x constructors called env() directly:
 *
 *     $this->clientConfig->setHmacSecret(env('SAMPATH_HMAC', ''));
 *
 * Under `php artisan config:cache` -- the documented production setup --
 * Laravel stops populating $_ENV, so env() returns null and every one of those
 * reads silently became ''. The result was an empty service endpoint and an
 * HMAC computed with an empty secret: every payment failed, and only on
 * production, where the cache is warmed.
 *
 * These tests simulate that state by clearing the environment entirely while
 * leaving the config repository populated, which is exactly what a cached
 * config looks like.
 */
class ConfigCacheTest extends TestCase
{
    /** @var array<string,string|false> */
    private $savedEnvironment = array();

    /** @var string[] */
    private static $variables = array(
        'SAMPATH_SERVICE_ENDPOINT',
        'SAMPATH_AUTHTOKEN',
        'SAMPATH_HMAC',
        'SAMPATH_CURRENCY',
        'SAMPATH_RETURN_URL',
        'SAMPATH_TOKENIZE_CLIENT_ID',
        'SAMPATH_PURCHASE_CLIENT_ID',
    );

    protected function setUp(): void
    {
        parent::setUp();

        // Simulate a cached config: the repository holds the values, the
        // environment holds nothing.
        foreach (self::$variables as $variable) {
            $this->savedEnvironment[$variable] = getenv($variable);
            putenv($variable);
            unset($_ENV[$variable], $_SERVER[$variable]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnvironment as $variable => $value) {
            if ($value !== false) {
                putenv($variable . '=' . $value);
                $_ENV[$variable] = $value;
            }
        }

        parent::tearDown();
    }

    public function testEnvIsGenuinelyEmptyInThisTest()
    {
        // Proves the simulation is real; without this the tests below could
        // pass for the wrong reason.
        $this->assertFalse(getenv('SAMPATH_HMAC'));
        $this->assertNull(env('SAMPATH_HMAC'));
    }

    public function testCredentialsStillResolveWhenEnvIsUnavailable()
    {
        $configuration = $this->app->make(GatewayConfiguration::class);

        $this->assertSame('test-hmac-secret', $configuration->hmacSecret());
        $this->assertSame('test-auth-token', $configuration->authToken());
        $this->assertSame('https://sampath.example.test/proxy', $configuration->serviceEndpoint());
    }

    public function testAPaymentCompletesWithACachedConfigAndNoEnvironment()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array(
                'txnReference' => 'TXN-1',
                'responseCode' => '00',
                'responseText' => 'APPROVED',
            ),
        ));
        $this->app->instance(HttpTransportInterface::class, $transport);

        $result = $this->app->make(PaycorpSampathVault::class)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertTrue($result['status'], '1.x failed here with an empty endpoint and an empty HMAC secret');
        $this->assertSame('00', $result['ResponseCode']);
    }

    public function testTheRequestIsSignedWithTheRealSecretNotAnEmptyOne()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00'),
        ));
        $this->app->instance(HttpTransportInterface::class, $transport);

        $this->app->make(PaycorpSampathVault::class)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $body = $transport->lastRequestBody();
        $withRealSecret = hash_hmac('sha256', $body, 'test-hmac-secret', false);
        $withEmptySecret = hash_hmac('sha256', $body, '', false);

        $this->assertSame($withRealSecret, $transport->header('HMAC'));
        $this->assertNotSame($withEmptySecret, $transport->header('HMAC'));
    }

    public function testTheEndpointIsNotEmpty()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00'),
        ));
        $this->app->instance(HttpTransportInterface::class, $transport);

        $this->app->make(PaycorpSampathVault::class)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertSame('https://sampath.example.test/proxy', $transport->lastUrl());
    }
}
