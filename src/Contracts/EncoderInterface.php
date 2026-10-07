<?php

namespace createch\PaycorpSampathVault\Contracts;

/**
 * Converts a string into the byte representation the gateway signs over.
 *
 * Extracted as a seam because the legacy client ran every HMAC input through
 * utf8_decode(), and that byte-level behaviour must be reproducible exactly
 * after utf8_decode() is removed from PHP.
 */
interface EncoderInterface
{
    /**
     * @param  string  $value
     * @return string  Raw bytes, not necessarily valid UTF-8.
     */
    public function encode($value);
}
