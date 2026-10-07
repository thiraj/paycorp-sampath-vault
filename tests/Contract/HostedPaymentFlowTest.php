<?php

namespace createch\PaycorpSampathVault\Test\Contract;

use createch\PaycorpSampathVault\Test\Support\GatewayTestFactory;
use createch\PaycorpSampathVault\Testing\FakeHttpTransport;
use PHPUnit\Framework\TestCase;

/**
 * The hosted redirect flow end to end: init, then complete.
 */
class HostedPaymentFlowTest extends TestCase
{
    public function testTheFullInitThenCompleteSequence()
    {
        $transport = (new FakeHttpTransport())
            ->willRespondWithJson(array('responseData' => array(
                'reqid' => 'REQ-1',
                'expireAt' => '2026-10-07 12:30:00',
                'paymentPageUrl' => 'https://sampath.example.test/pay/REQ-1',
            )))
            ->willRespondWithJson($this->completeEnvelope());

        $gateway = GatewayTestFactory::vault($transport);

        $init = $gateway->initRequest(array(
            'total_amount' => 1500, 'service_fee_amount' => 50, 'payment_amount' => 1450,
            'clientRef' => 'ORDER-1', 'comment' => 'Order 1',
        ));

        $this->assertTrue($init['status']);
        $this->assertSame('REQ-1', $init['reqid']);
        $this->assertSame('2026-10-07 12:30:00', $init['expire_at']);

        $complete = $gateway->completeRequest(array('reqid' => $init['reqid']));

        $this->assertTrue($complete['status']);
        $this->assertSame('00', $complete['ResponseCode']);
        $this->assertSame('TOK-7', $complete['Token']);
        $this->assertSame('456445******4564', $complete['CardNumber']);
        $this->assertSame('PAYMENT_COMPLETE', $transport->lastRequestPayload()['operation']);
        $this->assertSame('REQ-1', $transport->lastRequestPayload()['requestData']['reqid']);
    }

    public function testInitReportsFailureWhenTheGatewayReturnsNoReqid()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('paymentPageUrl' => null),
        ));

        $result = GatewayTestFactory::vault($transport)->initRequest(array(
            'total_amount' => 1500, 'service_fee_amount' => 0, 'payment_amount' => 1500,
        ));

        $this->assertFalse($result['status']);
        $this->assertSame('Payment init request failed', $result['msg']);
    }

    public function testInitRequiresAReturnUrlBeforeSendingAnything()
    {
        $transport = new FakeHttpTransport();

        $result = GatewayTestFactory::vault($transport, array('return_url' => ''))->initRequest(array(
            'total_amount' => 1500, 'service_fee_amount' => 0, 'payment_amount' => 1500,
        ));

        $this->assertFalse($result['status']);
        $this->assertStringContainsString('SAMPATH_RETURN_URL', $result['msg']);
        $this->assertSame(0, $transport->requestCount());
    }

    public function testAConfiguredCancelUrlIsSent()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('reqid' => 'REQ-1', 'paymentPageUrl' => 'https://x.test/p'),
        ));

        GatewayTestFactory::vault($transport, array(
            'cancel_url' => 'https://merchant.example.test/cancel',
        ))->initRequest(array('total_amount' => 1, 'service_fee_amount' => 0, 'payment_amount' => 1));

        $this->assertSame(
            'https://merchant.example.test/cancel',
            $transport->lastRequestPayload()['requestData']['redirect']['cancelUrl']
        );
    }

    public function testCompleteToleratesAnEnvelopeWithoutACreditCardNode()
    {
        $envelope = $this->completeEnvelope();
        unset($envelope['responseData']['creditCard']);

        $transport = (new FakeHttpTransport())->willRespondWithJson($envelope);

        $result = GatewayTestFactory::vault($transport)->completeRequest(array('reqid' => 'REQ-1'));

        $this->assertTrue($result['status']);
        $this->assertNull($result['CardNumber']);
    }

    /**
     * @return array<string,mixed>
     */
    private function completeEnvelope()
    {
        return json_decode(
            file_get_contents(dirname(__DIR__) . '/Fixtures/responses/payment-complete-success.json'),
            true
        );
    }
}
