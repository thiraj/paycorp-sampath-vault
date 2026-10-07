<?php

namespace createch\PaycorpSampathVault\Security;

use createch\PaycorpSampathVault\Contracts\EncoderInterface;
use createch\PaycorpSampathVault\Contracts\SignerInterface;

/**
 * HMAC-SHA256 request signer, wire-compatible with the legacy implementation.
 *
 * Both the payload and the secret are passed through the encoder first,
 * reproducing the utf8_decode() the gateway's digest was always computed
 * over. See Latin1Encoder for why that matters.
 */
final class Sha256HmacSigner implements SignerInterface
{
    const ALGORITHM = 'sha256';

    /** @var string */
    private $secret;

    /** @var EncoderInterface */
    private $encoder;

    /**
     * @param  string                 $secret
     * @param  EncoderInterface|null  $encoder
     */
    public function __construct($secret, ?EncoderInterface $encoder = null)
    {
        $this->secret = (string) $secret;
        $this->encoder = $encoder ?: new Latin1Encoder();
    }

    /**
     * @param  string  $payload
     * @return string  Lower-case hexadecimal digest.
     */
    public function sign($payload)
    {
        return hash_hmac(
            self::ALGORITHM,
            $this->encoder->encode($payload),
            $this->encoder->encode($this->secret),
            false
        );
    }

    /**
     * @param  string  $payload
     * @param  string  $signature
     * @return bool
     */
    public function verify($payload, $signature)
    {
        // hash_equals, not ===: a short-circuiting comparison lets an attacker
        // recover the digest byte by byte from response timing.
        return hash_equals($this->sign($payload), (string) $signature);
    }
}
