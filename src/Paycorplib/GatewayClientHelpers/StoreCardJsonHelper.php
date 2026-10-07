<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\StoreCardResponse;
use createch\PaycorpSampathVault\Support\Arr;

/**
 * Wire format for VAULT_STORE_CARD.
 *
 * The outbound field order and string coercion are byte-identical to the
 * original, because the HMAC is computed over this exact JSON: reordering a
 * single key would change the signature and the gateway would reject it.
 */
class StoreCardJsonHelper implements IJsonHelper {

    public function fromJson($responseData) {
        $storeCardResponse = new StoreCardResponse();
        $storeCardResponse->setToken(Arr::get($responseData, 'responseData.token'));
        $storeCardResponse->setResponseCode(Arr::get($responseData, 'responseData.responseCode'));
        $storeCardResponse->setResponseText(Arr::get($responseData, 'responseData.responseText'));

        return $storeCardResponse;
    }

    public function toJson($paycorpRequest) {
        $requestData = $paycorpRequest->getRequestData();
        $creditCard = $requestData->getCreditCard();

        return array(
            "version" => (string) $paycorpRequest->getVersion(),
            "msgId" => (string) $paycorpRequest->getMsgId(),
            "operation" => (string) $paycorpRequest->getOperation(),
            "requestDate" => (string) $paycorpRequest->getRequestDate(),
            "validateOnly" => $paycorpRequest->getValidateOnly(),
            "requestData" => array(
                "clientId" => $requestData->getClientId(),
                "clientRef" => (string) $requestData->getClientRef(),
                "creditCard" => array(
                    "type" => (string) $creditCard->getType(),
                    "holderName" => (string) $creditCard->getHolderName(),
                    "number" => (string) $creditCard->getNumber(),
                    "expiry" => (string) $creditCard->getExpiry(),
                    "secureId" => (string) $creditCard->getSecureId(),
                    "secureIdSupplied" => $creditCard->getSecureIdSupplied(),
                ),
            ),
        );
    }

}
