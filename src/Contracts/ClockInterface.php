<?php

namespace createch\PaycorpSampathVault\Contracts;

/**
 * Supplies the requestDate stamped on every gateway request.
 *
 * Injectable so that request-body assertions are deterministic, and so the
 * gateway timezone can be pinned independently of the application timezone.
 */
interface ClockInterface
{
    /**
     * @return string Formatted as Y-m-d H:i:s, in the gateway's timezone.
     */
    public function requestTimestamp();
}
