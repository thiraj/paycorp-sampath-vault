<?php include '../GatewayClient/GatewayClient.php';  ?>
<?php include '../GatewayClientConfig/ClientConfig.php'; ?>
<?php include '../GatewayClientComponent/RequestHeader.php'; ?>
<?php include '../GatewayClientFacade/BaseFacade.php'; ?>
<?php include '../GatewayClientComponent/CreditCard.php'; ?>
<?php include '../GatewayClientComponent/TransactionAmount.php'; ?>
<?php include '../GatewayClientComponent/Redirect.php'; ?>
<?php include '../GatewayClientRoot/PaycorpRequest.php'; ?>
<?php include '../GatewayClientRoot/PaycorpResponse.php'; ?>
<?php include '../au.com.gateway.client.report/SettlementReportRequest.php'; ?>
<?php include '../au.com.gateway.client.report/SettlementReportResponse.php'; ?>
<?php include '../GatewayClientUtils/IJsonHelper.php'; ?>
<?php include '../GatewayClientHelpers/SettlementReportJsonHelper.php'; ?>
<?php include '../GatewayClientUtils/HmacUtils.php'; ?>
<?php include '../GatewayClientUtils/CommonUtils.php'; ?>
<?php include '../GatewayClientUtils/RestClient.php'; ?>
<?php include '../GatewayClientEnums/DateType.php'; ?>
<?php include '../GatewayClientEnums/SettlementGroupBy.php'; ?>
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
$ClientConfig->setHmacSecret("3ewVC");
$ClientConfig->setValidateOnly(FALSE);
/*------------------------------------------------------------------------------
STEP2: Build Client object
------------------------------------------------------------------------------*/
$Client = new GatewayClient($ClientConfig);
/*------------------------------------------------------------------------------
STEP3: test_settlementByCustomer
------------------------------------------------------------------------------*/

$settlementRequest = new SettlementReportRequest();
$settlementRequest->setCustomerId(64000001);
$settlementRequest->setClientIds(array(14000001, 14000033));
$settlementRequest->setGroupBy(SettlementGroupBy::$CUSTOMER);
$settlementRequest->setFromDate("2014-01-01 00:00:00");
$settlementRequest->setToDate("2015-07-01 00:00:00");

/*------------------------------------------------------------------------------
STEP3: test_settlementByClient
------------------------------------------------------------------------------*/

//$settlementRequest->setCustomerId(64000001);
//$settlementRequest->setClientIds(array(14000001, 14000033));
//$settlementRequest->setGroupBy(SettlementGroupBy::$CLIENT);
//$settlementRequest->setFromDate("2014-01-01 00:00:00");
//$settlementRequest->setToDate("2015-07-01 00:00:00");

/*------------------------------------------------------------------------------
STEP3: test_settlementByMerchant
------------------------------------------------------------------------------*/

//$settlementRequest->setCustomerId(64000001);
//$settlementRequest->setClientIds(array(14000001, 14000033));
//$settlementRequest->setGroupBy(SettlementGroupBy::$MERCHANT);
//$settlementRequest->setFromDate("2014-01-01 00:00:00");
//$settlementRequest->setToDate("2015-07-01 00:00:00");

/*------------------------------------------------------------------------------
STEP 5: Process ReportSettlementResponse object
------------------------------------------------------------------------------*/

$settlementReportResponse = $Client->report()->settlement($settlementRequest);

$data = '{
    "settlementRecords":[
        {
            "customerId":"64000001",
            "creditCardType":"ERROR: NOT ENOUGH DIGITS",
            "settledAmount":12600,
            "approvedAmount":100,
            "declinedAmount":12500,
            "refundedAmount":0,
            "settledCount":4,
            "approvedCount":1,
            "declinedCount":3,
            "refundedCount":0
        },
        {
            "customerId":"64000001",
            "creditCardType":"MCV",
            "settledAmount":143338600,
            "approvedAmount":25226500,
            "declinedAmount":118112100,
            "refundedAmount":3933600,
            "settledCount":649,
            "approvedCount":409,
            "declinedCount":240,
            "refundedCount":99
        }
    ],
    "responseDate":"2015-07-06T12:
20:13.071",
    "networkType":"LIVE",
    "engineType":"PPE",
    "reportIndex":"lk_smp_report_transaction_log_64000001_*",
    "timezone":0.00
}';

$resp = new SettlementReportJsonHelper();
$test = $resp->fromJson($data);

print_r($test);
?>
