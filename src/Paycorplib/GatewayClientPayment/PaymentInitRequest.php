<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment;

class PaymentInitRequest {

    private $clientId;
    private $clientIdHash;
    private $transactionType;
    private $transactionAmount;
    private $redirect;
    private $clientRef;
    private $comment;
    private $tokenize;
    private $tokenReference;
    private $cssLocation1;
    private $cssLocation2;
    private $useReliability;
    private $extraData;

    public function __construct() {
        $this->useReliability = TRUE;
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

    public function getTransactionAmount() {
        return $this->transactionAmount;
    }

    public function setTransactionAmount($transactionAmount) {
        $this->transactionAmount = $transactionAmount;
    }

    public function getRedirect() {
        return $this->redirect;
    }

    public function setRedirect($redirect) {
        $this->redirect = $redirect;
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

    public function getTokenize() {
        return $this->tokenize;
    }

    public function setTokenize($tokenize) {
        $this->tokenize = $tokenize;
    }

    public function getTokenReference() {
        return $this->tokenReference;
    }

    public function setTokenReference($tokenReference) {
        $this->tokenReference = $tokenReference;
    }

    public function getCssLocation1() {
        return $this->cssLocation1;
    }

    public function setCssLocation1($cssLocation1) {
        $this->cssLocation1 = $cssLocation1;
    }

    public function getCssLocation2() {
        return $this->cssLocation2;
    }

    public function setCssLocation2($cssLocation2) {
        $this->cssLocation2 = $cssLocation2;
    }

    public function isUseReliability() {
        return $this->useReliability;
    }

    public function setUseReliability($useReliability) {
        $this->useReliability = $useReliability;
    }

    public function getExtraData() {
        return $this->extraData;
    }

    public function setExtraData($extraData) {
        $this->extraData = $extraData;
    }

}
