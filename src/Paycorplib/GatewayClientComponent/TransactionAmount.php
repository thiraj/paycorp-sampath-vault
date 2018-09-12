<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent;

class TransactionAmount {

    private $totalAmount;
    private $paymentAmount;
    private $serviceFeeAmount;
    private $withholdingAmount;
    private $currency;
    
    public function __construct($paymentAmount) {
        $this->paymentAmount =$paymentAmount;
    }

    public function getTotalAmount() {
        return $this->totalAmount;
    }

    public function setTotalAmount($totalAmount) {
        $this->totalAmount = $totalAmount;
    }

    public function getPaymentAmount() {
        return $this->paymentAmount;
    }

    public function setPaymentAmount($paymentAmount) {
        $this->paymentAmount = $paymentAmount;
    }

    public function getServiceFeeAmount() {
        return $this->serviceFeeAmount;
    }

    public function setServiceFeeAmount($serviceFeeAmount) {
        $this->serviceFeeAmount = $serviceFeeAmount;
    }
    
    public function getWithholdingAmount() {
        return $this->withholdingAmount;
    }

    public function setWithholdingAmount($withholdingAmount) {
        $this->withholdingAmount = $withholdingAmount;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function setCurrency($currency) {
        $this->currency = $currency;
    }

}
