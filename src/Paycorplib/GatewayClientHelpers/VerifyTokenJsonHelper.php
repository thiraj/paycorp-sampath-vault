<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\VerifyTokenResponse;

class VerifyTokenJsonHelper implements IJsonHelper {
    
    public function __construct() {
        
    }

    public function fromJson($responseData) {
        $verifyTokenResponse = new VerifyTokenResponse();
        $verifyTokenResponse->setResponseCode($responseData[responseData][responseCode]);
        $verifyTokenResponse->setResponseText($responseData[responseData][responseText]);

        return $verifyTokenResponse;
    }

    public function toJson($paycorpRequest) {
        $version = $paycorpRequest->getVersion();
        $msgId = $paycorpRequest->getMsgId();
        $operation = $paycorpRequest->getOperation();
        $requestDate = $paycorpRequest->getRequestDate();
        $validateOnly = $paycorpRequest->getValidateOnly();
        $requestData = $paycorpRequest->getRequestData();
        
        $clientId = $requestData->getClientId();
        $token = $requestData->getToken();

        return array(
            "version" => "$version",
            "msgId" => "$msgId",
            "operation" => "$operation",
            "requestDate" => "$requestDate",
            "validateOnly" => $validateOnly,
            "requestData" => array(
                "clientId" => $clientId,
                "token" => "$token"
                )
            );
    }

}
