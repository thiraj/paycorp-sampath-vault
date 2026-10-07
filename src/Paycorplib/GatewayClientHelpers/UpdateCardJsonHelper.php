<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\UpdateCardResponse;
use createch\PaycorpSampathVault\Support\Arr;

/**
 * Wire format for VAULT_UPDATE_CARD.
 *
 * Had the same unquoted-array-key defect as DeleteTokenJsonHelper, so
 * updateCard() was fatal on PHP 8 before this fix.
 */
class UpdateCardJsonHelper implements IJsonHelper {

    public function fromJson($responseData) {
        $updateCardResponse = new UpdateCardResponse();
        $updateCardResponse->setResponseCode(Arr::get($responseData, 'responseData.responseCode'));
        $updateCardResponse->setResponseText(Arr::get($responseData, 'responseData.responseText'));

        return $updateCardResponse;
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
                "expiryDate" => (string) $requestData->getExpiryDate(),
            ),
        );
    }

}
