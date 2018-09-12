<?php include '../GatewayClient/GatewayClient.php'; ?>
<?php include '../GatewayClientConfig/ClientConfig.php'; ?>
<?php include '../GatewayClientComponent/RequestHeader.php'; ?>
<?php include '../GatewayClientComponent/CreditCard.php'; ?>
<?php include '../GatewayClientComponent/TransactionAmount.php'; ?>
<?php include '../GatewayClientComponent/Redirect.php'; ?>
<?php include '../GatewayClientFacade/BaseFacade.php'; ?>
<?php include '../GatewayClientFacade/Payment.php'; ?>
<?php include '../GatewayClientPayment/PaymentInitRequest.php'; ?>
<?php include '../GatewayClientPayment/PaymentInitResponse.php'; ?>
<?php include '../GatewayClientRoot/PaycorpRequest.php'; ?>
<?php include '../GatewayClientUtils/IJsonHelper.php'; ?>
<?php include '../GatewayClientHelpers/PaymentInitJsonHelper.php'; ?>
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
$ClientConfig->setServiceEndpoint("https://test-sampath.paycorp.com.au/paycorp-webservice/InterfaceServlet");
$ClientConfig->setAuthToken("988a0dec");
$ClientConfig->setHmacSecret("Tes4");
$ClientConfig->setValidateOnly(FALSE);
/*------------------------------------------------------------------------------
STEP2: Build Client object
------------------------------------------------------------------------------*/
$Client = new GatewayClient($ClientConfig);
/*------------------------------------------------------------------------------
STEP3: Build PaymentInitRequest object
------------------------------------------------------------------------------*/
$initRequest = new PaymentInitRequest();
$initRequest->setClientId(14000031);
$initRequest->setTransactionType(TransactionType::$PURCHASE);
$initRequest->setClientRef("merchant_reference");
$initRequest->setComment("merchant_additional_data");
$initRequest->setTokenize(TRUE);
$initRequest->setExtraData(array("ADD-KEY-1" => "ADD-VALUE-1", "ADD-KEY-2" => "ADD-VALUE-2"));
// sets transaction-amounts details (all amounts are in cents)
$transactionAmount = new TransactionAmount(1010);
$transactionAmount->setTotalAmount(0);
$transactionAmount->setServiceFeeAmount(0);
//$transactionAmount->setPaymentAmount(100);
$transactionAmount->setCurrency("AUD");
$initRequest->setTransactionAmount($transactionAmount);
// sets redirect settings
$redirect = new Redirect("http://localhost/paycorp-client-php/au.com.gateway.IT/pcw_payment-complete_UT.php");
//$redirect->setReturnUrl();
$redirect->setReturnMethod("GET");
$initRequest->setRedirect($redirect);

/*------------------------------------------------------------------------------
STEP4: Process PaymentInitRequest object
------------------------------------------------------------------------------*/
$initResponse = $Client->payment()->init($initRequest);

/*------------------------------------------------------------------------------
STEP5: Extract PaymentInitResponse object
------------------------------------------------------------------------------*/
echo '<br><br>PCW Payment-Init Respopnse: --------------------------------------';
echo '<br>Req Id : ' . $initResponse->getReqid();
echo '<br>Payment Page Url : ' . $initResponse->getPaymentPageUrl();
echo '<br>Expire At : ' . $initResponse->getExpireAt();
echo '<br>------------------------------------------------------------------<br>';
?>

<html>
    <head></head>
    <body>
        <div style="float:left">
            <br><br><br><br>
            <iframe class="col-lg-12"  height="600px" width="600px" src="<?php echo $initResponse->getPaymentPageUrl(); ?>">
        </div>
    </body>
</html>
