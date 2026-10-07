<?php

namespace createch\PaycorpSampathVault\Contracts;

/**
 * Produces the HMAC that authenticates a request body to the gateway.
 */
interface SignerInterface
{
    /**
     * @param  string  $payload  The exact request body that will be sent.
     * @return string            Lower-case hexadecimal digest.
     */
    public function sign($payload);

    /**
     * Constant-time comparison of a received signature against the expected one.
     *
     * Used when validating gateway callbacks; a plain === comparison here
     * would leak the digest one byte at a time through response timing.
     *
     * @param  string  $payload
     * @param  string  $signature
     * @return bool
     */
    public function verify($payload, $signature);
}
