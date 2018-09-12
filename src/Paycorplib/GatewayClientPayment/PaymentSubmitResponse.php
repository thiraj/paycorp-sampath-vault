<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment;

class PaymentSubmitResponse {
    private $reqid;
    
    private $expireAt;
    
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

}
