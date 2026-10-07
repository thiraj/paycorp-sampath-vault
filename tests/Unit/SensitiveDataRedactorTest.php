<?php

namespace createch\PaycorpSampathVault\Test\Unit;

use createch\PaycorpSampathVault\Security\SensitiveDataRedactor;
use PHPUnit\Framework\TestCase;

/**
 * Nothing that reaches a log or an HTTP response may carry card data or
 * credentials. The legacy client returned raw exception messages to callers
 * and echoed whole request bodies, so all of this could leak.
 */
class SensitiveDataRedactorTest extends TestCase
{
    /** @var SensitiveDataRedactor */
    private $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SensitiveDataRedactor();
    }

    public function testItMasksAPanInJsonKeepingTheLastFourDigits()
    {
        $output = $this->redactor->redact('{"number":"4564456445644564"}');

        $this->assertStringNotContainsString('4564456445644564', $output);
        // The last four stay so a payment remains traceable in support.
        $this->assertStringContainsString('4564', $output);
    }

    public function testItRemovesTheCvvEntirely()
    {
        // PCI DSS forbids storing a CVV in any form, masked or not.
        $output = $this->redactor->redact('{"secureId":"123","cvv":"456"}');

        $this->assertStringNotContainsString('123', $output);
        $this->assertStringNotContainsString('456', $output);
    }

    public function testItRemovesVaultTokens()
    {
        $output = $this->redactor->redact('{"token":"tok_abc123secret"}');

        $this->assertStringNotContainsString('tok_abc123secret', $output);
    }

    public function testItRemovesTheHmacAndAuthTokenHeaders()
    {
        $output = $this->redactor->redact(
            "HMAC: 9f86d081884c7d659a2feaa0c55ad015a3bf4f1b\nAUTHTOKEN: 11112222-3333-4444-5555"
        );

        $this->assertStringNotContainsString('9f86d081884c7d659a2feaa0c55ad015a3bf4f1b', $output);
        $this->assertStringNotContainsString('11112222-3333-4444-5555', $output);
        $this->assertStringContainsString('[REDACTED]', $output);
    }

    public function testItRemovesTheHmacSecret()
    {
        $output = $this->redactor->redact('hmac_secret=fake-hmac-secret-value and more');

        $this->assertStringNotContainsString('fake-hmac-secret-value', $output);
    }

    public function testItMasksABarePanNotAttachedToAnyKey()
    {
        $output = $this->redactor->redact('curl error while sending 4564456445644564 upstream');

        $this->assertStringNotContainsString('4564456445644564', $output);
    }

    public function testItLeavesOrdinaryDiagnosticsReadable()
    {
        $message = 'Could not reach the Paycorp gateway: Connection timed out after 60001 ms';

        // A useful error message is the whole point; over-redaction that
        // destroys the diagnostic is also a failure.
        $this->assertStringContainsString('Connection timed out', $this->redactor->redact($message));
    }

    public function testItHandlesAnEmptyString()
    {
        $this->assertSame('', $this->redactor->redact(''));
    }

    public function testItDoesNotMaskShortNumbersLikeAmountsOrResponseCodes()
    {
        $output = $this->redactor->redact('amount 1500 responseCode 00 settlementDate 20261007');

        $this->assertStringContainsString('1500', $output);
        $this->assertStringContainsString('20261007', $output);
    }
}
