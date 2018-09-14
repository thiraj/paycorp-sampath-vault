<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentInitResponse;

class PaymentInitJsonHelper implements IJsonHelper {

    public function __construct() {
        
    }

    public function fromJson($responseData) {
        $paymentInitResponse = new PaymentInitResponse();
        $paymentInitResponse->setReqid($responseData['responseData']['reqid']);
        $paymentInitResponse->setExpireAt($responseData['responseData']['expireAt']);
        $paymentInitResponse->setPaymentPageUrl($responseData['responseData']['paymentPageUrl']);

        return $paymentInitResponse;
    }

    public function toJson($paycorpRequest) {
        $version = $paycorpRequest->getVersion();
        $msgId = $paycorpRequest->getMsgId();
        $operation = $paycorpRequest->getOperation();
        $requestDate = $paycorpRequest->getRequestDate();
        $validateOnly = $paycorpRequest->getValidateOnly();
        $requestData = $paycorpRequest->getRequestData();
        
        $clientId = $requestData->getClientId();
        $clientIdHash = $requestData->getClientIdHash();
        $transactionType = $requestData->getTransactionType();
        $clientRef = $requestData->getClientRef();
        $comment = $requestData->getComment();
        $tokenize = $requestData->getTokenize();
        $tokenReference = $requestData->getTokenReference();
        $cssLocation1 = $requestData->getCssLocation1();
        $cssLocation2 = $requestData->getCssLocation2();
        $useReliability = $requestData->isUseReliability();
        $extraData = $requestData->getExtraData();
        
        $transactionAmount = $requestData->getTransactionAmount();
        $totalAmount = $transactionAmount->getTotalAmount();
        $paymentAmount = $transactionAmount->getPaymentAmount();
        $serviceFeeAmount = $transactionAmount->getServiceFeeAmount();
        $currency = $transactionAmount->getCurrency();
        
        $redirect = $requestData->getRedirect();
        $returnUrl = $redirect->getReturnUrl();
        $cancelUrl = $redirect->getCancelUrl();
        $returnMethod = $redirect->getReturnMethod();

        return array(
            "version" => "$version",
            "msgId" => "$msgId",
            "operation" => "$operation",
            "requestDate" => "$requestDate",
            "validateOnly" => $validateOnly,
            "requestData" => array(
                "clientId" => $clientId,
                "clientIdHash" => "$clientIdHash",
                "transactionType" => "$transactionType",
                "transactionAmount" => array(
                    "totalAmount" => $totalAmount,
                    "paymentAmount" => $paymentAmount,
                    "serviceFeeAmount" => $serviceFeeAmount,
                    "currency" => "$currency"
                ),
                "redirect" => array(
                    "returnUrl" => "$returnUrl",
                    "cancelUrl" => "$cancelUrl",
                    "returnMethod" => "$returnMethod"
                ),
                "clientRef" => "$clientRef",
                "comment" => "$comment",
                "tokenize" => $tokenize,
                "tokenReference" => "$tokenReference",
                "cssLocation1" => "$cssLocation1",
                "cssLocation2" => "$cssLocation2",
                "useReliability" => $useReliability,
                "extraData" => $extraData
                )
            );
        

    }

}
