<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent;

class RequestHeader {

    private $authToken;
    private $hmac;
    
    public function getAuthToken() {
        return $this->authToken;
    }

    public function setAuthToken($authToken) {
        $this->authToken = $authToken;
    }

    public function getHmac() {
        return $this->hmac;
    }

    public function setHmac($hmac) {
        $this->hmac = $hmac;
    }

}
