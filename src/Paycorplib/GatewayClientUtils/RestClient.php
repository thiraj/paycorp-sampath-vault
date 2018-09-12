<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils;

class RestClient {
    
    private function __construct() {
    }

    public static function sendRequest($config, $jsonRequest, $headers) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config->getServiceEndpoint());
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequest);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

}
