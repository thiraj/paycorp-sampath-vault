<?php

namespace createch\PaycorpSampathVault\Contracts;

/**
 * The hosted (redirect) payment page flow: initialise, send the cardholder to
 * the gateway, then complete once they return.
 *
 * Kept separate from RealTimePaymentGatewayInterface so that a consumer which
 * only redirects never has to depend on card-data-handling methods.
 */
interface HostedPaymentGatewayInterface
{
    /**
     * Open a hosted payment session.
     *
     * @param  array<string,mixed>  $data  Keys: total_amount, service_fee_amount,
     *                                     payment_amount, clientRef, comment.
     * @return array<string,mixed>         On success: reqid, payment_page_url,
     *                                     status=true. On failure: status=false, msg.
     */
    public function initRequest($data);

    /**
     * Settle a hosted payment session after the cardholder returns.
     *
     * @param  array<string,mixed>  $data  Keys: reqid.
     * @return array<string,mixed>
     */
    public function completeRequest($data);
}
