<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent;

class ThreeDSecure {
    
    private $xid;
    private $eci;
    
    // ucaf for mastercard
    private $cavv;
    private $vERes;
    private $pARes;
    
     public function  getXid() {
        return $this->xid;
    }

    public function  setXid($xid) {
        $this->xid = $xid;
    }

    public function  getEci() {
        return $this->eci;
    }

    public function  setEci($eci) {
        $this->eci = $eci;
    }

    public function  getCavv() {
        return $this->cavv;
    }

    public function  setCavv($cavv) {
        $this->cavv = $cavv;
    }

    public function  getvERes() {
        return $this->vERes;
    }

    public function  setvERes($vERes) {
        $this->vERes = $vERes;
    }

    public function  getpARes() {
        return $this->pARes;
    }

    public function  setpARes($pARes) {
        $this->pARes = $pARes;
    }
}
