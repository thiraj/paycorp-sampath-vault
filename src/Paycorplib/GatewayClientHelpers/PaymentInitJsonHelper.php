<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentInitResponse;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Support\Arr;

/**
 * Wire format for PAYMENT_INIT.
 *
 * The outbound key order and string coercion match the original byte for
 * byte, because the HMAC is computed over this JSON.
 */
class PaymentInitJsonHelper implements IJsonHelper {

    public function fromJson($responseData) {
        $paymentInitResponse = new PaymentInitResponse();
        $paymentInitResponse->setReqid(Arr::get($responseData, 'responseData.reqid'));
        $paymentInitResponse->setExpireAt(Arr::get($responseData, 'responseData.expireAt'));
        $paymentInitResponse->setPaymentPageUrl(Arr::get($responseData, 'responseData.paymentPageUrl'));

        return $paymentInitResponse;
    }

    public function toJson($paycorpRequest) {
        $requestData = $paycorpRequest->getRequestData();
        $transactionAmount = $requestData->getTransactionAmount();
        $redirect = $requestData->getRedirect();

        return array(
            "version" => (string) $paycorpRequest->getVersion(),
            "msgId" => (string) $paycorpRequest->getMsgId(),
            "operation" => (string) $paycorpRequest->getOperation(),
            "requestDate" => (string) $paycorpRequest->getRequestDate(),
            "validateOnly" => $paycorpRequest->getValidateOnly(),
            "requestData" => array(
                "clientId" => $requestData->getClientId(),
                "clientIdHash" => (string) $requestData->getClientIdHash(),
                "transactionType" => (string) $requestData->getTransactionType(),
                "transactionAmount" => array(
                    "totalAmount" => $transactionAmount->getTotalAmount(),
                    "paymentAmount" => $transactionAmount->getPaymentAmount(),
                    "serviceFeeAmount" => $transactionAmount->getServiceFeeAmount(),
                    "currency" => (string) $transactionAmount->getCurrency(),
                ),
                "redirect" => array(
                    "returnUrl" => (string) $redirect->getReturnUrl(),
                    "cancelUrl" => (string) $redirect->getCancelUrl(),
                    "returnMethod" => (string) $redirect->getReturnMethod(),
                ),
                "clientRef" => (string) $requestData->getClientRef(),
                "comment" => (string) $requestData->getComment(),
                "tokenize" => $requestData->getTokenize(),
                "tokenReference" => (string) $requestData->getTokenReference(),
                "cssLocation1" => (string) $requestData->getCssLocation1(),
                "cssLocation2" => (string) $requestData->getCssLocation2(),
                "useReliability" => $requestData->isUseReliability(),
                "extraData" => $requestData->getExtraData(),
            ),
        );
    }

}
