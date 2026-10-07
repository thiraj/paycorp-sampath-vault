<?php

namespace createch\PaycorpSampathVault\Test\Support;

use createch\PaycorpSampathVault\Contracts\ClockInterface;

/**
 * A clock that never moves, so an outbound request body -- and therefore its
 * HMAC -- is byte-for-byte reproducible in assertions.
 */
final class FrozenClock implements ClockInterface
{
    /** @var string */
    private $timestamp;

    /**
     * @param  string  $timestamp
     */
    public function __construct($timestamp = '2026-10-07 12:00:00')
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function requestTimestamp()
    {
        return $this->timestamp;
    }
}
