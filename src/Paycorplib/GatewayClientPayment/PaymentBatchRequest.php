<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment;

class PaymentBatchRequest {
    
    private $comment;
    private $processAt;
    private $serialId;
    var $creditTransactions;
    
    public function  getComment() {
        return $this->comment;
    }

    public function  setComment($comment) {
        $this->comment = $comment;
    }

    public function  getProcessAt() {
        return $this->processAt;
    }


    public function  setProcessAt($processAt) {
        $this->processAt = $processAt;
    }

    public function  getSerialId() {
        return $this->serialId;
    }

    public function  setSerialId($serialId) {
        $this->serialId = $serialId;
    }

    public function  getCreditTransactions() {
        return $this->creditTransactions;
    }

    public function  setCreditTransactions($creditTransactions) {
        $this->creditTransactions = $creditTransactions;
    }
}
