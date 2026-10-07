<?php

namespace createch\PaycorpSampathVault\Test\Unit;

use createch\PaycorpSampathVault\Support\SystemClock;
use PHPUnit\Framework\TestCase;

class SystemClockTest extends TestCase
{
    public function testItUsesTheWireFormatTheGatewayExpects()
    {
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (new SystemClock())->requestTimestamp()
        );
    }

    public function testAnExplicitTimezoneIsHonoured()
    {
        // The legacy client used date(), which follows the application
        // timezone, so two deployments of one merchant could stamp the same
        // transaction hours apart.
        $utc = new SystemClock('UTC');
        $colombo = new SystemClock('Asia/Colombo');

        $this->assertNotSame(
            substr($utc->requestTimestamp(), 0, 16),
            substr($colombo->requestTimestamp(), 0, 16),
            'Asia/Colombo is UTC+5:30, so the minute component must differ'
        );
    }

    public function testNullKeepsTheHostDefaultForBackwardsCompatibility()
    {
        $this->assertSame(
            substr(date('Y-m-d H:i'), 0, 16),
            substr((new SystemClock(null))->requestTimestamp(), 0, 16)
        );
    }

    public function testAnEmptyStringIsTreatedAsNoTimezone()
    {
        // An unset .env variable arrives as '' rather than null.
        $this->assertSame(
            substr(date('Y-m-d H:i'), 0, 16),
            substr((new SystemClock(''))->requestTimestamp(), 0, 16)
        );
    }
}
