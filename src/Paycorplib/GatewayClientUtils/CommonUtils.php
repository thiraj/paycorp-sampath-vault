<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils;

use createch\PaycorpSampathVault\Support\RandomMessageIdGenerator;

/**
 * Backwards-compatible identifier helper.
 *
 * Delegates to RandomMessageIdGenerator, which draws from the CSPRNG instead
 * of mt_rand(). The output keeps the original 8-4-4-4-12 upper-case
 * hexadecimal shape, so the gateway sees the format it always has.
 *
 * @deprecated Inject a MessageIdGeneratorInterface instead.
 */
class CommonUtils {

    private function __construct() {
    }

    /**
     * @return string
     */
    public static function generateGUID() {
        $generator = new RandomMessageIdGenerator();

        return $generator->generate();
    }

}
