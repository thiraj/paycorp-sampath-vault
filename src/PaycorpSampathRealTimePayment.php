<?php

namespace createch\PaycorpSampathVault;

use createch\PaycorpSampathVault\Paycorplib\GatewayClient\GatewayClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentRealTimeRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\CreditCard;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\TransactionAmount;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums\TransactionType;

class PaycorpSampathRealTimePayment
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

    public function realTimePayment($data){

        try{

            $creditCard = new CreditCard();
            $creditCard->setType($data['card_type']); //VISA, MASTER
            $creditCard->setHolderName($data['card_holder_name']);
            $creditCard->setExpiry($data['expire_at']);
            $creditCard->setNumber($data['card_number']);
            $creditCard->setSecureId($data['secure_id']);
            $creditCard->setSecureIdSupplied(TRUE);

            $realTimeRequest = new PaymentRealTimeRequest();
            $realTimeRequest->setClientId($this->clientIdPurchase);
            $realTimeRequest->setTransactionType(TransactionType::$PURCHASE);
            $realTimeRequest->setCreditCard($creditCard);

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

            return $this->response;

        }catch(\Exception $e){

            $this->response['status'] = false;
            $this->response['msg'] = $e->getMessage();

            return $this->response;
        }

    }

}