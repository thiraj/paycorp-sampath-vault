<?php

namespace createch\PaycorpSampathVault\Test\Integration;

use createch\PaycorpSampathVault\Configuration\GatewayConfiguration;
use createch\PaycorpSampathVault\Contracts\ClockInterface;
use createch\PaycorpSampathVault\Contracts\EncoderInterface;
use createch\PaycorpSampathVault\Contracts\HostedPaymentGatewayInterface;
use createch\PaycorpSampathVault\Contracts\HttpTransportInterface;
use createch\PaycorpSampathVault\Contracts\MessageIdGeneratorInterface;
use createch\PaycorpSampathVault\Contracts\RealTimePaymentGatewayInterface;
use createch\PaycorpSampathVault\Contracts\RedactorInterface;
use createch\PaycorpSampathVault\Contracts\SignerInterface;
use createch\PaycorpSampathVault\Http\CurlTransport;
use createch\PaycorpSampathVault\Paycorplib\GatewayClient\GatewayClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\PaycorpSampathRealTimePayment;
use createch\PaycorpSampathVault\PaycorpSampathVault;
use createch\PaycorpSampathVault\PaycorpSampathVaultServiceProvider;
use createch\PaycorpSampathVault\Test\TestCase;
use createch\PaycorpSampathVault\Testing\FakeHttpTransport;
use PHPUnit\Framework\Attributes\DataProvider;

class ServiceProviderTest extends TestCase
{
    /**
     * @dataProvider containerBindings
     */
    #[DataProvider('containerBindings')]
    public function testEveryBindingResolves($abstract, $expectedType)
    {
        $this->assertInstanceOf($expectedType, $this->app->make($abstract));
    }

    /**
     * @return array<string,array{0:string,1:string}>
     */
    public static function containerBindings()
    {
        return array(
            'configuration' => array(GatewayConfiguration::class, GatewayConfiguration::class),
            'client config' => array(ClientConfig::class, ClientConfig::class),
            'gateway client' => array(GatewayClient::class, GatewayClient::class),
            'vault gateway' => array(PaycorpSampathVault::class, PaycorpSampathVault::class),
            'raw card gateway' => array(PaycorpSampathRealTimePayment::class, PaycorpSampathRealTimePayment::class),
            'string alias' => array('paycorp-sampath-vault', PaycorpSampathVault::class),
            'transport interface' => array(HttpTransportInterface::class, CurlTransport::class),
            'signer interface' => array(SignerInterface::class, SignerInterface::class),
            'clock interface' => array(ClockInterface::class, ClockInterface::class),
            'message id interface' => array(MessageIdGeneratorInterface::class, MessageIdGeneratorInterface::class),
            'redactor interface' => array(RedactorInterface::class, RedactorInterface::class),
            'encoder interface' => array(EncoderInterface::class, EncoderInterface::class),
            'hosted payment interface' => array(HostedPaymentGatewayInterface::class, PaycorpSampathVault::class),
            'real time interface' => array(RealTimePaymentGatewayInterface::class, PaycorpSampathVault::class),
        );
    }

    public function testTheStringAliasAndTheClassResolveToTheSameSingleton()
    {
        $this->assertSame(
            $this->app->make('paycorp-sampath-vault'),
            $this->app->make(PaycorpSampathVault::class)
        );
    }

    public function testConfigurationIsReadFromTheConfigRepository()
    {
        $configuration = $this->app->make(GatewayConfiguration::class);

        $this->assertSame('https://sampath.example.test/proxy', $configuration->serviceEndpoint());
        $this->assertSame('99990001', $configuration->tokenizeClientId());
        $this->assertSame('99990002', $configuration->purchaseClientId());
    }

    public function testThePackageConfigDefaultsAreMergedForKeysTheAppDidNotSet()
    {
        // mergeConfigFrom must supply the transport defaults even when the
        // application has only set the credentials.
        $this->assertTrue($this->app['config']->get('paycorp-sampath-vault.transport.verify_peer'));
        $this->assertSame(120, $this->app['config']->get('paycorp-sampath-vault.transport.timeout'));
    }

