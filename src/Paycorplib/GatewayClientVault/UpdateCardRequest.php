<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientVault;

class UpdateCardRequest {

    private $clientId;
    private $token;
    private $expiryDate;

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

    public function getExpiryDate() {
        return $this->expiryDate;
    }

    public function setExpiryDate($expiryDate) {
        $this->expiryDate = $expiryDate;
    }

}
