<?php

namespace createch\PaycorpSampathVault\Test\Unit;

use createch\PaycorpSampathVault\Configuration\ConfigurationFactory;
use createch\PaycorpSampathVault\Configuration\GatewayConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * The factory is what keeps this client usable outside Laravel, and what keeps
 * env() confined to the published config file.
 */
class ConfigurationFactoryTest extends TestCase
{
    /** @var array<string,string|false> */
    private $saved = array();

    /** @var string[] */
    private static $variables = array(
        'SAMPATH_SERVICE_ENDPOINT', 'SAMPATH_AUTHTOKEN', 'SAMPATH_HMAC',
        'SAMPATH_CURRENCY', 'SAMPATH_VERIFY_SSL', 'SAMPATH_TIMEOUT',
    );

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::$variables as $variable) {
            $this->saved[$variable] = getenv($variable);
            putenv($variable);
            unset($_ENV[$variable], $_SERVER[$variable]);
        }
    }

    protected function tearDown(): void
    {
        foreach (self::$variables as $variable) {
            putenv($variable);
            unset($_ENV[$variable], $_SERVER[$variable]);

            if ($this->saved[$variable] !== false) {
                putenv($variable . '=' . $this->saved[$variable]);
            }
        }

        parent::tearDown();
    }

    public function testAnExplicitConfigurationObjectIsReturnedUnchanged()
    {
        $configuration = new GatewayConfiguration(array('authtoken' => 'abc'));

        $this->assertSame($configuration, ConfigurationFactory::resolve($configuration));
    }

    public function testAnExplicitArrayIsUsedDirectly()
    {
        $configuration = ConfigurationFactory::resolve(array('authtoken' => 'abc'));

        $this->assertSame('abc', $configuration->authToken());
    }

    public function testItReadsEnvironmentVariablesWhenNothingElseIsAvailable()
    {
        putenv('SAMPATH_SERVICE_ENDPOINT=https://sandbox.example.test/proxy');
        putenv('SAMPATH_AUTHTOKEN=env-token');
        putenv('SAMPATH_HMAC=env-secret');
        putenv('SAMPATH_CURRENCY=USD');

        $configuration = ConfigurationFactory::fromEnvironment();

        $this->assertSame('https://sandbox.example.test/proxy', $configuration->serviceEndpoint());
        $this->assertSame('env-token', $configuration->authToken());
        $this->assertSame('env-secret', $configuration->hmacSecret());
        $this->assertSame('USD', $configuration->currency());
    }

    public function testItMapsNestedTransportVariables()
    {
        putenv('SAMPATH_TIMEOUT=45');
        putenv('SAMPATH_VERIFY_SSL=false');

        $options = ConfigurationFactory::fromEnvironment()->transportOptions();

        $this->assertSame(45, $options->timeout());
        $this->assertFalse($options->verifiesPeer(), '"false" from the environment must disable verification');
    }

    public function testVerificationStaysOnWhenTheVariableIsAbsent()
    {
        $this->assertTrue(ConfigurationFactory::fromEnvironment()->transportOptions()->verifiesPeer());
    }

    public function testMissingEnvironmentVariablesYieldAConstructibleConfiguration()
    {
        // Must not throw: an application has to boot before it is configured.
        $this->assertInstanceOf(GatewayConfiguration::class, ConfigurationFactory::fromEnvironment());
    }

    public function testItPrefersSuperglobalsOverGetenv()
    {
        // Laravel's Dotenv populates $_ENV and $_SERVER.
        putenv('SAMPATH_AUTHTOKEN=from-getenv');
        $_ENV['SAMPATH_AUTHTOKEN'] = 'from-env-superglobal';

        $this->assertSame('from-env-superglobal', ConfigurationFactory::fromEnvironment()->authToken());
    }
}
