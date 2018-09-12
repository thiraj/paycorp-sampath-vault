<?php include '../GatewayClient/GatewayClient.php'; ?>
<?php include '../GatewayClientConfig/ClientConfig.php'; ?>
<?php include '../GatewayClientComponent/RequestHeader.php'; ?>
<?php include '../GatewayClientComponent/CreditCard.php'; ?>
<?php include '../GatewayClientComponent/TransactionAmount.php'; ?>
<?php include '../GatewayClientComponent/Redirect.php'; ?>
<?php include '../GatewayClientFacade/BaseFacade.php'; ?>
<?php include '../GatewayClientFacade/Payment.php'; ?>
<?php include '../GatewayClientRoot/PaycorpRequest.php'; ?>
<?php include '../GatewayClientRoot/PaycorpResponse.php'; ?>
<?php include '../GatewayClientPayment/PaymentCompleteRequest.php'; ?>
<?php include '../GatewayClientPayment/PaymentCompleteResponse.php'; ?>
<?php include '../GatewayClientUtils/IJsonHelper.php'; ?>
<?php include '../GatewayClientHelpers/PaymentCompleteJsonHelper.php'; ?>
<?php include '../GatewayClientUtils/HmacUtils.php'; ?>
<?php include '../GatewayClientUtils/CommonUtils.php'; ?>
<?php include '../GatewayClientUtils/RestClient.php'; ?>
<?php include '../GatewayClientEnums/TransactionType.php'; ?>
<?php include '../GatewayClientEnums/Version.php'; ?>
<?php include '../GatewayClientEnums/Operation.php'; ?>
<?php include '../GatewayClientFacade/Vault.php'; ?>
<?php include '../GatewayClientFacade/Report.php'; ?>

<?php
date_default_timezone_set('Asia/Colombo');
/*------------------------------------------------------------------------------
STEP1: Build ClientConfig object
------------------------------------------------------------------------------*/
$ClientConfig = new ClientConfig();
$ClientConfig->setServiceEndpoint("https://sampath.paycorp.com.au/rest/service/proxy/");
$ClientConfig->setAuthToken("ad0862e6-13a2-4c68-a518-6dc885883adf");
$ClientConfig->setHmacSecret("YkBb7uqHROKCUOB6");
$ClientConfig->setValidateOnly(FALSE);
/*------------------------------------------------------------------------------
STEP2: Build Client object
------------------------------------------------------------------------------*/

$Client = new GatewayClient($ClientConfig);
/*------------------------------------------------------------------------------
STEP3: Build PaymentCompleteRequest object
------------------------------------------------------------------------------*/
$completeRequest = new PaymentCompleteRequest();
$completeRequest->setClientId(14000396);
$completeRequest->setReqid($_GET['reqid']);
/*------------------------------------------------------------------------------
STEP4: Process PaymentCompleteRequest object
------------------------------------------------------------------------------*/
$completeResponse = $Client->payment()->complete($completeRequest);
/*------------------------------------------------------------------------------
STEP5: Process PaymentCompleteResponse object
------------------------------------------------------------------------------*/
echo '<br><br>PCW Payment-Complete Respopnse: ----------------------------------';
echo '<br>Txn Reference : ' . $completeResponse->getTxnReference();
echo '<br>Response Code : ' . $completeResponse->getResponseCode();
echo '<br>Response Text : ' . $completeResponse->getResponseText();
echo '<br>Settlement Date : ' . $completeResponse->getSettlementDate();
echo '<br>Auth Code : ' . $completeResponse->getAuthCode();
echo '<br>Token : ' . $completeResponse->getToken();
echo '<br>Token Response Text: ' . $completeResponse->getTokenResponseText();
echo '<br>----------------------------------------------------------------------';
?>
