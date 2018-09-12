<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientVault;

class VerifyTokenResponse {

    private $token;
    private $clientRef;
    private $responseCode;
    private $responseText;

    public function __construct() {
        
    }
    
    public function getToken() {
        return $this->token;
    }

    public function setToken($token) {
        $this->token = $token;
    }
    
    public function getClientRef() {
        return $this->clientRef;
    }

    public function setClientRef($clientRef) {
        $this->clientRef = $clientRef;
    }

    public function getResponseCode() {
        return $this->responseCode;
    }

    public function setResponseCode($responseCode) {
        $this->responseCode = $responseCode;
    }

    public function getResponseText() {
        return $this->responseText;
    }

    public function setResponseText($responseText) {
        $this->responseText = $responseText;
    }

}
