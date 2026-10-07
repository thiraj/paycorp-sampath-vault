<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers;

use createch\PaycorpSampathVault\Exceptions\UnsupportedOperationException;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;

/**
 * Placeholder for PAYMENT_BATCH.
 *
 * The original class was an empty body with no methods at all, so
 * Payment::batch() could never have produced a request even if the missing
 * Operation::$PAYMENT_BATCH constant had existed.
 *
 * Both methods now fail loudly instead of silently producing nothing. A
 * fabricated batch wire format is the one thing more dangerous than an
 * unimplemented one: a wrong field name in a batch of real debits does not
 * fail safe. Supply the Paycorp batch specification and this becomes a normal
 * helper.
 */
class PaymentBatchJsonHelper implements IJsonHelper {

    /**
     * @param  array<string,mixed>  $json
     * @return never
     *
     * @throws UnsupportedOperationException
     */
    public function fromJson($json) {
        throw UnsupportedOperationException::notImplemented('PAYMENT_BATCH');
    }

    /**
     * @param  object  $instance
     * @return never
     *
     * @throws UnsupportedOperationException
     */
    public function toJson($instance) {
        throw UnsupportedOperationException::notImplemented('PAYMENT_BATCH');
    }

}
