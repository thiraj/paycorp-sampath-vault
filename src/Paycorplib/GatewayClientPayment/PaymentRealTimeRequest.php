<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment;

class PaymentRealTimeRequest {

    private $clientId;
    private $originalTxnReference;
    private $creditCard;
    private $transactionType;
    private $transactionAmount;
    private $clientRef;
    private $comment;
    private $extraData;
    
    public function __construct() {
    }

    public function getClientId() {
        return $this->clientId;
    }

    public function setClientId($clientId) {
        $this->clientId = $clientId;
    }

    public function getOriginalTxnReference() {
        return $this->originalTxnReference;
    }

    public function setOriginalTxnReference($originalTxnReference) {
        $this->originalTxnReference = $originalTxnReference;
    }

    public function getCreditCard() {
        return $this->creditCard;
    }

    public function setCreditCard($creditCard) {
        $this->creditCard = $creditCard;
    }

    public function getTransactionType() {
        return $this->transactionType;
    }

    public function setTransactionType($transactionType) {
        $this->transactionType = $transactionType;
    }

    public function getTransactionAmount() {
        return $this->transactionAmount;
    }

    public function setTransactionAmount($transactionAmount) {
        $this->transactionAmount = $transactionAmount;
    }

    public function getClientRef() {
        return $this->clientRef;
    }

    public function setClientRef($clientRef) {
        $this->clientRef = $clientRef;
    }

    public function getComment() {
        return $this->comment;
    }

    public function setComment($comment) {
        $this->comment = $comment;
    }

    public function getExtraData() {
        return $this->extraData;
    }

    public function setExtraData($extraData) {
        $this->extraData = $extraData;
    }

}
