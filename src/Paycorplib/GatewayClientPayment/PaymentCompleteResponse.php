<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment;

class PaymentCompleteResponse {

    private $clientId;
    private $clientIdHash;
    private $transactionType;
    private $creditCard;
    private $transactionAmount;
    private $clientRef;
    private $comment;
    private $txnReference;
    private $feeReference;
    private $responseCode;
    private $responseText;
    private $settlementDate;
    private $token;
    private $tokenized;
    private $tokenResponseText;
    private $authCode;
    private $cvcResponse;
    private $extraData;
    
    public function __construct() {
    }

    public function getClientId() {
        return $this->clientId;
    }

    public function setClientId($clientId) {
        $this->clientId = $clientId;
    }

    public function getClientIdHash() {
        return $this->clientIdHash;
    }

    public function setClientIdHash($clientIdHash) {
        $this->clientIdHash = $clientIdHash;
    }

    public function getTransactionType() {
        return $this->transactionType;
    }

    public function setTransactionType($transactionType) {
        $this->transactionType = $transactionType;
    }

    public function getCreditCard() {
        return $this->creditCard;
    }

    public function setCreditCard($creditCard) {
        $this->creditCard = $creditCard;
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

    public function getTxnReference() {
        return $this->txnReference;
    }

    public function setTxnReference($txnReference) {
        $this->txnReference = $txnReference;
    }

    public function getFeeReference() {
        return $this->feeReference;
    }

    public function setFeeReference($feeReference) {
        $this->feeReference = $feeReference;
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

    public function getToken() {
        return $this->token;
    }

    public function setToken($token) {
        $this->token = $token;
    }

    public function getTokenized() {
        return $this->tokenized;
    }

    public function setTokenized($tokenized) {
        $this->tokenized = $tokenized;
    }

    public function getTokenResponseText() {
        return $this->tokenResponseText;
    }

    public function setTokenResponseText($tokenResponseText) {
        $this->tokenResponseText = $tokenResponseText;
    }

    public function getAuthCode() {
        return $this->authCode;
    }

    public function setAuthCode($authCode) {
        $this->authCode = $authCode;
    }

    public function getCvcResponse() {
        return $this->cvcResponse;
    }

    public function setCvcResponse($cvcResponse) {
        $this->cvcResponse = $cvcResponse;
    }
    
    public function getExtraData() {
        return $this->extraData;
    }

    public function setExtraData($extraData) {
        $this->extraData = $extraData;
    }

}
