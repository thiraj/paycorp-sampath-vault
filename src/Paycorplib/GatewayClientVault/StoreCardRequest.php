<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientVault;

class StoreCardRequest {

    private $clientId;
    private $clientRef;
    private $creditCard;

    public function __construct() {
        
    }

    public function getClientId() {
        return $this->clientId;
    }

    public function setClientId($clientId) {
        $this->clientId = $clientId;
    }

    public function getClientRef() {
        return $this->clientRef;
    }

    public function setClientRef($clientRef) {
        $this->clientRef = $clientRef;
    }

    public function getCreditCard() {
        return $this->creditCard;
    }

    public function setCreditCard($creditCard) {
        $this->creditCard = $creditCard;
    }

}
