<?php

namespace createch\PaycorpSampathVault\Test\Unit;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\HmacUtils;
use createch\PaycorpSampathVault\Security\Sha256HmacSigner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The wire-compatibility gate for the removal of utf8_decode().
 *
 * Every digest here was produced by the ORIGINAL implementation --
 * hash_hmac('sha256', utf8_decode($data), utf8_decode($secret)) -- captured
 * while PHP still shipped utf8_decode(). If any of these change, live
 * merchants start getting HMAC rejections from the gateway.
 */
class Sha256HmacSignerTest extends TestCase
{
    /**
     * @dataProvider goldenVectors
     */
    #[DataProvider('goldenVectors')]
    public function testItReproducesTheLegacyDigest($secretHex, $payloadHex, $expectedHmac)
    {
        $signer = new Sha256HmacSigner(hex2bin($secretHex));

        $this->assertSame(
            $expectedHmac,
            $signer->sign(hex2bin($payloadHex)),
            'The signature changed. Any difference here means the gateway will '
            . 'reject requests that succeed on the 1.x release.'
        );
    }

    /**
     * @dataProvider goldenVectors
     */
    #[DataProvider('goldenVectors')]
    public function testTheLegacyStaticHelperProducesTheSameDigest($secretHex, $payloadHex, $expectedHmac)
    {
        // Integrations call this static directly; the typo in the name is
        // original and deliberately preserved.
        $this->assertSame(
            $expectedHmac,
            HmacUtils::genarateHmac(hex2bin($secretHex), hex2bin($payloadHex))
        );
    }

    /**
     * @return array<int,array{0:string,1:string,2:string}>
     */
    public static function goldenVectors()
    {
        $path = dirname(__DIR__) . '/Fixtures/hmac/golden-vectors.json';
        $data = json_decode(file_get_contents($path), true);

        $cases = array();

        foreach ($data['vectors'] as $index => $vector) {
            $cases['vector #' . $index] = array(
                $vector['secret_hex'],
                $vector['payload_hex'],
                $vector['hmac'],
            );
        }

        return $cases;
    }

    public function testVerifyAcceptsAMatchingSignature()
    {
        $signer = new Sha256HmacSigner('secret');
        $payload = '{"a":1}';

        $this->assertTrue($signer->verify($payload, $signer->sign($payload)));
    }

    public function testVerifyRejectsATamperedSignature()
    {
        $signer = new Sha256HmacSigner('secret');

        $this->assertFalse($signer->verify('{"a":1}', str_repeat('0', 64)));
        $this->assertFalse($signer->verify('{"a":1}', ''));
    }

    public function testVerifyRejectsASignatureThatOnlyMatchesAsAPrefix()
    {
        // Guards against a === comparison or a strncmp-style check.
        $signer = new Sha256HmacSigner('secret');
        $payload = '{"a":1}';
        $correct = $signer->sign($payload);

        $this->assertFalse($signer->verify($payload, substr($correct, 0, 32)));
    }

    public function testDifferentSecretsProduceDifferentDigests()
    {
        $payload = '{"a":1}';

        $this->assertNotSame(
            (new Sha256HmacSigner('secret-a'))->sign($payload),
            (new Sha256HmacSigner('secret-b'))->sign($payload)
        );
    }
}
