<?php

namespace createch\PaycorpSampathVault\Support;

use createch\PaycorpSampathVault\Contracts\MessageIdGeneratorInterface;
use createch\PaycorpSampathVault\Exceptions\PaycorpException;
use Exception;

/**
 * Generates the per-request msgId as an RFC 4122 version 4 UUID.
 *
 * The legacy generator used mt_rand(), a Mersenne Twister seeded from the
 * process -- predictable, and prone to collisions across forked workers. The
 * msgId is what the gateway uses to recognise a duplicate submission, so a
 * collision between two concurrent requests risks one payment being treated
 * as a replay of another. This uses the CSPRNG instead.
 *
 * Output keeps the legacy 8-4-4-4-12 upper-case hexadecimal shape so the
 * gateway sees exactly the format it has always received.
 */
final class RandomMessageIdGenerator implements MessageIdGeneratorInterface
{
    /**
     * @return string
     *
     * @throws \createch\PaycorpSampathVault\Exceptions\PaycorpException
     *         When no cryptographically secure source is available. Failing is
     *         correct here: a predictable msgId weakens replay protection.
     */
    public function generate()
    {
        $bytes = $this->randomBytes(16);

        // Set the version (4) and variant (RFC 4122) bits.
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        $hex = strtoupper(bin2hex($bytes));

        return substr($hex, 0, 8) . '-'
            . substr($hex, 8, 4) . '-'
            . substr($hex, 12, 4) . '-'
            . substr($hex, 16, 4) . '-'
            . substr($hex, 20, 12);
    }

    /**
     * @param  positive-int  $length
     * @return string
     *
     * @throws \createch\PaycorpSampathVault\Exceptions\PaycorpException
     */
    private function randomBytes($length)
    {
        try {
            return random_bytes($length);
        } catch (Exception $e) {
            throw new PaycorpException(
                'No cryptographically secure random source is available, so a safe '
                . 'gateway message id cannot be generated.',
                0,
                $e
            );
        }
    }
}
