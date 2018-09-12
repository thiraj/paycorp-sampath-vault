<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientRoot;

class PaycorpResponse {

    private $msgId;
    private $error;
    private $responseData;

    public function __construct() {
        
    }

    public function getMsgId() {
        return $this->msgId;
    }

    public function setMsgId($msgId) {
        $this->msgId = $msgId;
    }

    public function getError() {
        return $this->error;
    }

    public function setError($error) {
        $this->error = $error;
    }

    public function getResponseData() {
        return $this->responseData;
    }

    public function setResponseData($responseData) {
        $this->responseData = $responseData;
    }

}
