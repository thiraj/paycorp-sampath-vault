<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\DeleteTokenResponse;
use createch\PaycorpSampathVault\Support\Arr;

/**
 * Wire format for VAULT_DELETE_TOKEN.
 *
 * fromJson() previously read $responseData[responseData][responseCode] with
 * the array keys unquoted. PHP 7 resolved the bare words to strings with a
 * notice, so it happened to work; PHP 8 made undefined constants a fatal
 * Error, so deleteToken() has been broken on PHP 8 since that release.
 * The keys are now quoted and read defensively.
 */
class DeleteTokenJsonHelper implements IJsonHelper {

    public function fromJson($responseData) {
        $deleteTokenResponse = new DeleteTokenResponse();
        $deleteTokenResponse->setResponseCode(Arr::get($responseData, 'responseData.responseCode'));
        $deleteTokenResponse->setResponseText(Arr::get($responseData, 'responseData.responseText'));

        return $deleteTokenResponse;
    }

    public function toJson($paycorpRequest) {
        $requestData = $paycorpRequest->getRequestData();

        return array(
            "version" => (string) $paycorpRequest->getVersion(),
            "msgId" => (string) $paycorpRequest->getMsgId(),
            "operation" => (string) $paycorpRequest->getOperation(),
            "requestDate" => (string) $paycorpRequest->getRequestDate(),
            "validateOnly" => $paycorpRequest->getValidateOnly(),
            "requestData" => array(
                "clientId" => $requestData->getClientId(),
                "token" => (string) $requestData->getToken(),
            ),
        );
    }

}
