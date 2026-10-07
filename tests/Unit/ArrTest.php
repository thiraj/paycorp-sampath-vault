<?php

namespace createch\PaycorpSampathVault\Test\Unit;

use createch\PaycorpSampathVault\Support\Arr;
use PHPUnit\Framework\TestCase;

/**
 * Every response parser reads gateway payloads through Arr, so its treatment
 * of absent and null keys defines the package's fallback behaviour. It must
 * match the isset() checks it replaced exactly.
 */
class ArrTest extends TestCase
{
    public function testItReadsANestedValue()
    {
        $data = array('responseData' => array('creditCard' => array('number' => '411111')));

        $this->assertSame('411111', Arr::get($data, 'responseData.creditCard.number'));
    }

    public function testItReturnsTheDefaultForAMissingKey()
    {
        $this->assertSame('fallback', Arr::get(array(), 'responseData.responseCode', 'fallback'));
    }

    public function testItReturnsTheDefaultWhenAnIntermediateSegmentIsMissing()
    {
        $data = array('responseData' => array());

        $this->assertSame(0, Arr::get($data, 'responseData.transactionAmount.totalAmount', 0));
    }

    public function testItTreatsAPresentNullAsAbsentJustLikeIsset()
    {
        // The guarded legacy reads used isset(), which is false for null, so a
        // present-but-null clientRef became 0. That must not change.
        $data = array('responseData' => array('clientRef' => null));

        $this->assertSame(0, Arr::get($data, 'responseData.clientRef', 0));
    }

    public function testItReturnsTheDefaultWhenAnIntermediateValueIsNotAnArray()
    {
        $data = array('responseData' => 'not-an-array');

        $this->assertNull(Arr::get($data, 'responseData.responseCode'));
    }

    public function testItReturnsTheDefaultForNonArrayInput()
    {
        // json_decode() of a failed transport returned null; this is the guard
        // that stopped that null becoming a set of empty fields unnoticed.
        $this->assertSame('x', Arr::get(null, 'responseData.responseCode', 'x'));
        $this->assertSame('x', Arr::get(false, 'responseData.responseCode', 'x'));
        $this->assertSame('x', Arr::get('string', 'responseData.responseCode', 'x'));
    }

    public function testHasDistinguishesAPresentNullFromAnAbsentKey()
    {
        $data = array('responseData' => null);

        $this->assertTrue(Arr::has($data, 'responseData'), 'the key exists even though its value is null');
        $this->assertFalse(Arr::has($data, 'error'));
    }

    public function testGetStringCoercesScalars()
    {
        $data = array('a' => array('int' => 42, 'float' => 1.5, 'true' => true, 'false' => false));

        $this->assertSame('42', Arr::getString($data, 'a.int'));
        $this->assertSame('1.5', Arr::getString($data, 'a.float'));
        $this->assertSame('true', Arr::getString($data, 'a.true'));
        $this->assertSame('false', Arr::getString($data, 'a.false'));
    }

    public function testGetStringRejectsArraysAndFallsBackToTheDefault()
    {
        $data = array('a' => array('nested' => array('x')));

        $this->assertSame('', Arr::getString($data, 'a.nested'));
    }

    public function testGetStringReturnsTheDefaultForAMissingKey()
    {
        $this->assertSame('none', Arr::getString(array(), 'a.b', 'none'));
    }
}
