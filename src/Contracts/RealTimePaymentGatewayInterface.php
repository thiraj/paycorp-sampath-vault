<?php

namespace createch\PaycorpSampathVault\Contracts;

/**
 * Server-to-server authorisation, either against a stored vault token or
 * against raw card details.
 */
interface RealTimePaymentGatewayInterface
{
    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>  On success: TxnReference, ResponseCode,
     *                              ResponseText, SettlementDate, AuthCode,
     *                              status=true. On failure: status=false, msg.
     */
    public function realTimePayment($data);
}
