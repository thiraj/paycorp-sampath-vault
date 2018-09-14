<?php
namespace createch\PaycorpSampathVault\Test;

use PaycorpSampathVault;

class PaycorpSampathVaultTest extends TestCase
{
    /**
     * Check that the multiply method returns correct result
     * @return void
     */
    public function testIPGInit()
    {
        $this->assertSame(PaycorpSampathVault::IPGLoaded(), "1.0.0.1");
    }
}