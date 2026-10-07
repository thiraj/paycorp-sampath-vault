<?php

namespace createch\PaycorpSampathVault\Test\Unit\Helpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\PaymentCompleteJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use PHPUnit\Framework\TestCase;

/**
 * The legacy helper mixed guarded and unguarded reads, producing a specific
 * and observable set of fallbacks. Callers compare against those values, so
 * each one is pinned here rather than normalised to something tidier.
 */
class PaymentCompleteJsonHelperTest extends TestCase
{
    /** @var PaymentCompleteJsonHelper */
    private $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new PaymentCompleteJsonHelper();
    }

    public function testItNowDeclaresTheInterface()
    {
        // It previously satisfied IJsonHelper only by coincidence.
        $this->assertInstanceOf(IJsonHelper::class, $this->helper);
    }

    public function testItParsesACompleteSuccessfulResponse()
    {
        $response = $this->helper->fromJson($this->fullEnvelope());

        $this->assertSame('00', $response->getResponseCode());
        $this->assertSame('99990001', $response->getClientId());
        $this->assertSame('PURCHASE', $response->getTransactionType());
        $this->assertSame('456445******4564', $response->getCreditCard()->getNumber());
        $this->assertSame('12/28', $response->getCreditCard()->getExpiry());
        $this->assertSame('ORDER-1', $response->getClientRef());
        $this->assertSame('TXN-9', $response->getTxnReference());
        $this->assertSame('TOK-7', $response->getToken());
        $this->assertSame('LKR', $response->getTransactionAmount()->getCurrency());
        $this->assertSame(1500, $response->getTransactionAmount()->getTotalAmount());
    }

    public function testMissingClientRefFallsBackToIntegerZeroNotNull()
    {
        // Pinned to the legacy value: callers may compare === 0.
        $envelope = $this->fullEnvelope();
        unset($envelope['responseData']['clientRef']);

        $this->assertSame(0, $this->helper->fromJson($envelope)->getClientRef());
    }

    public function testMissingFeeReferenceAndTokenFallBackToIntegerZero()
    {
        $envelope = $this->fullEnvelope();
        unset($envelope['responseData']['feeReference'], $envelope['responseData']['token']);

        $response = $this->helper->fromJson($envelope);

        $this->assertSame(0, $response->getFeeReference());
        $this->assertSame(0, $response->getToken());
    }

    public function testMissingCommentAndTokenResponseTextFallBackToEmptyString()
    {
        $envelope = $this->fullEnvelope();
        unset($envelope['responseData']['comment'], $envelope['responseData']['tokenResponseText']);

        $response = $this->helper->fromJson($envelope);

        $this->assertSame('', $response->getComment());
        $this->assertSame('', $response->getTokenResponseText());
    }

    public function testMissingWithholdingAmountFallsBackToZero()
    {
        $envelope = $this->fullEnvelope();
        unset($envelope['responseData']['transactionAmount']['withholdingAmount']);

        $this->assertSame(0, $this->helper->fromJson($envelope)->getTransactionAmount()->getWithholdingAmount());
    }

    public function testAPresentNullIsTreatedTheSameAsAnAbsentKey()
    {
        // The legacy guards used isset(), which is false for null.
        $envelope = $this->fullEnvelope();
        $envelope['responseData']['clientRef'] = null;
        $envelope['responseData']['comment'] = null;

        $response = $this->helper->fromJson($envelope);

        $this->assertSame(0, $response->getClientRef());
        $this->assertSame('', $response->getComment());
    }

    public function testPreviouslyUnguardedFieldsFallBackToNull()
    {
        // These raised a PHP warning and yielded null before; the value is
        // unchanged, only the warning is gone.
        $response = $this->helper->fromJson(array('responseData' => array()));

        $this->assertNull($response->getResponseCode());
        $this->assertNull($response->getTxnReference());
        $this->assertNull($response->getAuthCode());
        $this->assertNull($response->getSettlementDate());
    }

    public function testAnEnvelopeWithNoCreditCardDoesNotFatal()
    {
        $envelope = $this->fullEnvelope();
        unset($envelope['responseData']['creditCard']);

        $response = $this->helper->fromJson($envelope);

        $this->assertNotNull($response->getCreditCard());
        $this->assertNull($response->getCreditCard()->getNumber());
    }

    public function testOutboundRequestShapeIsUnchanged()
    {
        // The HMAC is computed over this exact JSON, so key order and types
        // must not drift.
        $request = new \createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentCompleteRequest();
        $request->setClientId('99990001');
        $request->setReqid('REQ-1');

        $envelope = new \createch\PaycorpSampathVault\Paycorplib\GatewayClientRoot\PaycorpRequest();
        $envelope->setMsgId('MSG-1');
        $envelope->setOperation('PAYMENT_COMPLETE');
        $envelope->setRequestDate('2026-10-07 12:00:00');
        $envelope->setRequestData($request);

        $this->assertSame(
            array('version', 'msgId', 'operation', 'requestDate', 'validateOnly', 'requestData'),
            array_keys($this->helper->toJson($envelope))
        );
        $this->assertSame(
            array('clientId', 'reqid'),
            array_keys($this->helper->toJson($envelope)['requestData'])
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function fullEnvelope()
    {
        return json_decode(
            file_get_contents(dirname(__DIR__, 2) . '/Fixtures/responses/payment-complete-success.json'),
            true
        );
    }
}
