<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientRoot;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums\Version;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\CommonUtils;

class PaycorpRequest {

    private $version;
    private $msgId;
    private $operation;
    private $requestDate;
    private $validateOnly;
    private $requestData;
    
    public function __construct() {
        $this->version = Version::$VERSION_LATEST;
        $this->msgId = CommonUtils::generateGUID();
        $this->validateOnly = FALSE;
    }
    
    public function getVersion() {
        return $this->version;
    }

    public function setVersion($version) {
        $this->version = $version;
    }
    
    public function getMsgId() {
        return $this->msgId;
    }

    public function setMsgId($msgId) {
        $this->msgId = $msgId;
    }

    public function getOperation() {
        return $this->operation;
    }

    public function setOperation($operation) {
        $this->operation = $operation;
    }

    public function getRequestDate() {
        return $this->requestDate;
    }

    public function setRequestDate($requestDate) {
        $this->requestDate = $requestDate;
    }

    public function getValidateOnly() {
        return $this->validateOnly;
    }

    public function setValidateOnly($validateOnly) {
        $this->validateOnly = $validateOnly;
    }

    public function getRequestData() {
        return $this->requestData;
    }

    public function setRequestData($requestData) {
        $this->requestData = $requestData;
    }

}
