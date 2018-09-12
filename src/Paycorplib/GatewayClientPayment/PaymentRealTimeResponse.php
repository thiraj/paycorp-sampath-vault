<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment;

class PaymentRealTimeResponse {

    private $txnReference;
    private $responseCode;
    private $responseText;
    private $settlementDate;
    private $authCode;
    
    public function __construct() {
    }

    public function getTxnReference() {
        return $this->txnReference;
    }

    public function setTxnReference($txnReference) {
        $this->txnReference = $txnReference;
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

    public function getSettlementDate() {
        return $this->settlementDate;
    }

    public function setSettlementDate($settlementDate) {
        $this->settlementDate = $settlementDate;
    }

    public function getAuthCode() {
        return $this->authCode;
    }

    public function setAuthCode($authCode) {
        $this->authCode = $authCode;
    }

}