<?php

namespace createch\PaycorpSampathVault\Support;

use createch\PaycorpSampathVault\Contracts\ClockInterface;
use DateTime;
use DateTimeZone;

/**
 * Stamps requestDate from the system clock in an explicit timezone.
 *
 * The legacy client called date('Y-m-d H:i:s'), which follows the
 * application's timezone. Two deployments of the same merchant could
 * therefore stamp the same transaction hours apart, which matters for
 * settlement windows and duplicate detection. The timezone is now explicit
 * and configurable, defaulting to the host timezone to preserve the existing
 * behaviour for anyone who has not set one.
 */
final class SystemClock implements ClockInterface
{
    const FORMAT = 'Y-m-d H:i:s';

    /** @var DateTimeZone|null */
    private $timezone;

    /**
     * @param  string|null  $timezone  An identifier such as "Asia/Colombo".
     *                                 Null keeps the host default.
     */
    public function __construct($timezone = null)
    {
        $this->timezone = ($timezone === null || $timezone === '')
            ? null
            : new DateTimeZone($timezone);
    }

    /**
     * @return string
     */
    public function requestTimestamp()
    {
        $now = new DateTime('now');

        if ($this->timezone !== null) {
            $now->setTimezone($this->timezone);
        }

        return $now->format(self::FORMAT);
    }
}
