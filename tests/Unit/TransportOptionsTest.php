<?php

namespace createch\PaycorpSampathVault\Test\Unit;

use createch\PaycorpSampathVault\Configuration\TransportOptions;
use PHPUnit\Framework\TestCase;

class TransportOptionsTest extends TestCase
{
    public function testSecureDefaults()
    {
        $options = new TransportOptions();

        $this->assertTrue($options->verifiesPeer(), 'TLS verification must default to on');
        $this->assertSame(60, $options->connectTimeout());
        $this->assertSame(120, $options->timeout());
        $this->assertNull($options->caBundle());
        $this->assertNull($options->proxyHost());
    }

    public function testTheUserAgentIdentifiesThisPackage()
    {
        // The legacy client claimed to be "Mozilla/4.0 ... MSIE 8.0", which is
        // useless in a gateway access log and invites WAF rules.
        $this->assertStringContainsString('paycorp-sampath-vault', (new TransportOptions())->userAgent());
    }

    public function testVerificationCanOnlyBeDisabledExplicitly()
    {
        $this->assertFalse((new TransportOptions(array('verify_peer' => false)))->verifiesPeer());
        $this->assertTrue((new TransportOptions(array('verify_peer' => null)))->verifiesPeer());
        $this->assertTrue((new TransportOptions(array()))->verifiesPeer());
    }

    public function testItIsImmutable()
    {
        $original = new TransportOptions();
        $modified = $original->with(array('timeout' => 5));

        $this->assertSame(120, $original->timeout());
        $this->assertSame(5, $modified->timeout());
    }

    public function testEmptyStringsBecomeNullRatherThanBreakingCurl()
    {
        $options = new TransportOptions(array('ca_bundle' => '', 'proxy_host' => '', 'proxy_port' => ''));

        $this->assertNull($options->caBundle());
        $this->assertNull($options->proxyHost());
        $this->assertNull($options->proxyPort());
    }

    public function testStringBooleansFromTheEnvironmentAreInterpretedNotCast()
    {
        // (bool) "false" is true in PHP, so a plain cast would silently ignore
        // SAMPATH_VERIFY_SSL=false.
        $this->assertFalse((new TransportOptions(array('verify_peer' => 'false')))->verifiesPeer());
        $this->assertFalse((new TransportOptions(array('verify_peer' => '0')))->verifiesPeer());
        $this->assertFalse((new TransportOptions(array('verify_peer' => 'off')))->verifiesPeer());
        $this->assertFalse((new TransportOptions(array('verify_peer' => 'no')))->verifiesPeer());
        $this->assertTrue((new TransportOptions(array('verify_peer' => 'true')))->verifiesPeer());
        $this->assertTrue((new TransportOptions(array('verify_peer' => '1')))->verifiesPeer());
    }

    public function testAnUnrecognisedValueCannotAccidentallyDisableTlsVerification()
    {
        // A typo must fail safe: verification stays on.
        $this->assertTrue((new TransportOptions(array('verify_peer' => 'flase')))->verifiesPeer());
        $this->assertTrue((new TransportOptions(array('verify_peer' => 'disabled')))->verifiesPeer());
    }

    public function testToArrayRoundTrips()
    {
        $options = new TransportOptions(array('timeout' => 30, 'verify_peer' => false, 'proxy_port' => '8080'));
        $restored = new TransportOptions($options->toArray());

        $this->assertSame($options->toArray(), $restored->toArray());
        $this->assertSame(8080, $restored->proxyPort());
    }
}
