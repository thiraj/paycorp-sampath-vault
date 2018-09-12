<?php include '../GatewayClient/GatewayClient.php'; ?>
<?php include '../GatewayClientConfig/ClientConfig.php'; ?>
<?php include '../GatewayClientComponent/RequestHeader.php'; ?>
<?php include '../GatewayClientComponent/CreditCard.php'; ?>
<?php include '../GatewayClientComponent/TransactionAmount.php'; ?>
<?php include '../GatewayClientComponent/Redirect.php'; ?>
<?php include '../GatewayClientFacade/BaseFacade.php'; ?>
<?php include '../GatewayClientFacade/Payment.php'; ?>
<?php include '../GatewayClientPayment/PaymentRealTimeRequest.php'; ?>
<?php include '../GatewayClientPayment/PaymentRealTimeResponse.php'; ?>
<?php include '../GatewayClientRoot/PaycorpRequest.php'; ?>
<?php include '../GatewayClientUtils/IJsonHelper.php'; ?>
<?php include '../GatewayClientHelpers/PaymentRealTimeJsonHelper.php'; ?>
<?php include '../GatewayClientUtils/HmacUtils.php'; ?>
<?php include '../GatewayClientUtils/CommonUtils.php'; ?>
<?php include '../GatewayClientUtils/RestClient.php'; ?>
<?php include '../GatewayClientEnums/TransactionType.php'; ?>
<?php include '../GatewayClientEnums/Version.php'; ?>
<?php include '../GatewayClientEnums/Operation.php'; ?>
<?php include '../GatewayClientFacade/Vault.php'; ?>
<?php include '../GatewayClientFacade/Report.php'; ?>

<?php

error_reporting(E_ALL);
date_default_timezone_set('Asia/Colombo');
/*------------------------------------------------------------------------------
STEP1: Build ClientConfig object
------------------------------------------------------------------------------*/
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
$realTimeRequest->setExtraData($exData);
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
?>
