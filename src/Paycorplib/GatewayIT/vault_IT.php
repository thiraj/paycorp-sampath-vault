<?php include '../GatewayClient/GatewayClient.php'; ?>
<?php include '../GatewayClientConfig/ClientConfig.php'; ?>
<?php include '../GatewayClientComponent/RequestHeader.php'; ?>
<?php include '../GatewayClientComponent/CreditCard.php'; ?>
<?php include '../GatewayClientFacade/BaseFacade.php'; ?>
<?php include '../GatewayClientRoot/PaycorpRequest.php'; ?>
<?php include '../GatewayClientVault/StoreCardRequest.php'; ?>
<?php include '../GatewayClientVault/StoreCardResponse.php'; ?>
<?php include '../GatewayClientVault/RetrieveCardRequest.php'; ?>
<?php include '../GatewayClientVault/RetrieveCardResponse.php'; ?>
<?php include '../GatewayClientVault/UpdateCardRequest.php'; ?>
<?php include '../GatewayClientVault/UpdateCardResponse.php'; ?>
<?php include '../GatewayClientVault/VerifyTokenRequest.php'; ?>
<?php include '../GatewayClientVault/VerifyTokenResponse.php'; ?>
<?php include '../GatewayClientVault/DeleteTokenRequest.php'; ?>
<?php include '../GatewayClientVault/DeleteTokenResponse.php'; ?>
<?php include '../GatewayClientUtils/IJsonHelper.php'; ?>
<?php include '../GatewayClientHelpers/StoreCardJsonHelper.php'; ?>
<?php include '../GatewayClientHelpers/DeleteTokenJsonHelper.php';  ?>
<?php include '../GatewayClientHelpers/RetrieveCardJsonHelper.php'; ?>
<?php include '../GatewayClientUtils/HmacUtils.php'; ?>
<?php include '../GatewayClientUtils/CommonUtils.php'; ?>
<?php include '../GatewayClientUtils/RestClient.php'; ?>
<?php include '../GatewayClientHelpers/UpdateCardJsonHelper.php'; ?>
<?php include '../GatewayClientHelpers/VerifyTokenJsonHelper.php'; ?>
<?php include '../GatewayClientEnums/Version.php'; ?>
<?php include '../GatewayClientEnums/Operation.php'; ?>
<?php include '../GatewayClientFacade/Payment.php'; ?>
<?php include '../GatewayClientFacade/Vault.php'; ?>
<?php include '../GatewayClientFacade/Report.php'; ?>

<?php
date_default_timezone_set('Asia/Colombo');
/*------------------------------------------------------------------------------
STEP1: Build ClientConfig object
------------------------------------------------------------------------------*/

$ClientConfig = new ClientConfig();
$ClientConfig->setServiceEndpoint("https://test-merchants.paycorp.com.au/rest/service/proxy");
$ClientConfig->setAuthToken("");
$ClientConfig->setHmacSecret("");
/*------------------------------------------------------------------------------
STEP2: Build Client object
------------------------------------------------------------------------------*/
$Client = new GatewayClient($ClientConfig);
/*------------------------------------------------------------------------------
STEP3: Build StoreCardRequest object
------------------------------------------------------------------------------*/
$storeCardRequest = new StoreCardRequest();

$storeCardRequest->setClientId(100007);
$storeCardRequest->setClientRef("api-");
// sets credit-card details
$creditCard = new CreditCard();
$creditCard->setType("VISA");
$creditCard->setHolderName("Scott Tiger");
$creditCard->setExpiry("1222");
$creditCard->setNumber("4564456445644564");
$creditCard->setSecureId("123");
$creditCard->setSecureIdSupplied(TRUE);
$storeCardRequest->setCreditCard($creditCard);

