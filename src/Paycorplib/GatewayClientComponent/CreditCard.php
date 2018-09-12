<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent;

class CreditCard {

    private $type;
    private $holderName;
    private $number;
    private $expiry;
    private $secureId;
    private $secureIdSupplied;
    private $track1;
    private $track2;
    private $track3;
    private $cardChipData;

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getHolderName() {
        return $this->holderName;
    }

    public function setHolderName($holderName) {
        $this->holderName = $holderName;
    }

    public function getNumber() {
        return $this->number;
    }

    public function setNumber($number) {
        $this->number = $number;
    }

    public function getExpiry() {
        return $this->expiry;
    }

    public function setExpiry($expiry) {
        $this->expiry = $expiry;
    }

    public function getSecureId() {
        return $this->secureId;
    }

    public function setSecureId($secureId) {
        $this->secureId = $secureId;
    }

    public function getSecureIdSupplied() {
        return $this->secureIdSupplied;
    }

    public function setSecureIdSupplied($secureIdSupplied) {
        $this->secureIdSupplied = $secureIdSupplied;
    }

    public function getTrack1() {
        return $this->track1;
    }

    public function setTrack1($track1) {
        $this->track1 = $track1;
    }

    public function getTrack2() {
        return $this->track2;
    }

    public function setTrack2($track2) {
        $this->track2 = $track2;
    }

    public function getTrack3() {
        return $this->track3;
    }

    public function setTrack3($track3) {
        $this->track3 = $track3;
    }

    public function getCardChipData() {
        return $this->cardChipData;
    }

    public function setCardChipData($cardChipData) {
        $this->cardChipData = $cardChipData;
    }

}
