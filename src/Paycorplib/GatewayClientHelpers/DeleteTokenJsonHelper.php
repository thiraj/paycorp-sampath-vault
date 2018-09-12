<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\DeleteTokenResponse;

class DeleteTokenJsonHelper implements IJsonHelper {
    
    public function __construct() {
        
    }

    public function fromJson($responseData) {
        $deleteTokenResponse = new DeleteTokenResponse();
        $deleteTokenResponse->setResponseCode($responseData[responseData][responseCode]);
        $deleteTokenResponse->setResponseText($responseData[responseData][responseText]);

        return $deleteTokenResponse;
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
