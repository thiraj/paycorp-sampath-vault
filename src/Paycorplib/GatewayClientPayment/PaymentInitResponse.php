<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment;

class PaymentInitResponse {

    private $reqid;
    private $expireAt;
    private $paymentPageUrl;
    
    public function __construct() {
    }

    public function getReqid() {
        return $this->reqid;
    }

    public function setReqid($reqid) {
        $this->reqid = $reqid;
    }

    public function getExpireAt() {
        return $this->expireAt;
    }

    public function setExpireAt($expireAt) {
        $this->expireAt = $expireAt;
    }

    public function getPaymentPageUrl() {
        return $this->paymentPageUrl;
    }

    public function setPaymentPageUrl($paymentPageUrl) {
        $this->paymentPageUrl = $paymentPageUrl;
    }

}
