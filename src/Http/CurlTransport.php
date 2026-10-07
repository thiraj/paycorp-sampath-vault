<?php

namespace createch\PaycorpSampathVault\Http;

use createch\PaycorpSampathVault\Configuration\TransportOptions;
use createch\PaycorpSampathVault\Contracts\HttpTransportInterface;
use createch\PaycorpSampathVault\Contracts\RedactorInterface;
use createch\PaycorpSampathVault\Exceptions\TransportException;
use createch\PaycorpSampathVault\Security\SensitiveDataRedactor;

/**
 * The only class in this package that touches the network.
 *
 * WHAT CHANGED FROM THE LEGACY RestClient
 * ---------------------------------------
 * 1. TLS verification is on. The old client set CURLOPT_SSL_VERIFYPEER to
 *    false, so every PAN and CVV it sent crossed a connection that accepted
 *    any certificate. Verification can only be disabled explicitly, and the
 *    endpoint must then also be whitelisted as insecure in configuration.
 * 2. A failed exchange raises TransportException instead of returning false.
 *    The old behaviour let `false` flow into json_decode(), producing null,
 *    which every response parser read as a set of empty fields -- a network
 *    outage therefore looked like a SUCCESSFUL transaction with a blank
 *    response code.
 * 3. There is a total timeout, not just a connect timeout, so a stalled
 *    gateway cannot pin a PHP worker indefinitely.
 * 4. Redirects are not followed. A 30x from a payment endpoint is a
 *    misconfiguration or an attack, never something to chase while carrying
 *    an auth token in the headers.
 * 5. It never retries. A retried authorisation can capture the money twice;
 *    only the caller knows whether an operation is safe to repeat.
 */
final class CurlTransport implements HttpTransportInterface
{
    /** @var RedactorInterface */
    private $redactor;

    /**
     * @param  RedactorInterface|null  $redactor
     */
    public function __construct(?RedactorInterface $redactor = null)
    {
        $this->redactor = $redactor ?: new SensitiveDataRedactor();
    }

    /**
     * @param  string             $url
     * @param  string             $body
     * @param  array<int,string>  $headers
     * @param  TransportOptions   $options
     * @return HttpResponse
     *
     * @throws TransportException
     */
    public function post($url, $body, array $headers, TransportOptions $options)
    {
        $handle = curl_init();

        if ($handle === false) {
            throw TransportException::connectionFailed('curl_init() failed');
        }

        try {
            curl_setopt_array($handle, $this->buildOptions($url, $body, $headers, $options));

            $responseBody = curl_exec($handle);
            $errorNumber = curl_errno($handle);
            $errorMessage = curl_error($handle);
            $statusCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        } finally {
            // No-op on PHP 8+, still required on PHP 7 to release the handle.
            curl_close($handle);
        }

        // curl_exec() is declared string|bool. Anything that is not a string
        // means we did not get a body, and a payment response we cannot read is
        // a failure -- never an empty success.
        if (! is_string($responseBody) || $errorNumber !== 0) {
            throw TransportException::connectionFailed(
                $this->redactor->redact($errorMessage !== '' ? $errorMessage : 'unknown curl failure'),
                $errorNumber
            );
        }

        return new HttpResponse($statusCode, $responseBody);
    }

    /**
     * @param  string             $url
     * @param  string             $body
     * @param  array<int,string>  $headers
     * @param  TransportOptions   $options
     * @return array<int,mixed>
     */
    private function buildOptions($url, $body, array $headers, TransportOptions $options)
    {
        $curlOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => $options->connectTimeout(),
            CURLOPT_TIMEOUT => $options->timeout(),
            CURLOPT_USERAGENT => $options->userAgent(),

            // A payment endpoint must never redirect us elsewhere while we are
            // carrying the auth token and HMAC in the request headers.
            CURLOPT_FOLLOWLOCATION => false,

            // Pin to modern TLS. Anything below 1.2 is unacceptable for
            // cardholder data and is rejected by PCI DSS 3.2.1 onward.
            CURLOPT_SSL_VERIFYPEER => $options->verifiesPeer(),
            CURLOPT_SSL_VERIFYHOST => $options->verifiesPeer() ? 2 : 0,
        );

        if (defined('CURL_SSLVERSION_TLSv1_2')) {
            $curlOptions[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;
        }

        if ($options->caBundle() !== null) {
            $curlOptions[CURLOPT_CAINFO] = $options->caBundle();
        }

        if ($options->proxyHost() !== null) {
            $curlOptions[CURLOPT_PROXY] = $options->proxyHost();

            if ($options->proxyPort() !== null) {
                $curlOptions[CURLOPT_PROXYPORT] = $options->proxyPort();
            }
        }

        return $curlOptions;
    }
}
