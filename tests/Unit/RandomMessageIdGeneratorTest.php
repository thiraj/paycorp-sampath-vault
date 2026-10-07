<?php

namespace createch\PaycorpSampathVault\Test\Unit;

use createch\PaycorpSampathVault\Support\RandomMessageIdGenerator;
use PHPUnit\Framework\TestCase;

/**
 * The msgId is what the gateway uses to recognise a duplicate submission, so
 * a collision between two concurrent requests risks one payment being treated
 * as a replay of another. The legacy generator used mt_rand().
 */
class RandomMessageIdGeneratorTest extends TestCase
{
    /** @var RandomMessageIdGenerator */
    private $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new RandomMessageIdGenerator();
    }

    public function testItKeepsTheLegacyWireFormat()
    {
        // The gateway has always received 8-4-4-4-12 upper-case hex.
        $this->assertMatchesRegularExpression(
            '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/',
            $this->generator->generate()
        );
    }

    public function testItIsExactlyThirtySixCharacters()
    {
        $this->assertSame(36, strlen($this->generator->generate()));
    }

    public function testItSetsTheRfc4122VersionAndVariantBits()
    {
        $id = $this->generator->generate();

        $this->assertSame('4', substr($id, 14, 1), 'version nibble must be 4');
        $this->assertContains(substr($id, 19, 1), array('8', '9', 'A', 'B'), 'variant bits must be RFC 4122');
    }

    public function testItDoesNotCollideAcrossManyGenerations()
    {
        $ids = array();

        for ($i = 0; $i < 20000; $i++) {
            $ids[$this->generator->generate()] = true;
        }

        $this->assertCount(20000, $ids, 'a duplicate msgId risks a payment being rejected as a replay');
    }

    public function testConsecutiveIdsShareNoCommonPrefix()
    {
        // mt_rand-based generators seeded per process produced correlated
        // output; a CSPRNG must not.
        $first = $this->generator->generate();
        $second = $this->generator->generate();

        $this->assertNotSame(substr($first, 0, 8), substr($second, 0, 8));
    }

    public function testTheLegacyStaticHelperStillWorks()
    {
        $id = \createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\CommonUtils::generateGUID();

        $this->assertMatchesRegularExpression(
            '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/',
            $id
        );
    }
}
