<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\UpdateCardResponse;

class UpdateCardJsonHelper implements IJsonHelper {

    public function __construct() {
        
    }

    public function fromJson($responseData) {
        $updateCardResponse = new UpdateCardResponse();
        $updateCardResponse->setResponseCode($responseData[responseData][responseCode]);
        $updateCardResponse->setResponseText($responseData[responseData][responseText]);

        return $updateCardResponse;
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
        $expiryDate = $requestData->getExpiryDate();

        return array(
            "version" => "$version",
            "msgId" => "$msgId",
            "operation" => "$operation",
            "requestDate" => "$requestDate",
            "validateOnly" => $validateOnly,
            "requestData" => array(
                "clientId" => $clientId,
                "token" => "$token",
                "expiryDate" => "$expiryDate"
            )
        );
    }

}
