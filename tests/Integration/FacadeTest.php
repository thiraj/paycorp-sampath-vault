<?php

namespace createch\PaycorpSampathVault\Test\Integration;

use createch\PaycorpSampathVault\Contracts\HttpTransportInterface;
use createch\PaycorpSampathVault\PaycorpSampathVault;
use createch\PaycorpSampathVault\PaycorpSampathVaultFacade;
use createch\PaycorpSampathVault\Test\TestCase;
use createch\PaycorpSampathVault\Testing\FakeHttpTransport;

/**
 * The facade is the surface most integrations use, so its accessor and
 * forwarded methods must not drift.
 */
class FacadeTest extends TestCase
{
    public function testTheFacadeResolvesTheGateway()
    {
        $this->assertInstanceOf(PaycorpSampathVault::class, PaycorpSampathVaultFacade::getFacadeRoot());
    }

    public function testTheAliasRegisteredByAutoDiscoveryWorks()
    {
        $this->assertSame('1.0.0.1', \PaycorpSampathVault::IPGLoaded());
    }

    public function testAPaymentCanBeMadeThroughTheFacade()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array(
                'txnReference' => 'TXN-1', 'responseCode' => '00', 'responseText' => 'APPROVED',
            ),
        ));
        $this->app->instance(HttpTransportInterface::class, $transport);

        $result = \PaycorpSampathVault::realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 1500,
        ));

        $this->assertTrue($result['status']);
        $this->assertSame('TXN-1', $result['TxnReference']);
    }

    public function testAHostedPaymentSessionCanBeOpenedThroughTheFacade()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array(
                'reqid' => 'REQ-1',
                'expireAt' => '2026-10-07 12:30:00',
                'paymentPageUrl' => 'https://sampath.example.test/pay/REQ-1',
            ),
        ));
        $this->app->instance(HttpTransportInterface::class, $transport);

        $result = \PaycorpSampathVault::initRequest(array(
            'total_amount' => 1500, 'service_fee_amount' => 50, 'payment_amount' => 1450,
            'clientRef' => 'ORDER-1', 'comment' => 'Order 1',
        ));

        $this->assertTrue($result['status']);
        $this->assertSame('REQ-1', $result['reqid']);
        $this->assertSame('https://sampath.example.test/pay/REQ-1', $result['payment_page_url']);
    }

    public function testCompleteRequestReturnsTheHistoricalKeySet()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(
            json_decode(file_get_contents(dirname(__DIR__) . '/Fixtures/responses/payment-complete-success.json'), true)
        );
        $this->app->instance(HttpTransportInterface::class, $transport);

        $result = \PaycorpSampathVault::completeRequest(array('reqid' => 'REQ-1'));

        // Pinned: integrations read these exact keys.
        foreach (array(
            'ResponseCode', 'ClientID', 'TransactionType', 'CardNumber', 'ExpireAt',
            'ClientRef', 'Comment', 'TxnReference', 'ResponseText', 'AuthCode',
            'ExtraData', 'Token', 'status',
        ) as $key) {
            $this->assertArrayHasKey($key, $result, $key . ' is part of the 1.x public contract');
        }

        $this->assertTrue($result['status']);
        $this->assertSame('456445******4564', $result['CardNumber']);
        $this->assertSame('TOK-7', $result['Token']);
    }
}
