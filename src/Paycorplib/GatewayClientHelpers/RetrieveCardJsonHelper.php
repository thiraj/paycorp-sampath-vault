<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\RetrieveCardResponse;
use createch\PaycorpSampathVault\Support\Arr;

/**
 * Wire format for VAULT_RETRIEVE_CARD.
 *
 * Only responseCode and responseText are mapped, matching the original
 * behaviour exactly. Any card detail the gateway returns is deliberately left
 * unmapped rather than guessed at: inventing key names here would either
 * silently drop data or, worse, surface a PAN the caller did not expect to
 * hold. Supply the Paycorp field list and it can be mapped properly.
 */
class RetrieveCardJsonHelper implements IJsonHelper {

    public function fromJson($responseData) {
        $retrieveCardResponse = new RetrieveCardResponse();
        $retrieveCardResponse->setResponseCode(Arr::get($responseData, 'responseData.responseCode'));
        $retrieveCardResponse->setResponseText(Arr::get($responseData, 'responseData.responseText'));

        return $retrieveCardResponse;
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
