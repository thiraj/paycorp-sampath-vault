<?php

namespace createch\PaycorpSampathVault\Test\Unit;

use createch\PaycorpSampathVault\Configuration\GatewayConfiguration;
use createch\PaycorpSampathVault\Exceptions\ConfigurationException;
use PHPUnit\Framework\TestCase;

class GatewayConfigurationTest extends TestCase
{
    public function testConstructionNeverThrowsOnMissingValues()
    {
        // A Laravel application, and `artisan`, must still boot when the
        // SAMPATH_* variables are absent, exactly as they did before.
        $configuration = new GatewayConfiguration(array());

        $this->assertSame('', $configuration->serviceEndpoint());
        $this->assertSame('', $configuration->authToken());
        $this->assertSame('', $configuration->hmacSecret());
    }

    public function testAssertUsableNamesEveryMissingVariable()
    {
        $configuration = new GatewayConfiguration(array());

        try {
            $configuration->assertUsable();
            $this->fail('expected a ConfigurationException');
        } catch (ConfigurationException $e) {
            $this->assertStringContainsString('SAMPATH_SERVICE_ENDPOINT', $e->getMessage());
            $this->assertStringContainsString('SAMPATH_AUTHTOKEN', $e->getMessage());
            $this->assertStringContainsString('SAMPATH_HMAC', $e->getMessage());
        }
    }

    public function testAssertUsableReportsOperationSpecificRequirements()
    {
        $configuration = $this->validConfiguration();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/SAMPATH_RETURN_URL/');

        $configuration->assertUsable(array('SAMPATH_RETURN_URL' => ''));
    }

    public function testAssertUsablePassesForACompleteHttpsConfiguration()
    {
        $this->validConfiguration()->assertUsable();

        $this->addToAssertionCount(1);
    }

    public function testItRefusesAPlainHttpEndpoint()
    {
        // Cardholder data must not travel in the clear.
        $configuration = $this->validConfiguration(array(
            'service_endpoint' => 'http://sampath.example.test/proxy',
        ));

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/non-HTTPS/');

        $configuration->assertUsable();
    }

    public function testThePlainHttpEscapeHatchIsExplicitAndOptIn()
    {
        $configuration = $this->validConfiguration(array(
            'service_endpoint' => 'http://localhost:8080/proxy',
            'allow_insecure_endpoint' => true,
        ));

        $configuration->assertUsable();

        $this->addToAssertionCount(1);
    }

    public function testWhitespaceOnlyCredentialsCountAsMissing()
    {
        $configuration = $this->validConfiguration(array('hmac_secret' => '   '));

        $this->expectException(ConfigurationException::class);

        $configuration->assertUsable();
    }

    public function testItTrimsSurroundingWhitespaceFromValues()
    {
        // A trailing newline in a .env file is a classic cause of HMAC failures.
        $configuration = $this->validConfiguration(array('authtoken' => "  token  \n"));

        $this->assertSame('token', $configuration->authToken());
    }

    public function testItInterpretsStringBooleansFromTheEnvironment()
    {
        // .env values arrive as strings; "false" must not be truthy.
        $this->assertTrue($this->validConfiguration(array('validate_only' => 'true'))->isValidateOnly());
        $this->assertTrue($this->validConfiguration(array('validate_only' => '1'))->isValidateOnly());
        $this->assertFalse($this->validConfiguration(array('validate_only' => 'false'))->isValidateOnly());
        $this->assertFalse($this->validConfiguration(array('validate_only' => '0'))->isValidateOnly());
        $this->assertFalse($this->validConfiguration(array('validate_only' => ''))->isValidateOnly());
    }

    public function testItIsImmutableAndWithReturnsACopy()
    {
        $original = $this->validConfiguration();
        $modified = $original->with(array('currency' => 'USD'));

        $this->assertSame('LKR', $original->currency());
        $this->assertSame('USD', $modified->currency());
        $this->assertNotSame($original, $modified);
    }

    public function testDebugOutputHidesCredentials()
    {
        // Guards against dd($config) or a stack trace leaking the secret.
        $debug = $this->validConfiguration()->__debugInfo();

        $this->assertSame('[REDACTED]', $debug['authtoken']);
        $this->assertSame('[REDACTED]', $debug['hmac_secret']);
        $this->assertStringNotContainsString('super-secret', json_encode($debug));
    }

    public function testTlsVerificationIsOnByDefault()
    {
        // The legacy client hard-coded it off.
        $this->assertTrue($this->validConfiguration()->transportOptions()->verifiesPeer());
    }

    public function testThereIsATotalRequestTimeoutByDefault()
    {
        // The legacy client had only a connect timeout, so a stalled gateway
        // pinned a PHP worker indefinitely.
        $this->assertGreaterThan(0, $this->validConfiguration()->transportOptions()->timeout());
    }

    public function testTheLegacyConnectTimeoutIsPreserved()
    {
        // Lowering it would start failing requests that succeed today.
        $this->assertSame(60, $this->validConfiguration()->transportOptions()->connectTimeout());
    }

    /**
     * @param  array<string,mixed>  $overrides
     * @return GatewayConfiguration
     */
    private function validConfiguration(array $overrides = array())
    {
        return new GatewayConfiguration(array_merge(array(
            'service_endpoint' => 'https://sampath.example.test/proxy',
            'authtoken' => 'token',
            'hmac_secret' => 'super-secret',
            'currency' => 'LKR',
            'return_url' => 'https://merchant.example.test/return',
            'tokenize_client_id' => '1',
            'purchase_client_id' => '2',
        ), $overrides));
    }
}
