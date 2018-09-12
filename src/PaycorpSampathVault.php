<?php

namespace createch\PaycorpSampathVault;

use createch\PaycorpSampathVault\Paycorplib\GatewayClient\GatewayClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentRealTimeRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\RequestHeader;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\CreditCard;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\TransactionAmount;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientFacade\BaseFacade;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientFacade\Payment;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientFacade\Vault;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientFacade\Report;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientRoot\PaycorpRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\StoreCardRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\StoreCardResponse;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\RetrieveCardRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\RetrieveCardResponse;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\UpdateCardRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\UpdateCardResponse;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\VerifyTokenRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\VerifyTokenResponse;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\DeleteTokenRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\DeleteTokenResponse;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\HmacUtils;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\CommonUtils;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\RestClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\StoreCardJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\DeleteTokenJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\RetrieveCardJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\UpdateCardJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\VerifyTokenJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums\Version;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums\Operation;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums\TransactionType;


class PaycorpSampathVault
{

    public function IPGLoaded(){
        return "Hi";
    }

    public function realTimePayment(){

//        STEP1: Build ClientConfig object
//        ------------------------------------------------------------------------------*/
        $ClientConfig = new ClientConfig();
        $ClientConfig->setServiceEndpoint("https://test-merchants.paycorp.com.au/rest/service/proxy");
        $ClientConfig->setAuthToken("ad7ba606-1");
        $ClientConfig->setHmacSecret("W1TTEgkkkk");

/*------------------------------------------------------------------------------
STEP2: Build Client object
------------------------------------------------------------------------------*/
        $Client = new GatewayClient($ClientConfig);
/*------------------------------------------------------------------------------
STEP3: Build PaymentRealTimeRequest object
------------------------------------------------------------------------------*/
        $realTimeRequest = new PaymentRealTimeRequest();

        $realTimeRequest->setClientId(10004708);
        $realTimeRequest->setTransactionType(TransactionType::$PURCHASE);
        $realTimeRequest->setClientRef("merchant_reference");
        $realTimeRequest->setComment("merchant_additional_data");

        $extraData = array("invoice-no" => "I99999", "job-no" => "J10101");
        $realTimeRequest->setExtraData($extraData);
        // sets credit-card details
        $creditCard = new CreditCard();
        $creditCard->setType("VISA");
        $creditCard->setHolderName("Bob Marley");
        $creditCard->setExpiry("1225");
        $creditCard->setNumber("4564456445644564");
        $creditCard->setSecureId("123");
        $creditCard->setSecureIdSupplied(TRUE);
        $realTimeRequest->setCreditCard($creditCard);
        // sets transaction-amounts details (all amounts are in cents)
        $transactionAmount = new TransactionAmount(1010);
        $transactionAmount->setTotalAmount(0);
        //$transactionAmount->setPaymentAmount(200);
        //$transactionAmount->setServiceFeeAmount(0);
        $transactionAmount->setCurrency("AUD");
        $realTimeRequest->setTransactionAmount($transactionAmount);
/*------------------------------------------------------------------------------
STEP4: Process RealTimeRequest object
------------------------------------------------------------------------------*/
        $realTimeResponse = $Client->payment()->realTime($realTimeRequest);

/*------------------------------------------------------------------------------
STEP5: Extract RealTimeResponse object
------------------------------------------------------------------------------*/
echo '<br><br>Payment Real-Time Response: --------------------------------------';
echo '<br>TxnReference : ' . $realTimeResponse->getTxnReference();
echo '<br>ResponseCode : ' . $realTimeResponse->getResponseCode();
echo '<br>ResponseText : ' . $realTimeResponse->getResponseText();
echo '<br>SettlementDate : ' . $realTimeResponse->getSettlementDate();
echo '<br>AuthCode : ' . $realTimeResponse->getAuthCode();
echo '<br>----------------------------------------------------------------------';

    }

}