<?php

namespace createch\PaycorpSampathVault\Test\Contract;

use createch\PaycorpSampathVault\Exceptions\ConfigurationException;
use createch\PaycorpSampathVault\Exceptions\GatewayErrorException;
use createch\PaycorpSampathVault\Exceptions\MalformedResponseException;
use createch\PaycorpSampathVault\Exceptions\TransportException;
use createch\PaycorpSampathVault\Test\Support\GatewayTestFactory;
use createch\PaycorpSampathVault\Testing\FakeHttpTransport;
use PHPUnit\Framework\TestCase;

/**
 * THE MOST IMPORTANT TESTS IN THIS PACKAGE.
 *
 * In 1.x, curl_exec() returning false was passed straight to json_decode(),
 * which produced null, which every response parser read as a complete set of
 * empty fields -- and realTimePayment() then returned 'status' => true. A
 * network outage was therefore indistinguishable from a completed payment, so
 * an order could be marked paid when no money had moved.
 *
 * Each test below pins one failure mode to a result the caller can act on,
 * and distinguishes "definitely did not happen" from "we do not know".
 */
class FailureModeTest extends TestCase
{
    public function testATransportFailureIsNotReportedAsASuccessfulPayment()
    {
        $transport = (new FakeHttpTransport())->willFail('Connection timed out after 60001 ms');

        $result = GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertFalse($result['status'], 'a network failure must never read as status=true');
        $this->assertArrayNotHasKey('ResponseCode', $result, 'no blank response code may be invented');
        $this->assertStringContainsString('Connection timed out', $result['msg']);
    }

    public function testATransportFailureIsFlaggedAsAnUnknownOutcome()
    {
        // The request may have reached the gateway and been authorised before
        // the connection dropped, so the caller must reconcile, not retry.
        $transport = (new FakeHttpTransport())->willFail();

        $result = GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertSame('unknown', $result['outcome']);
    }

    public function testAnEmptyResponseBodyIsAFailureNotAnEmptySuccess()
    {
        $transport = (new FakeHttpTransport())->willRespondWith('');

        $result = GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertFalse($result['status']);
        $this->assertSame('unknown', $result['outcome']);
    }

    public function testAnHtmlErrorPageIsAFailureNotAnEmptySuccess()
    {
        // A load balancer in front of the gateway returning a 502 page.
        $transport = (new FakeHttpTransport())->willRespondWith('<html><body>502 Bad Gateway</body></html>', 502);

        $result = GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertFalse($result['status']);
        $this->assertSame('unknown', $result['outcome']);
    }

    public function testAHttp200CarryingNonJsonIsAFailure()
    {
        $transport = (new FakeHttpTransport())->willRespondWith('not json at all', 200);

        $result = GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertFalse($result['status']);
    }

    public function testAnEnvelopeWithoutResponseDataIsAFailure()
    {
        // Valid JSON, but not a gateway envelope. 1.x computed
        // strpos($body, 'responseData') and then discarded the result.
        $transport = (new FakeHttpTransport())->willRespondWithJson(array('unexpected' => 'shape'));

        $result = GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertFalse($result['status']);
        $this->assertSame('unknown', $result['outcome']);
    }

    public function testAGatewayErrorNodeIsSurfacedWithItsCode()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'error' => array('errorCode' => 'HMAC_FAILURE', 'errorDescription' => 'Invalid HMAC'),
        ));

        $result = GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertFalse($result['status']);
        $this->assertStringContainsString('HMAC_FAILURE', $result['msg']);
        $this->assertStringContainsString('Invalid HMAC', $result['msg']);
        // The gateway definitively rejected it, so no money moved.
        $this->assertSame('failed', $result['outcome']);
    }

    public function testADeclinedPaymentIsASuccessfulExchangeWithADeclineCode()
    {
        // A decline is not an error: the exchange worked, the issuer said no.
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array(
                'txnReference' => 'TXN-2',
                'responseCode' => '05',
                'responseText' => 'DO NOT HONOUR',
            ),
        ));

        $result = GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertTrue($result['status']);
        $this->assertSame('05', $result['ResponseCode']);
        $this->assertSame('DO NOT HONOUR', $result['ResponseText']);
    }

    public function testMissingCredentialsFailBeforeAnyRequestIsSent()
    {
        $transport = new FakeHttpTransport();

        $result = GatewayTestFactory::vault($transport, array('hmac_secret' => ''))->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertFalse($result['status']);
        $this->assertSame(0, $transport->requestCount(), 'no half-configured request may reach the network');
        $this->assertStringContainsString('SAMPATH_HMAC', $result['msg']);
    }

    public function testAPlainHttpEndpointIsRefusedBeforeAnyCardDataIsSent()
    {
        $transport = new FakeHttpTransport();

        $result = GatewayTestFactory::vault($transport, array(
            'service_endpoint' => 'http://sampath.example.test/proxy',
        ))->realTimePayment(array('token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500));

        $this->assertFalse($result['status']);
        $this->assertSame(0, $transport->requestCount());
        $this->assertStringContainsString('non-HTTPS', $result['msg']);
    }

    public function testFailureMessagesNeverLeakCardDataOrSecrets()
    {
        $transport = (new FakeHttpTransport())->willRespondWith(
            '{"responseData":{"creditCard":{"number":"4564456445644564","secureId":"123"}},"bad":',
            200
        );

        $result = GatewayTestFactory::rawCardGateway($transport)->realTimePayment(array(
            'card_number' => '4564456445644564', 'secure_id' => '123',
            'card_type' => 'VISA', 'card_holder_name' => 'A N OTHER',
            'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertFalse($result['status']);
        $this->assertStringNotContainsString('4564456445644564', $result['msg']);
        $this->assertStringNotContainsString(GatewayTestFactory::SECRET, $result['msg']);
    }

    public function testThrowOnErrorRethrowsTypedExceptionsForNewCode()
    {
        $transport = (new FakeHttpTransport())->willFail('boom');

        $this->expectException(TransportException::class);

        GatewayTestFactory::vault($transport, array('throw_on_error' => true))->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));
    }

    public function testThrowOnErrorRethrowsGatewayErrors()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'error' => array('errorCode' => 'BAD_CLIENT', 'errorDescription' => 'Unknown clientId'),
        ));

        $this->expectException(GatewayErrorException::class);

        GatewayTestFactory::vault($transport, array('throw_on_error' => true))->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));
    }

    public function testThrowOnErrorRethrowsMalformedResponses()
    {
        $transport = (new FakeHttpTransport())->willRespondWith('{}');

        $this->expectException(MalformedResponseException::class);

        GatewayTestFactory::vault($transport, array('throw_on_error' => true))->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));
    }

    public function testThrowOnErrorRethrowsConfigurationProblems()
    {
        $this->expectException(ConfigurationException::class);

        GatewayTestFactory::vault(new FakeHttpTransport(), array(
            'authtoken' => '', 'throw_on_error' => true,
        ))->realTimePayment(array('token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500));
    }
}