/*------------------------------------------------------------------------------
STEP4: Process StoreCardRequest object
------------------------------------------------------------------------------*/
$storeCardResponse = $Client->vault()->storeCard($storeCardRequest);
/*------------------------------------------------------------------------------
STEP5: Extract StoreCardResponse object
------------------------------------------------------------------------------*/
echo '<br><br>Vault Store-Card Response: --------------------------------------';
echo '<br>Token: ' . $storeCardResponse->getToken();
echo '<br>Response Code: ' . $storeCardResponse->getResponseCode();
echo '<br>Response Text: ' . $storeCardResponse->getResponseText();
echo '<br>----------------------------------------------------------------------';
/*------------------------------------------------------------------------------
STEP6: Build RetrieveCardRequest object
------------------------------------------------------------------------------*/
$retrieveCardRequest = new RetrieveCardRequest();
$retrieveCardRequest->setClientId(10000715);
$retrieveCardRequest->setToken("4564450210414564");
$retrieveCardRequest->setToken($storeCardResponse->getToken());
/*------------------------------------------------------------------------------
STEP7: Process RetrieveCardRequest object
------------------------------------------------------------------------------*/
$retrieveCardResponse = $Client->vault()->retrieveCard($retrieveCardRequest);
/*------------------------------------------------------------------------------
STEP8: Extract RetrieveCardResponse object
------------------------------------------------------------------------------*/
echo '<br><br>Vault Retrieve-Card Response: ------------------------------------';
echo '<br>Response Code: ' . $retrieveCardResponse->getResponseCode();
echo '<br>Response Text: ' . $retrieveCardResponse->getResponseText();
echo '<br>----------------------------------------------------------------------';
/*------------------------------------------------------------------------------
STEP9: Build UpdateCardRequest object
------------------------------------------------------------------------------*/
$updateCardRequest = new UpdateCardRequest();
$updateCardRequest->setClientId(10000715);
$updateCardRequest->setToken("4564450210414564");
$updateCardRequest->setExpiryDate("1222");
/*------------------------------------------------------------------------------
STEP10: Process UpdateCardRequest object
------------------------------------------------------------------------------*/
$updateCardResponse = $Client->vault()->updateCard($updateCardRequest);
/*------------------------------------------------------------------------------
STEP11: Extract UpdateCardResponse object
------------------------------------------------------------------------------*/
echo '<br><br>Vault Update-Card Response: --------------------------------------';
echo '<br>Response Code: ' . $updateCardResponse->getResponseCode();
echo '<br>Response Text: ' . $updateCardResponse->getResponseText();
echo '<br>----------------------------------------------------------------------';
/*------------------------------------------------------------------------------
STEP12: Build VerifyTokenRequest object
------------------------------------------------------------------------------*/
$verifyTokenRequest = new VerifyTokenRequest();
$verifyTokenRequest->setClientId(10000715);
$verifyTokenRequest->setToken("4564450210414564");
/*------------------------------------------------------------------------------
STEP13: Process VerifyTokenRequest object
------------------------------------------------------------------------------*/
$verifyTokenResponse = $Client->vault()->verifyToken($verifyTokenRequest);
/*------------------------------------------------------------------------------
STEP14: Extract VerifyTokenResponse object
------------------------------------------------------------------------------*/
echo '<br><br>Vault Verify-Token Response: -------------------------------------';
echo '<br>Token: ' . $verifyTokenResponse->getToken();
echo '<br>Client Ref: ' . $verifyTokenResponse->getClientRef();
echo '<br>Response Code: ' . $verifyTokenResponse->getResponseCode();
echo '<br>Response Text: ' . $verifyTokenResponse->getResponseText();
echo '<br>----------------------------------------------------------------------';
/*------------------------------------------------------------------------------
STEP15: Build DeleteTokenRequest object
------------------------------------------------------------------------------*/
$deleteTokenRequest = new DeleteTokenRequest();
$deleteTokenRequest->setClientId(10000715);
$deleteTokenRequest->setToken("4564450210414564");
/*------------------------------------------------------------------------------
STEP16: Process DeleteTokenRequest object
------------------------------------------------------------------------------*/
$deleteTokenResponse = $Client->vault()->deleteToken($deleteTokenRequest);
echo "SSS";
/*------------------------------------------------------------------------------
STEP17: Extract DeleteTokenRequest object
------------------------------------------------------------------------------*/
echo '<br><br>Vault Delete-Token Response: -------------------------------------';
echo '<br>Response Code: ' . $deleteTokenResponse->getResponseCode();
echo '<br>Response Text: ' . $deleteTokenResponse->getResponseText();
echo '<br>----------------------------------------------------------------------';
?>
