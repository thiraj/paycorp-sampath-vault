<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientVault;

class RetrieveCardRequest {

    private $clientId;
    private $token;

    public function __construct() {
        
    }

    public function getClientId() {
        return $this->clientId;
    }

    public function setClientId($clientId) {
        $this->clientId = $clientId;
    }
    
    public function getToken() {
        return $this->token;
    }

    public function setToken($token) {
        $this->token = $token;
    }

}
