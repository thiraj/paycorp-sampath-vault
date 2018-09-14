<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientFacade;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\RestClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\HmacUtils;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientRoot\PaycorpRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\RequestHeader;

abstract class BaseFacade {

    protected $config;

    protected function __construct($config) {
        $this->config = $config;
    }

    protected function process($request, $operation, $jsonHelper) {
        $jsonRequest = $this->buildRequest($request, $operation, $jsonHelper);
//        echo '<div class="col-lg-10 col-lg-offset-1"><div class="alert alert-info" role="alert"><strong>Request</strong> ' . $jsonRequest .'</div><br><br/></div>';

        $headers = $this->buildHeaders($jsonRequest);
        
        $jsonResponse = RestClient::sendRequest($this->config, $jsonRequest, $headers);
        $isValidResponse = strpos($jsonResponse, 'responseData');
//        if($isValidResponse === FALSE){
//            echo '<div class="col-lg-10 col-lg-offset-1"><div class="alert alert-danger" role="alert"><strong>Oh snap!</strong> Something Wrong The response is ' . $jsonResponse . ' </div></div>';
//
//        }
//        else {
//            echo '<div class="col-lg-10 col-lg-offset-1"><div class="alert alert-success" role="alert"><strong>Response</strong> ' . $jsonResponse .'</div></div>';
//        }

        return $this->buildResponse($jsonResponse, $jsonHelper);
    }

    private function buildHeaders($request) {
        $header = new RequestHeader();
        $header->setAuthToken($this->config->getAuthToken());
        $header->setHmac(HmacUtils::genarateHmac($this->config->getHmacSecret(), $request));

        $headers = array();
        $headers[] = 'HMAC: ' . $header->getHmac() . '';
        $headers[] = 'AUTHTOKEN: ' . $header->getAuthToken() . '';
        $headers[] = 'Content-Type: application/json';

        return $headers;
    }

    private function buildRequest($requestData, $operation, $jsonHelper) {
        $paycorpRequest = new PaycorpRequest();
        $paycorpRequest->setOperation($operation);
        $paycorpRequest->setRequestDate(date('Y-m-d H:i:s'));
        $paycorpRequest->setValidateOnly($this->config->isValidateOnly());
        $paycorpRequest->setRequestData($requestData);

        $jsonRequest = $jsonHelper->toJson($paycorpRequest);
        return json_encode($jsonRequest);
    }

    private function buildResponse($response, $jsonHelper) {
        $paycorpResponse = $jsonHelper->fromJson(json_decode($response, TRUE));
        return $paycorpResponse;
    }

}