    public function testAnApplicationCanSubstituteTheTransportWithoutTouchingThePackage()
    {
        // The whole point of binding the interface: this is how a consuming
        // application tests its own payment flows.
        $fake = new FakeHttpTransport();
        $this->app->instance(HttpTransportInterface::class, $fake);

        $this->assertSame($fake, $this->app->make(HttpTransportInterface::class));
    }

    public function testThePublishedConfigIsASingleFileAtTheTopLevelOfConfig()
    {
        // REGRESSION: 1.x published to config_path('paycorp-sampath-vault'), a
        // DIRECTORY. Laravel only autoloads top-level config/*.php, so the
        // published file was never read by anything.
        $paths = PaycorpSampathVaultServiceProvider::pathsToPublish(
            PaycorpSampathVaultServiceProvider::class,
            PaycorpSampathVaultServiceProvider::CONFIG_KEY . '-config'
        );

        $this->assertNotEmpty($paths, 'the publish tag must exist so vendor:publish can find it');

        foreach ($paths as $source => $destination) {
            $this->assertStringEndsWith('config/paycorp-sampath-vault.php', str_replace('\\', '/', $source));
            $this->assertStringEndsWith('paycorp-sampath-vault.php', str_replace('\\', '/', $destination));
            $this->assertFileExists($source);
        }
    }

    public function testThePublishedConfigFileContainsNoHardCodedCredentials()
    {
        // REGRESSION: 1.x shipped live production credentials as literals in its
        // config file. This canary is structural rather than a comparison against
        // the leaked strings -- publishing those strings here would re-disclose
        // them on every clone. Any credential key that grows a literal default,
        // or any host name appearing in the file, fails the build.
        $source = file_get_contents(dirname(__DIR__, 2) . '/config/paycorp-sampath-vault.php');

        $this->assertIsString($source);

        $credentialKeys = [
            'service_endpoint',
            'authtoken',
            'hmac_secret',
            'tokenize_client_id',
            'purchase_client_id',
        ];

        foreach ($credentialKeys as $key) {
            $this->assertMatchesRegularExpression(
                "/'" . $key . "'\s*=>\s*env\(\s*'[A-Z0-9_]+'\s*(?:,\s*''\s*)?\)/",
                $source,
                "'{$key}' must read from env() with no literal default"
            );
        }

        // No host name may be baked in: the gateway endpoint is deployment data.
        $this->assertDoesNotMatchRegularExpression(
            '/[a-z0-9.-]+\.(?:com|net|lk|au|org|io)/i',
            $this->stripComments($source),
            'the config file must not name a gateway host'
        );

        // Reject any bare string or numeric literal outside an env() default:
        // a credential reintroduced under a new key is still a credential.
        $code = $this->stripComments($source);
        $code = preg_replace("/env\(\s*'[A-Z0-9_]+'\s*(?:,[^)]*)?\)/", 'ENV', $code);

        $this->assertDoesNotMatchRegularExpression(
            "/=>\s*'[^']+'/",
            (string) $code,
            'every config value must come from env(), never a literal'
        );
    }

    /**
     * Strip // and block comments so the deliberate prose in the config file --
     * which names the leaked keys by their env var -- does not trip the scans.
     *
     * @param  string  $source
     * @return string
     */
    private function stripComments($source)
    {
        $out = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $out .= is_array($token) ? $token[1] : $token;
        }

        return $out;
    }

    public function testProvidesListsTheServicesItRegisters()
    {
        $provider = new PaycorpSampathVaultServiceProvider($this->app);

        $this->assertContains('paycorp-sampath-vault', $provider->provides());
        $this->assertContains(PaycorpSampathVault::class, $provider->provides());
    }
}
