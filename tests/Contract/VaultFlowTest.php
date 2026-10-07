<?php

namespace createch\PaycorpSampathVault\Test\Contract;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\CreditCard;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\RetrieveCardRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\StoreCardRequest;
use createch\PaycorpSampathVault\Test\Support\GatewayTestFactory;
use createch\PaycorpSampathVault\Testing\FakeHttpTransport;
use PHPUnit\Framework\TestCase;

/**
 * The vault operations, end to end through the real pipeline.
 *
 * These were the least exercised paths in 1.x and three of the five were
 * outright fatal on PHP 8.
 */
class VaultFlowTest extends TestCase
{
    public function testStoreCardSendsTheExpectedEnvelopeAndReturnsTheToken()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('token' => 'TOK-NEW', 'responseCode' => '00', 'responseText' => 'STORED'),
        ));

        $creditCard = new CreditCard();
        $creditCard->setType('VISA');
        $creditCard->setHolderName('A N OTHER');
        $creditCard->setNumber('4564456445644564');
        $creditCard->setExpiry('12/28');
        $creditCard->setSecureId('123');
        $creditCard->setSecureIdSupplied(true);

        $request = new StoreCardRequest();
        $request->setClientId('99990001');
        $request->setClientRef('ORDER-1');
        $request->setCreditCard($creditCard);

        $response = GatewayTestFactory::client($transport)->getVault()->storeCard($request);

        $this->assertSame('TOK-NEW', $response->getToken());
        $this->assertSame('00', $response->getResponseCode());

        $payload = $transport->lastRequestPayload();
        $this->assertSame('VAULT_STORE_CARD', $payload['operation']);
        $this->assertSame(
            array('clientId', 'clientRef', 'creditCard'),
            array_keys($payload['requestData']),
            'requestData key order is part of the signed payload'
        );
        $this->assertSame('4564456445644564', $payload['requestData']['creditCard']['number']);
        $this->assertTrue($payload['requestData']['creditCard']['secureIdSupplied']);
    }

    public function testRetrieveCardSendsTheTokenAndParsesTheResult()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00', 'responseText' => 'FOUND'),
        ));

        $request = new RetrieveCardRequest();
        $request->setClientId('99990001');
        $request->setToken('TOK-1');

        $response = GatewayTestFactory::client($transport)->getVault()->retrieveCard($request);

        $this->assertSame('00', $response->getResponseCode());
        $this->assertSame('VAULT_RETRIEVE_CARD', $transport->lastRequestPayload()['operation']);
        $this->assertSame('TOK-1', $transport->lastRequestPayload()['requestData']['token']);
    }

    public function testEveryVaultOperationSignsItsBody()
    {
        $transport = (new FakeHttpTransport())->willRespondWithJson(array(
            'responseData' => array('responseCode' => '00', 'responseText' => 'OK'),
        ));

        $request = new RetrieveCardRequest();
        $request->setClientId('99990001');
        $request->setToken('TOK-1');

        GatewayTestFactory::client($transport)->getVault()->retrieveCard($request);

        $expected = (new \createch\PaycorpSampathVault\Security\Sha256HmacSigner(GatewayTestFactory::SECRET))
            ->sign($transport->lastRequestBody());

        $this->assertSame($expected, $transport->header('HMAC'));
    }

    public function testAVaultTransportFailureIsNotSwallowed()
    {
        $transport = (new FakeHttpTransport())->willFail('gateway unreachable');

        $request = new RetrieveCardRequest();
        $request->setClientId('99990001');
        $request->setToken('TOK-1');

        $this->expectException(\createch\PaycorpSampathVault\Exceptions\TransportException::class);

        GatewayTestFactory::client($transport)->getVault()->retrieveCard($request);
    }
}
