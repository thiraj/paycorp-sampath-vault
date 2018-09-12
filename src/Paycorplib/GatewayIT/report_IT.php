<?php include '../GatewayClient/GatewayClient.php';  ?>
<?php include '../GatewayClientConfig/ClientConfig.php'; ?>
<?php include '../GatewayClientComponent/RequestHeader.php'; ?>
<?php include '../GatewayClientFacade/BaseFacade.php'; ?>
<?php include '../GatewayClientComponent/CreditCard.php'; ?>
<?php include '../GatewayClientComponent/TransactionAmount.php'; ?>
<?php include '../GatewayClientComponent/Redirect.php'; ?>
<?php include '../GatewayClientRoot/PaycorpRequest.php'; ?>
<?php include '../GatewayClientRoot/PaycorpResponse.php'; ?>
<?php include '../au.com.gateway.client.report/BasicReportRequest.php'; ?>
<?php include '../au.com.gateway.client.report/BasicReportResponse.php'; ?>
<?php include '../GatewayClientUtils/IJsonHelper.php'; ?>
<?php include '../GatewayClientHelpers/BasicReportJsonHelper.php'; ?>
<?php include '../GatewayClientUtils/HmacUtils.php'; ?>
<?php include '../GatewayClientUtils/CommonUtils.php'; ?>
<?php include '../GatewayClientUtils/RestClient.php'; ?>
<?php include '../GatewayClientEnums/DateType.php'; ?>
<?php include '../GatewayClientEnums/TransactionType.php'; ?>
<?php include '../GatewayClientEnums/Version.php'; ?>
<?php include '../GatewayClientEnums/Operation.php'; ?>
<?php include '../GatewayClientFacade/Payment.php'; ?>
<?php include '../GatewayClientFacade/Report.php'; ?>
<?php include '../GatewayClientFacade/Vault.php'; ?>

<?php
date_default_timezone_set('Asia/Colombo');

/*------------------------------------------------------------------------------
STEP1: Build ClientConfig object
------------------------------------------------------------------------------*/
$ClientConfig = new ClientConfig();
$ClientConfig->setServiceEndpoint("https://test-merchants.paycorp.com.au/rest/service/proxy");
$ClientConfig->setAuthToken("e186398c-4");
$ClientConfig->setHmacSecret("jjiuyj");
$ClientConfig->setValidateOnly(FALSE);
/*------------------------------------------------------------------------------
STEP2: Build Client object
------------------------------------------------------------------------------*/

$Client = new GatewayClient($ClientConfig);

/*------------------------------------------------------------------------------
STEP3: BasicReport Object
------------------------------------------------------------------------------*/

$basicReportRequest = new BasicReportRequest();
$basicReportRequest->setMinAmount(0);
$basicReportRequest->setMaxAmount(0);
$basicReportRequest->setCustomerId(0);

/*------------------------------------------------------------------------------
STEP4: test_txnReference
------------------------------------------------------------------------------*/

//$basicReportRequest->setTxnReference("2015300000000068");

/*------------------------------------------------------------------------------
STEP4: test_date_range_event
------------------------------------------------------------------------------*/
$basicReportRequest->setDateType(DateType::$EVENT);
$basicReportRequest->setFromDate("2014-09-06 05:55:14");
$basicReportRequest->setToDate("2015-07-05 05:55:14");
$basicReportRequest->setResultsetSize(10);

/*------------------------------------------------------------------------------
STEP4: test_date_range_event_approved()
------------------------------------------------------------------------------*/
//$basicReportRequest->setDateType(DateType::$EVENT);
//$basicReportRequest->setFromDate("2014-09-06 05:55:14");
//$basicReportRequest->setToDate("2015-07-05 05:55:14");
//$basicReportRequest->setResultsetSize(10);
//$arr = array("-1");
//$basicReportRequest->setResponseCodes($arr);

/*------------------------------------------------------------------------------
STEP4: test_batch_ref() 
------------------------------------------------------------------------------*/

//$basicReportRequest->setBatchReference("948106");

/*------------------------------------------------------------------------------
STEP4: test_card_number()
------------------------------------------------------------------------------*/
//$basicReportRequest->setCreditCardNumber("456445xxxxxx4564");

/*------------------------------------------------------------------------------
STEP4: test_card_expiry()
------------------------------------------------------------------------------*/
//$basicReportRequest->setCreditCardExpiry("1019");

/*------------------------------------------------------------------------------
STEP4: test_card_type()
------------------------------------------------------------------------------*/
//$arr = array("VISA");
//$basicReportRequest->setCreditCardTypes($arr);

/*------------------------------------------------------------------------------
STEP4: test_txn_type
------------------------------------------------------------------------------*/
//$arr = array(TransactionType::$REFUND);
//$basicReportRequest->setTransactionTypes($arr);

/*------------------------------------------------------------------------------
STEP4: test_client_ref
------------------------------------------------------------------------------*/
//$basicReportRequest->setClientRef("02820619140027");

/*------------------------------------------------------------------------------
STEP4: test_amount_range
------------------------------------------------------------------------------*/

//$basicReportRequest->setMinAmount(9100);
//basicReportRequest->setMaxAmount(9100);

/*------------------------------------------------------------------------------
STEP 5: Process ReportBasicResponse object
------------------------------------------------------------------------------*/
$basicReportResponse =$Client->report()->basic($basicReportRequest);
