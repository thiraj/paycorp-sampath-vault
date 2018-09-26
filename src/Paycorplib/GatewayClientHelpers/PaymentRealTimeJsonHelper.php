<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentRealTimeResponse;

class PaymentRealTimeJsonHelper implements IJsonHelper {

    public function __construct() {
        
    }

    public function fromJson($responseData) {
        $paymentRealTimeResponse = new PaymentRealTimeResponse();
        $paymentRealTimeResponse->setTxnReference(isset($responseData['responseData']['txnReference'])?$responseData['responseData']['txnReference']:"");
        $paymentRealTimeResponse->setResponseCode(isset($responseData['responseData']['responseCode'])?$responseData['responseData']['responseCode']:"");
        $paymentRealTimeResponse->setResponseText(isset($responseData['responseData']['responseText'])?$responseData['responseData']['responseText']:"");
        $paymentRealTimeResponse->setSettlementDate(isset($responseData['responseData']['settlementDate'])?$responseData['responseData']['settlementDate']:"");
        $paymentRealTimeResponse->setAuthCode(isset($responseData['responseData']['authCode'])?$responseData['responseData']['authCode']:"");

        return $paymentRealTimeResponse;
    }

    public function toJson($paycorpRequest) {
        $version = $paycorpRequest->getVersion();
        $msgId = $paycorpRequest->getMsgId();
        $operation = $paycorpRequest->getOperation();
        $requestDate = $paycorpRequest->getRequestDate();
        $validateOnly = $paycorpRequest->getValidateOnly();
        $requestData = $paycorpRequest->getRequestData();

        $clientId = $requestData->getClientId();
        $originalTxnReference = $requestData->getOriginalTxnReference();
        $transactiontype = $requestData->getTransactionType();

        $creditCard = $requestData->getCreditCard();
        $type = $creditCard->getType();
        $holderName = $creditCard->getHolderName();
        $number = $creditCard->getNumber();
        $expiry = $creditCard->getExpiry();
        $secureId = $creditCard->getSecureId();
        $secureIdSupplied = $creditCard->getSecureIdSupplied();

        $transactionAmount = $requestData->getTransactionAmount();
        $totalAmount = $transactionAmount->getTotalAmount();
        $paymentAmount = $transactionAmount->getPaymentAmount();
        $serviceFeeAmount = $transactionAmount->getServiceFeeAmount();
        $currency = $transactionAmount->getCurrency();

        $clientRef = $requestData->getClientRef();
        $comment = $requestData->getComment();
        $extraData = $requestData->getExtraData();

        return array(
            "version" => "$version",
            "msgId" => "$msgId",
            "operation" => "$operation",
            "requestDate" => "$requestDate",
            "validateOnly" => $validateOnly,
            "requestData" => array(
                "clientId" => $clientId,
                "originalTxnReference" => "$originalTxnReference",
                "creditCard" => array(
                    "type" => "$type",
                    "holderName" => "$holderName",
                    "number" => "$number",
                    "expiry" => "$expiry",
                    "secureId" => "$secureId",
                    "secureIdSupplied" => $secureIdSupplied
                ),
                "transactionType" => "$transactiontype",
                "transactionAmount" => array(
                    "totalAmount" => $totalAmount,
                    "paymentAmount" => $paymentAmount,
                    "serviceFeeAmount" => $serviceFeeAmount,
                    "currency" => "$currency"
                ),
                "clientRef" => "$clientRef",
                "comment" => "$comment",
                "extraData" => $extraData
                )
            );
    }

}
