<?php

namespace createch\PaycorpSampathVault\Test\Support;

use createch\PaycorpSampathVault\Configuration\GatewayConfiguration;
use createch\PaycorpSampathVault\Contracts\HttpTransportInterface;
use createch\PaycorpSampathVault\Paycorplib\GatewayClient\GatewayClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\PaycorpSampathRealTimePayment;
use createch\PaycorpSampathVault\PaycorpSampathVault;
use createch\PaycorpSampathVault\Security\SensitiveDataRedactor;
use createch\PaycorpSampathVault\Security\Sha256HmacSigner;
use createch\PaycorpSampathVault\Support\ClientRuntime;

/**
 * Builds gateway objects wired to a fake transport, a frozen clock and a fixed
 * message id, so every outbound request body is deterministic.
 */
final class GatewayTestFactory
{
    const SECRET = 'test-hmac-secret';

    /**
     * @param  array<string,mixed>  $overrides
     * @return GatewayConfiguration
     */
    public static function configuration(array $overrides = array())
    {
        return new GatewayConfiguration(array_merge(array(
            'service_endpoint' => 'https://sampath.example.test/rest/service/proxy',
            'authtoken' => 'test-auth-token',
            'hmac_secret' => self::SECRET,
            'currency' => 'LKR',
            'return_url' => 'https://merchant.example.test/return',
            'tokenize_client_id' => '99990001',
            'purchase_client_id' => '99990002',
        ), $overrides));
    }

    /**
     * @param  HttpTransportInterface  $transport
     * @return ClientRuntime
     */
    public static function runtime(HttpTransportInterface $transport)
    {
        return new ClientRuntime(
            $transport,
            new Sha256HmacSigner(self::SECRET),
            new FrozenClock(),
            new FixedMessageIdGenerator(),
            new SensitiveDataRedactor()
        );
    }

    /**
     * @param  HttpTransportInterface  $transport
     * @param  array<string,mixed>     $overrides
     * @return GatewayClient
     */
    public static function client(HttpTransportInterface $transport, array $overrides = array())
    {
        $configuration = self::configuration($overrides);

        return new GatewayClient(
            ClientConfig::fromGatewayConfiguration($configuration),
            self::runtime($transport)
        );
    }

    /**
     * @param  HttpTransportInterface  $transport
     * @param  array<string,mixed>     $overrides
     * @return PaycorpSampathVault
     */
    public static function vault(HttpTransportInterface $transport, array $overrides = array())
    {
        $configuration = self::configuration($overrides);

        return new PaycorpSampathVault($configuration, self::client($transport, $overrides));
    }

    /**
     * @param  HttpTransportInterface  $transport
     * @param  array<string,mixed>     $overrides
     * @return PaycorpSampathRealTimePayment
     */
    public static function rawCardGateway(HttpTransportInterface $transport, array $overrides = array())
    {
        $configuration = self::configuration($overrides);

        return new PaycorpSampathRealTimePayment($configuration, self::client($transport, $overrides));
    }
}
