<?php

namespace createch\PaycorpSampathVault\Contracts;

use createch\PaycorpSampathVault\Configuration\TransportOptions;
use createch\PaycorpSampathVault\Http\HttpResponse;

/**
 * Sends a signed request body to the gateway and returns what came back.
 *
 * This is the single seam that separates gateway logic from the network, so
 * every request-building and response-parsing path is testable without
 * touching a live payment endpoint.
 *
 * Implementations MUST NOT retry: a retried payment request can take the
 * money twice. Retry policy belongs to the caller, which knows whether the
 * operation is idempotent.
 */
interface HttpTransportInterface
{
    /**
     * @param  string            $url
     * @param  string            $body     Raw JSON request body, already signed.
     * @param  array<int,string> $headers  Pre-formatted "Name: value" header lines.
     * @param  TransportOptions  $options
     * @return HttpResponse
     *
     * @throws \createch\PaycorpSampathVault\Exceptions\TransportException
     *         When the exchange did not complete. The transaction outcome is
     *         then UNKNOWN and must be reconciled, never assumed failed.
     */
    public function post($url, $body, array $headers, TransportOptions $options);
}
