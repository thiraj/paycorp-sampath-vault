<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentRealTimeResponse;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Support\Arr;

/**
 * Wire format for PAYMENT_REAL_TIME.
 *
 * The empty-string defaults in fromJson() are kept exactly as they were, so
 * callers inspecting ResponseCode === '' still see what they always saw. What
 * changed is upstream: BaseFacade no longer reaches this method at all when
 * the exchange failed or the body was not a gateway envelope, so these
 * defaults can no longer disguise a network outage as a completed payment.
 */
class PaymentRealTimeJsonHelper implements IJsonHelper {

    public function fromJson($responseData) {
        $paymentRealTimeResponse = new PaymentRealTimeResponse();
        $paymentRealTimeResponse->setTxnReference(Arr::getString($responseData, 'responseData.txnReference', ""));
        $paymentRealTimeResponse->setResponseCode(Arr::getString($responseData, 'responseData.responseCode', ""));
        $paymentRealTimeResponse->setResponseText(Arr::getString($responseData, 'responseData.responseText', ""));
        $paymentRealTimeResponse->setSettlementDate(Arr::getString($responseData, 'responseData.settlementDate', ""));
        $paymentRealTimeResponse->setAuthCode(Arr::getString($responseData, 'responseData.authCode', ""));

        return $paymentRealTimeResponse;
    }

    public function toJson($paycorpRequest) {
        $requestData = $paycorpRequest->getRequestData();
        $creditCard = $requestData->getCreditCard();
        $transactionAmount = $requestData->getTransactionAmount();

        return array(
            "version" => (string) $paycorpRequest->getVersion(),
            "msgId" => (string) $paycorpRequest->getMsgId(),
            "operation" => (string) $paycorpRequest->getOperation(),
            "requestDate" => (string) $paycorpRequest->getRequestDate(),
            "validateOnly" => $paycorpRequest->getValidateOnly(),
            "requestData" => array(
                "clientId" => $requestData->getClientId(),
                "originalTxnReference" => (string) $requestData->getOriginalTxnReference(),
                "creditCard" => array(
                    "type" => (string) $creditCard->getType(),
                    "holderName" => (string) $creditCard->getHolderName(),
                    "number" => (string) $creditCard->getNumber(),
                    "expiry" => (string) $creditCard->getExpiry(),
                    "secureId" => (string) $creditCard->getSecureId(),
                    "secureIdSupplied" => $creditCard->getSecureIdSupplied(),
                ),
                "transactionType" => (string) $requestData->getTransactionType(),
                "transactionAmount" => array(
                    "totalAmount" => $transactionAmount->getTotalAmount(),
                    "paymentAmount" => $transactionAmount->getPaymentAmount(),
                    "serviceFeeAmount" => $transactionAmount->getServiceFeeAmount(),
                    "currency" => (string) $transactionAmount->getCurrency(),
                ),
                "clientRef" => (string) $requestData->getClientRef(),
                "comment" => (string) $requestData->getComment(),
                "extraData" => $requestData->getExtraData(),
            ),
        );
    }

}
