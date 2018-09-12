<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils;

class HmacUtils {
    
    private function __construct() {
    }

    public static function genarateHmac($secret, $data) {
        $Hmac = hash_hmac('sha256', utf8_decode($data), utf8_decode($secret), FALSE);
        return $Hmac;
    }

}
