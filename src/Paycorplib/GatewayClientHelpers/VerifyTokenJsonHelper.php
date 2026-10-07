<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\VerifyTokenResponse;
use createch\PaycorpSampathVault\Support\Arr;

/**
 * Wire format for VAULT_VERIFY_TOKEN.
 *
 * Had the same unquoted-array-key defect as DeleteTokenJsonHelper, so
 * verifyToken() was fatal on PHP 8 before this fix.
 */
class VerifyTokenJsonHelper implements IJsonHelper {

    public function fromJson($responseData) {
        $verifyTokenResponse = new VerifyTokenResponse();
        $verifyTokenResponse->setResponseCode(Arr::get($responseData, 'responseData.responseCode'));
        $verifyTokenResponse->setResponseText(Arr::get($responseData, 'responseData.responseText'));

        return $verifyTokenResponse;
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
