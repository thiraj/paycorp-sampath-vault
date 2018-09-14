<?php

namespace createch\PaycorpSampathVault;

use createch\PaycorpSampathVault\Paycorplib\GatewayClient\GatewayClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentRealTimeRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentInitRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentCompleteRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\CreditCard;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\TransactionAmount;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums\TransactionType;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\Redirect;


class PaycorpSampathVault
{
    private $client;
    private $clientConfig;
    private $returnUrl;
    private $clientIdTokenize;
    private $clientIdPurchase;
    private $currency;
    private $response = [];

    public function __construct()
    {
        $this->clientConfig = new ClientConfig();
        $this->clientConfig->setServiceEndpoint(env('SAMPATH_SERVICE_ENDPOINT', ''));
        $this->clientConfig->setAuthToken(env('SAMPATH_AUTHTOKEN',''));
        $this->clientConfig->setHmacSecret(env('SAMPATH_HMAC',''));

        $this->client = new GatewayClient($this->clientConfig);

        $this->returnUrl = env('SAMPATH_RETURN_URL','');
        $this->clientIdTokenize = env('SAMPATH_TOKENIZE_CLIENT_ID','');
        $this->clientIdPurchase = env('SAMPATH_PURCHASE_CLIENT_ID','');
        $this->currency = env('SAMPATH_CURRENCY','');

    }

    public function IPGLoaded(){
        return "1.0.0.1";
    }

    public function initRequest($data){

        try{

            $initRequest = new PaymentInitRequest();

            $initRequest->setClientId($this->clientIdTokenize);
            $initRequest->setTransactionType(TransactionType::$PURCHASE);
            $initRequest->setClientRef($data['clientRef'] ? $data['clientRef'] : '');
            $initRequest->setComment($data['comment'] ? $data['comment']: '');
            $initRequest->setTokenize(TRUE);
//        $initRequest->setExtraData(array("msisdn" => "$msisdn", "sessionId" => "$sessionId"));

            $transactionAmount = new TransactionAmount($data['total_amount']);
            $transactionAmount->setTotalAmount($data['total_amount']);
            $transactionAmount->setServiceFeeAmount($data['service_fee_amount']);
            $transactionAmount->setPaymentAmount($data['payment_amount']);
            $transactionAmount->setCurrency($this->currency);
            $initRequest->setTransactionAmount($transactionAmount);

            $redirect = new Redirect($this->returnUrl);
            $redirect->setReturnMethod("GET");
            $initRequest->setRedirect($redirect);

            $initResponse = $this->client->getPayment()->init($initRequest);

            if($initResponse->getReqid() != NULL){

                $this->response['reqid'] = $initResponse->getReqid();
                $this->response['payment_page_url'] = $initResponse->getPaymentPageUrl();
                $this->response['status'] = true;

                return $this->response;
            }else{

                $this->response['status'] = false;
                $this->response['msg'] = "Payment init request failed";

                return $this->response;
            }

        }catch (\Exception $e){

            $this->response['status'] = false;
            $this->response['msg'] = $e->getMessage();

            return $this->response;
        }

    }

    public function realTimePayment($data){

        try{

            $creditCard = new CreditCard();
            $creditCard->setNumber($data['token']);
            $creditCard->setExpiry($data['expire_at']);

            $realTimeRequest = new PaymentRealTimeRequest();
            $realTimeRequest->setClientId($this->clientIdPurchase);
            $realTimeRequest->setTransactionType(TransactionType::$PURCHASE);
            $realTimeRequest->setCreditCard($creditCard);

            $extraData = array("invoice-no" => "I99999", "job-no" => "J10101");
            $realTimeRequest->setExtraData($extraData);

            $transactionAmount = new TransactionAmount($data['amount']);
            $transactionAmount->setCurrency($this->currency);
            $realTimeRequest->setTransactionAmount($transactionAmount);
            $realTimeRequest->setClientRef($data['clientRef'] ? $data['clientRef'] : '');
            $realTimeRequest->setComment($data['comment'] ? $data['comment']: '');
            $realTimeResponse = $this->client->getPayment()->realTime($realTimeRequest);

            $this->response['TxnReference'] = $realTimeResponse->getTxnReference();
            $this->response['ResponseCode'] = $realTimeResponse->getResponseCode();
            $this->response['ResponseText'] = $realTimeResponse->getResponseText();
            $this->response['SettlementDate'] = $realTimeResponse->getSettlementDate();
            $this->response['AuthCode'] = $realTimeResponse->getAuthCode();
            $this->response['status'] = true;

            return $realTimeResponse;

        }catch(\Exception $e){

            $this->response['status'] = false;
            $this->response['msg'] = $e->getMessage();

            return $this->response;
        }

    }

    public function completeRequest($data){

        try{

            $completeRequest = new PaymentCompleteRequest();
            $completeRequest->setClientId($this->clientIdTokenize);
            $completeRequest->setReqid($data['reqid']);

            $completeResponse = $this->client->getPayment()->complete($completeRequest);

            $this->response['ResponseCode'] = $completeResponse->getResponseCode();
            $this->response['ClientID'] = $completeResponse->getClientId();
            $this->response['TransactionType'] = $completeResponse->getTransactionType();
            $this->response['CardNumber'] = $completeResponse->getCreditCard()->getNumber();
            $this->response['ExpireAt'] = $completeResponse->getCreditCard()->getExpiry();
            $this->response['ClientRef'] = $completeResponse->getClientRef();
            $this->response['Comment'] = $completeResponse->getComment();
            $this->response['TxnReference'] = $completeResponse->getTxnReference();
            $this->response['ResponseText'] = $completeResponse->getResponseText();
            $this->response['AuthCode'] = $completeResponse->getAuthCode();
            $this->response['ExtraData'] = $completeResponse->getExtraData();
            $this->response['Token'] = $completeResponse->getToken();
            $this->response['status'] = true;

            return $this->response;

        }catch (\Exception $e){

            $this->response['status'] = false;
            $this->response['msg'] = "Payment not completed";
            $this->response['ResponseText'] = $e->getMessage();
        }

    }
}