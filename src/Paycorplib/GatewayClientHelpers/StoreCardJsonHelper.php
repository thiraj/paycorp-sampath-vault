<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\StoreCardResponse;

class StoreCardJsonHelper implements IJsonHelper {

    public function __construct() {
        
    }

    public function fromJson($responseData) {
        $storeCardResponse = new StoreCardResponse();
        $storeCardResponse->setToken($responseData[responseData][token]);
        $storeCardResponse->setResponseCode($responseData[responseData][responseCode]);
        $storeCardResponse->setResponseText($responseData[responseData][responseText]);

        return $storeCardResponse;
    }

    public function toJson($paycorpRequest) {
        $version = $paycorpRequest->getVersion();
        $msgId = $paycorpRequest->getMsgId();
        $operation = $paycorpRequest->getOperation();
        $requestDate = $paycorpRequest->getRequestDate();
        $validateOnly = $paycorpRequest->getValidateOnly();
        $requestData = $paycorpRequest->getRequestData();

        $clientId = $requestData->getClientId();
        $clientRef = $requestData->getClientRef();

        $creditCard = $requestData->getCreditCard();
        $type = $creditCard->getType();
        $holderName = $creditCard->getHolderName();
        $number = $creditCard->getNumber();
        $expiry = $creditCard->getExpiry();
        $secureId = $creditCard->getSecureId();
        $secureIdSupplied = $creditCard->getSecureIdSupplied();

        return array(
            "version" => "$version",
            "msgId" => "$msgId",
            "operation" => "$operation",
            "requestDate" => "$requestDate",
            "validateOnly" => $validateOnly,
            "requestData" => array(
                "clientId" => $clientId,
                "clientRef" => "$clientRef",
                "creditCard" => array(
                    "type" => "$type",
                    "holderName" => "$holderName",
                    "number" => "$number",
                    "expiry" => "$expiry",
                    "secureId" => "$secureId",
                    "secureIdSupplied" => $secureIdSupplied
                )
            )
        );
    }

}
