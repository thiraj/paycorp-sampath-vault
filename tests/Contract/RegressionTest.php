<?php

namespace createch\PaycorpSampathVault\Test\Contract;

use createch\PaycorpSampathVault\Exceptions\UnsupportedOperationException;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\CreditTransaction;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums\Operation;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\DeleteTokenRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\UpdateCardRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\VerifyTokenRequest;
use createch\PaycorpSampathVault\PaycorpSampathVault;
use createch\PaycorpSampathVault\Test\Support\GatewayTestFactory;
use createch\PaycorpSampathVault\Testing\FakeHttpTransport;
use PHPUnit\Framework\TestCase;

/**
 * One test per defect found in the 1.x audit, so none of them can return.
 * Each test names the original symptom.
 */
class RegressionTest extends TestCase
{
    public function testVaultDeleteTokenNoLongerFatalsOnPhp8()
    {
        // Was: Error "Undefined constant responseData" from
        // $responseData[responseData][responseCode] in DeleteTokenJsonHelper.
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00', 'responseText' => 'DELETED'),
        ));

        $request = new DeleteTokenRequest();
        $request->setClientId('99990001');
        $request->setToken('TOK-1');

        $response = GatewayTestFactory::client($transport)->getVault()->deleteToken($request);

        $this->assertSame('00', $response->getResponseCode());
    }

    public function testVaultVerifyTokenNoLongerFatalsOnPhp8()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00', 'responseText' => 'VALID'),
        ));

        $request = new VerifyTokenRequest();
        $request->setClientId('99990001');
        $request->setToken('TOK-1');

        $this->assertSame(
            '00',
            GatewayTestFactory::client($transport)->getVault()->verifyToken($request)->getResponseCode()
        );
    }

    public function testVaultUpdateCardNoLongerFatalsOnPhp8()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00', 'responseText' => 'UPDATED'),
        ));

        $request = new UpdateCardRequest();
        $request->setClientId('99990001');
        $request->setToken('TOK-1');
        $request->setExpiryDate('12/29');

        $this->assertSame(
            '00',
            GatewayTestFactory::client($transport)->getVault()->updateCard($request)->getResponseCode()
        );
    }

    public function testCreditTransactionGetCommentNoLongerFatalsOnPhp8()
    {
        // Was: "return comment;" -- a bare word, which is an Error on PHP 8.
        $transaction = new CreditTransaction();
        $transaction->setComment('Order 1');

        $this->assertSame('Order 1', $transaction->getComment());
    }

    public function testCreditTransactionAcceptsAStringTransactionType()
    {
        // Was typehinted TransactionType, but every caller passes the string
        // TransactionType::$PURCHASE, so the hint guaranteed a TypeError.
        $transaction = new CreditTransaction();
        $transaction->setTransactionType(
            \createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums\TransactionType::$PURCHASE
        );

        $this->assertSame('PURCHASE', $transaction->getTransactionType());
    }

    public function testThePaymentBatchOperationConstantNowExists()
    {
        // Was: Payment::batch() referenced Operation::$PAYMENT_BATCH, which was
        // never declared, so it raised "Access to undeclared static property".
        $this->assertSame('PAYMENT_BATCH', Operation::$PAYMENT_BATCH);
    }

    public function testBatchFailsWithAnExplanatoryExceptionInsteadOfAnUndeclaredPropertyError()
    {
        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessageMatches('/not implemented/');

        GatewayTestFactory::client(new FakeHttpTransport())->getPayment()->batch(new \stdClass());
    }

    public function testCompleteRequestReturnsAnArrayOnFailureNeverNull()
    {
        // Was: the catch block populated $this->response and then fell off the
        // end of the method, so the caller received null.
        $transport = (new FakeHttpTransport())->willFail('gateway unreachable');

        $result = GatewayTestFactory::vault($transport)->completeRequest(array('reqid' => 'REQ-1'));

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertSame('Payment not completed', $result['msg']);
    }

    public function testResponsesDoNotLeakBetweenCallsOnASharedInstance()
    {
        // Was: $this->response was an accumulating instance property on a
        // container singleton, so keys from one transaction -- including card
        // data -- appeared in the next caller's result.
        $transport = (new FakeHttpTransport())
            ->willRespondWithJson(array('responseData' => array(
                'txnReference' => 'TXN-1', 'responseCode' => '00', 'responseText' => 'APPROVED',
                'settlementDate' => '20261007', 'authCode' => '999999',
            )))
            ->willFail('network dropped');

        $gateway = GatewayTestFactory::vault($transport);

        $first = $gateway->realTimePayment(array('token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 100));
        $second = $gateway->realTimePayment(array('token' => 'TOK-2', 'expire_at' => '12/28', 'amount' => 200));

        $this->assertSame('TXN-1', $first['TxnReference']);
        $this->assertArrayNotHasKey('TxnReference', $second, 'stale keys must not survive into a later call');
        $this->assertArrayNotHasKey('AuthCode', $second);
    }

    public function testMissingOptionalInputKeysDoNotWarn()
    {
        // Was: $data['clientRef'] ? $data['clientRef'] : '' read the key twice
        // and warned when it was absent.
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00'),
        ));

        // failOnWarning="true" in phpunit.xml.dist makes any notice fail here.
        $result = GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 100,
        ));

        $this->assertTrue($result['status']);
        $this->assertSame('', $transport->lastRequestPayload()['requestData']['clientRef']);
    }

    public function testIpgLoadedKeepsItsHistoricalValueForeverSoIntegrationGatesDoNotBreak()
    {
        $gateway = GatewayTestFactory::vault(new FakeHttpTransport());

        $this->assertSame('1.0.0.1', $gateway->IPGLoaded());
        $this->assertSame(PaycorpSampathVault::VERSION, $gateway->version());
        $this->assertNotSame($gateway->IPGLoaded(), $gateway->version());
    }

    public function testTheLegacyHmacHelperTypoIsStillCallable()
    {
        $this->assertTrue(method_exists(
            \createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\HmacUtils::class,
            'genarateHmac'
        ));
    }

    public function testClientConfigDebugOutputHidesTheSecret()
    {
        $config = \createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig::fromGatewayConfiguration(
            GatewayTestFactory::configuration()
        );

        $this->assertStringNotContainsString(
            GatewayTestFactory::SECRET,
            print_r($config->__debugInfo(), true)
        );
    }

    public function testNothingIsEchoedToOutputDuringARequest()
    {
        // Was: BaseFacade echoed the request and response bodies in HTML
        // alert divs, printing PANs and HMACs into the HTTP response.
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00'),
        ));

        ob_start();
        GatewayTestFactory::vault($transport)->realTimePayment(array(
            'token' => 'TOK-1', 'expire_at' => '12/28', 'amount' => 100,
        ));
        $printed = ob_get_clean();

        $this->assertSame('', $printed);
    }
}
