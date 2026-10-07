<?php

namespace createch\PaycorpSampathVault\Test\Unit;

use createch\PaycorpSampathVault\Configuration\TransportOptions;
use createch\PaycorpSampathVault\Exceptions\TransportException;
use createch\PaycorpSampathVault\Http\CurlTransport;
use createch\PaycorpSampathVault\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the real curl-backed transport.
 *
 * No live gateway is contacted: a closed local port is enough to prove the
 * failure path, which is the behaviour that matters most here.
 */
class CurlTransportTest extends TestCase
{
    public function testAnUnreachableHostThrowsInsteadOfReturningFalse()
    {
        // THE core fix. The legacy RestClient returned curl_exec()'s false,
        // which became null in json_decode() and was then read as a set of
        // empty response fields -- a successful-looking empty payment.
        $transport = new CurlTransport();

        $this->expectException(TransportException::class);

        $transport->post(
            'http://127.0.0.1:1/unreachable',
            '{}',
            array('Content-Type: application/json'),
            new TransportOptions(array('connect_timeout' => 2, 'timeout' => 3, 'verify_peer' => false))
        );
    }

    public function testTheExceptionCarriesTheCurlErrorNumberForDiagnostics()
    {
        $transport = new CurlTransport();

        try {
            $transport->post(
                'http://127.0.0.1:1/unreachable',
                '{}',
                array(),
                new TransportOptions(array('connect_timeout' => 2, 'timeout' => 3))
            );
            $this->fail('expected a TransportException');
        } catch (TransportException $e) {
            $this->assertNotSame(0, $e->getCurlErrorNumber());
            $this->assertStringContainsString('Could not reach the Paycorp gateway', $e->getMessage());
        }
    }

    public function testAnInvalidHostnameThrows()
    {
        $transport = new CurlTransport();

        $this->expectException(TransportException::class);

        $transport->post(
            'https://this-host-does-not-exist.invalid/proxy',
            '{}',
            array(),
            new TransportOptions(array('connect_timeout' => 3, 'timeout' => 5))
        );
    }

    public function testTheMessageIsRedactedSoCurlDiagnosticsCannotLeakCardData()
    {
        $transport = new CurlTransport();

        try {
            // A PAN in the URL is pathological, but proves the redactor is wired in.
            $transport->post(
                'http://127.0.0.1:1/4564456445644564',
                '{}',
                array(),
                new TransportOptions(array('connect_timeout' => 2, 'timeout' => 3))
            );
            $this->fail('expected a TransportException');
        } catch (TransportException $e) {
            $this->assertStringNotContainsString('4564456445644564', $e->getMessage());
        }
    }

    public function testHttpResponseClassifiesStatusCodes()
    {
        $this->assertTrue((new HttpResponse(200, '{}'))->isSuccessful());
        $this->assertTrue((new HttpResponse(201, '{}'))->isSuccessful());
        $this->assertFalse((new HttpResponse(302, ''))->isSuccessful());
        $this->assertFalse((new HttpResponse(401, ''))->isSuccessful());
        $this->assertFalse((new HttpResponse(500, ''))->isSuccessful());
        $this->assertFalse((new HttpResponse(0, ''))->isSuccessful());
    }

    public function testUnexpectedStatusExceptionSummarisesALongBody()
    {
        $exception = TransportException::unexpectedStatus(502, str_repeat('x', 5000));

        $this->assertSame(502, $exception->getStatusCode());
        $this->assertLessThan(700, strlen($exception->getMessage()));
    }

    public function testUnexpectedStatusExceptionDescribesAnEmptyBody()
    {
        $this->assertStringContainsString('<empty>', TransportException::unexpectedStatus(500, '')->getMessage());
    }
}
