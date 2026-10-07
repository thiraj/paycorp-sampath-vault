<?php

namespace createch\PaycorpSampathVault\Test\Contract;

use createch\PaycorpSampathVault\Security\Sha256HmacSigner;
use createch\PaycorpSampathVault\Test\Support\GatewayTestFactory;
use createch\PaycorpSampathVault\Testing\FakeHttpTransport;
use PHPUnit\Framework\TestCase;

/**
 * Pins the exact bytes this package puts on the wire.
 *
 * The HMAC is computed over the serialised request body, so renaming a key,
 * reordering one, or changing a value's type from string to integer changes
 * the signature and makes the gateway reject the request. These assertions
 * exist to make any such drift fail in CI rather than in production.
 */
class OutboundRequestTest extends TestCase
{
    public function testRealTimePaymentSendsTheExpectedEnvelope()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array(
                'txnReference' => 'TXN-1',
                'responseCode' => '00',
                'responseText' => 'APPROVED',
                'settlementDate' => '20261007',
                'authCode' => '123456',
            ),
        ));

        GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1',
            'expire_at' => '12/28',
            'amount' => 1500,
            'clientRef' => 'ORDER-1',
            'comment' => 'Order 1',
        ));

        $payload = $transport->lastRequestPayload();

        $this->assertSame('1.04', $payload['version']);
        $this->assertSame('AAAAAAAA-BBBB-4CCC-8DDD-EEEEEEEEEEEE', $payload['msgId']);
        $this->assertSame('PAYMENT_REAL_TIME', $payload['operation']);
        $this->assertSame('2026-10-07 12:00:00', $payload['requestDate']);
        $this->assertFalse($payload['validateOnly']);

        $this->assertSame(
            array(
                'clientId', 'originalTxnReference', 'creditCard', 'transactionType',
                'transactionAmount', 'clientRef', 'comment', 'extraData',
            ),
            array_keys($payload['requestData']),
            'requestData key order is part of the signed payload'
        );

        $this->assertSame('99990002', $payload['requestData']['clientId'], 'real-time uses the PURCHASE client id');
        $this->assertSame('PURCHASE', $payload['requestData']['transactionType']);
        $this->assertSame('TOK-1', $payload['requestData']['creditCard']['number']);
        $this->assertSame('12/28', $payload['requestData']['creditCard']['expiry']);
        $this->assertSame('LKR', $payload['requestData']['transactionAmount']['currency']);
        $this->assertSame(1500, $payload['requestData']['transactionAmount']['paymentAmount']);
        $this->assertSame('ORDER-1', $payload['requestData']['clientRef']);
    }

    public function testInitRequestUsesTheTokeniseClientIdAndRedirect()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array(
                'reqid' => 'REQ-1',
                'expireAt' => '2026-10-07 12:30:00',
                'paymentPageUrl' => 'https://sampath.example.test/pay/REQ-1',
            ),
        ));

        GatewayTestFactory::vault($transport)->initRequest(array(
            'total_amount' => 1500,
            'service_fee_amount' => 50,
            'payment_amount' => 1450,
            'clientRef' => 'ORDER-1',
            'comment' => 'Order 1',
        ));

        $payload = $transport->lastRequestPayload();

        $this->assertSame('PAYMENT_INIT', $payload['operation']);
        $this->assertSame('99990001', $payload['requestData']['clientId'], 'init uses the TOKENIZE client id');
        $this->assertTrue($payload['requestData']['tokenize']);
        $this->assertSame('GET', $payload['requestData']['redirect']['returnMethod']);
        $this->assertSame(
            'https://merchant.example.test/return',
            $payload['requestData']['redirect']['returnUrl']
        );
        $this->assertSame(1500, $payload['requestData']['transactionAmount']['totalAmount']);
        $this->assertSame(50, $payload['requestData']['transactionAmount']['serviceFeeAmount']);
        $this->assertSame(1450, $payload['requestData']['transactionAmount']['paymentAmount']);
    }

    public function testTheHmacHeaderMatchesASignatureOverTheExactBodySent()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00'),
        ));

        GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 100,
        ));

        $expected = (new Sha256HmacSigner(GatewayTestFactory::SECRET))->sign($transport->lastRequestBody());

        $this->assertSame($expected, $transport->header('HMAC'));
    }

    public function testTheAuthTokenAndContentTypeHeadersAreSent()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00'),
        ));

        GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 100,
        ));

        $this->assertSame('test-auth-token', $transport->header('AUTHTOKEN'));
        $this->assertSame('application/json', $transport->header('Content-Type'));
    }

    public function testANonAsciiCommentProducesTheLegacySignature()
    {
        // The whole point of Latin1Encoder: a non-ASCII comment must sign the
        // same way it did before utf8_decode() was removed.
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00'),
        ));

        GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 100,
            'comment' => 'Côte d\'Ivoire – Ürün ß',
        ));

        $body = $transport->lastRequestBody();
        $legacyDigest = hash_hmac(
            'sha256',
            $this->legacyUtf8Decode($body),
            $this->legacyUtf8Decode(GatewayTestFactory::SECRET),
            false
        );

        $this->assertSame($legacyDigest, $transport->header('HMAC'));
    }

    public function testItPostsToTheConfiguredEndpoint()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00'),
        ));

        GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 100,
        ));

        $this->assertSame('https://sampath.example.test/rest/service/proxy', $transport->lastUrl());
    }

    public function testEachRequestGetsExactlyOneHttpCallAndNeverARetry()
    {
        // A retried authorisation can capture the money twice.
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00'),
        ));

        GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 100,
        ));

        $this->assertSame(1, $transport->requestCount());
    }

    public function testTlsVerificationIsEnabledOnTheActualRequest()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00'),
        ));

        GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 100,
        ));

        $this->assertTrue($transport->lastOptions()->verifiesPeer());
        $this->assertGreaterThan(0, $transport->lastOptions()->timeout());
    }

    /**
     * An independent reimplementation of utf8_decode() for the well-formed
     * case, so this test does not depend on the class it is verifying.
     *
     * @param  string  $value
     * @return string
     */
    private function legacyUtf8Decode($value)
    {
        $out = '';

        foreach (preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) as $character) {
            $codePoint = mb_ord($character, 'UTF-8');
            $out .= $codePoint !== false && $codePoint <= 0xFF ? chr($codePoint) : '?';
        }

        return $out;
    }
}
