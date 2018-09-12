<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment;

class PaymentCompleteRequest {

    private $clientId;
    private $reqid;
    
    public function __construct() {
    }

    public function getClientId() {
        return $this->clientId;
    }

    public function setClientId($clientId) {
        $this->clientId = $clientId;
    }

    public function getReqid() {
        return $this->reqid;
    }

    public function setReqid($reqid) {
        $this->reqid = $reqid;
    }

}
