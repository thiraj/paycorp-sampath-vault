<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils;

use createch\PaycorpSampathVault\Security\Sha256HmacSigner;

/**
 * Backwards-compatible HMAC helper.
 *
 * Retained verbatim in its public shape -- original method name and typo
 * included -- because integrations call it directly. It now delegates to
 * Sha256HmacSigner, which reproduces the removed utf8_decode() byte for byte
 * (see Latin1Encoder) so the digest is unchanged on every PHP version.
 *
 * @deprecated Inject a SignerInterface instead; this static helper cannot be
 *             substituted in a test.
 */
class HmacUtils {

    private function __construct() {
    }

    /**
     * Original spelling, kept so existing callers do not break.
     *
     * @param  string  $secret
     * @param  string  $data
     * @return string  Lower-case hexadecimal digest.
     */
    public static function genarateHmac($secret, $data) {
        return self::generateHmac($secret, $data);
    }

    /**
     * Correctly spelled alias.
     *
     * @param  string  $secret
     * @param  string  $data
     * @return string
     */
    public static function generateHmac($secret, $data) {
        $signer = new Sha256HmacSigner($secret);

        return $signer->sign($data);
    }

    /**
     * Constant-time signature check, for validating gateway callbacks.
     *
     * @param  string  $secret
     * @param  string  $data
     * @param  string  $signature
     * @return bool
     */
    public static function verifyHmac($secret, $data, $signature) {
        $signer = new Sha256HmacSigner($secret);

        return $signer->verify($data, $signature);
    }

}
