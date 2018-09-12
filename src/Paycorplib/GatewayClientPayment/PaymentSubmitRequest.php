<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment;

class PaymentSubmitRequest {
    private $clientId;
    
    private $transactionType;
    
    private $transactionAmount;
    
    private $creditCard;
    
    private $clientRef;
    
    private $comment;
    
    private $extraData;
    
    private $tokenize;
    
    private $tokenReference;
    
    public function  getClientId() {
        return $this->clientId;
    }

    public function  setClientId($clientId) {
        $this->clientId = $clientId;
    }

    public function getTransactionType() {
        return $this->transactionType;
    }

    public function  setTransactionType($transactionType) {
        $this->transactionType = $transactionType;
    }

    public function  getTransactionAmount() {
        return $this->transactionAmount;
    }

    public function  setTransactionAmount($transactionAmount) {
        $this->transactionAmount = $transactionAmount;
    }

    public function  getCreditCard() {
        return $this->creditCard;
    }

    public function  setCreditCard($creditCard) {
        $this->creditCard = $creditCard;
    }

    public function  getClientRef() {
        return $this->clientRef;
    }

    public function  setClientRef($clientRef) {
        $this->clientRef = $clientRef;
    }

    public function  getComment() {
        return $this->comment;
    }

    public function  setComment($comment) {
        $this->comment = $comment;
    }

    public function getExtraData() {
        return $this->extraData;
    }

    public function  setExtraData($extraData) {
        $this->extraData = $extraData;
    }

    public function  getTokenize() {
        return $this->tokenize;
    }

    public function  setTokenize($tokenize) {
        $this->tokenize = $tokenize;
    }

    public function  getTokenReference() {
        return $this->tokenReference;
    }

    public function  setTokenReference($tokenReference) {
        $this->tokenReference = $tokenReference;
    }
    
}
