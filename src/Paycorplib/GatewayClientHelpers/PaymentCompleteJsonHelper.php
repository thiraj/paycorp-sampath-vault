<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\CreditCard;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\TransactionAmount;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentCompleteResponse;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use createch\PaycorpSampathVault\Support\Arr;

/**
 * Wire format for PAYMENT_COMPLETE.
 *
 * Now declares IJsonHelper, which it previously did not -- it satisfied the
 * interface by coincidence, so nothing stopped a future edit from breaking the
 * contract BaseFacade relies on.
 *
 * DEFAULTS ARE LOAD-BEARING
 * -------------------------
 * The original mixed guarded and unguarded reads, which produced an odd but
 * observable set of fallbacks: a missing clientRef, feeReference, token or
 * withholdingAmount became the integer 0, a missing comment or
 * tokenResponseText became "", and every other missing field became null
 * (with a PHP warning). Callers compare against those values, so each one is
 * reproduced exactly rather than normalised. Arr::get() treats
 * present-but-null as missing, matching the isset() checks it replaces.
 *
 * The only behavioural change is that a missing field no longer emits a
 * warning or throws under a strict error handler.
 */
class PaymentCompleteJsonHelper implements IJsonHelper {

    public function fromJson($responseData) {
        $response = new PaymentCompleteResponse();

        $response->setClientId(Arr::get($responseData, 'responseData.clientId'));
        $response->setClientIdHash(Arr::get($responseData, 'responseData.clientIdHash'));
        $response->setTransactionType(Arr::get($responseData, 'responseData.transactionType'));

        $creditCard = new CreditCard();
        $creditCard->setType(Arr::get($responseData, 'responseData.creditCard.type'));
        $creditCard->setHolderName(Arr::get($responseData, 'responseData.creditCard.holderName'));
        $creditCard->setNumber(Arr::get($responseData, 'responseData.creditCard.number'));
        $creditCard->setExpiry(Arr::get($responseData, 'responseData.creditCard.expiry'));
        $response->setCreditCard($creditCard);

        $transactionAmount = new TransactionAmount(
            Arr::get($responseData, 'responseData.transactionAmount.paymentAmount')
        );
        $transactionAmount->setTotalAmount(Arr::get($responseData, 'responseData.transactionAmount.totalAmount'));
        $transactionAmount->setPaymentAmount(Arr::get($responseData, 'responseData.transactionAmount.paymentAmount'));
        $transactionAmount->setServiceFeeAmount(Arr::get($responseData, 'responseData.transactionAmount.serviceFeeAmount'));
        // Legacy fallback: 0, not null.
        $transactionAmount->setWithholdingAmount(Arr::get($responseData, 'responseData.transactionAmount.withholdingAmount', 0));
        $transactionAmount->setCurrency(Arr::get($responseData, 'responseData.transactionAmount.currency'));
        $response->setTransactionAmount($transactionAmount);

        // Legacy fallback: 0, not null or "".
        $response->setClientRef(Arr::get($responseData, 'responseData.clientRef', 0));
        // Legacy fallback: "".
        $response->setComment(Arr::get($responseData, 'responseData.comment', ""));
        $response->setTxnReference(Arr::get($responseData, 'responseData.txnReference'));
        // Legacy fallback: 0.
        $response->setFeeReference(Arr::get($responseData, 'responseData.feeReference', 0));
        $response->setResponseCode(Arr::get($responseData, 'responseData.responseCode'));
        $response->setResponseText(Arr::get($responseData, 'responseData.responseText'));
        $response->setSettlementDate(Arr::get($responseData, 'responseData.settlementDate'));
        // Legacy fallback: 0.
        $response->setToken(Arr::get($responseData, 'responseData.token', 0));
        $response->setTokenized(Arr::get($responseData, 'responseData.tokenized'));
        // Legacy fallback: "".
        $response->setTokenResponseText(Arr::get($responseData, 'responseData.tokenResponseText', ""));
        $response->setAuthCode(Arr::get($responseData, 'responseData.authCode'));
        $response->setCvcResponse(Arr::get($responseData, 'responseData.cvcResponse'));
        // Never mapped by the original helper; mapped now because the field is
        // echoed back verbatim from the request, so there is nothing to guess.
        $response->setExtraData(Arr::get($responseData, 'responseData.extraData'));

        return $response;
    }

    public function toJson($paycorpRequest) {
        $requestData = $paycorpRequest->getRequestData();

        return array(
            "version" => (string) $paycorpRequest->getVersion(),
            "msgId" => (string) $paycorpRequest->getMsgId(),
            "operation" => (string) $paycorpRequest->getOperation(),
            "requestDate" => (string) $paycorpRequest->getRequestDate(),
            "validateOnly" => $paycorpRequest->getValidateOnly(),
            "requestData" => array(
                "clientId" => $requestData->getClientId(),
                "reqid" => $requestData->getReqid(),
            ),
        );
    }

}
