<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientFacade;

use createch\PaycorpSampathVault\Exceptions\UnsupportedOperationException;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums\Operation;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\PaymentCompleteJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\PaymentInitJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\PaymentRealTimeJsonHelper;
use createch\PaycorpSampathVault\Support\ClientRuntime;

/**
 * Payment operations: hosted page init/complete and real-time authorisation.
 */
final class Payment extends BaseFacade {

    /**
     * @param  ClientConfig        $config
     * @param  ClientRuntime|null  $runtime
     */
    public function __construct($config, ?ClientRuntime $runtime = null) {
        parent::__construct($config, $runtime);
    }

    /**
     * @param  \createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentRealTimeRequest  $request
     * @return \createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentRealTimeResponse
     */
    public function realTime($request) {
        return $this->process($request, Operation::$PAYMENT_REAL_TIME, new PaymentRealTimeJsonHelper());
    }

    /**
     * @param  \createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentInitRequest  $request
     * @return \createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentInitResponse
     */
    public function init($request) {
        return $this->process($request, Operation::$PAYMENT_INIT, new PaymentInitJsonHelper());
    }

    /**
     * @param  \createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentCompleteRequest  $request
     * @return \createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentCompleteResponse
     */
    public function complete($request) {
        return $this->process($request, Operation::$PAYMENT_COMPLETE, new PaymentCompleteJsonHelper());
    }

    /**
     * Batch payment submission -- declared by the gateway, not implemented here.
     *
     * This method has never worked: it referenced Operation::$PAYMENT_BATCH,
     * which was not declared, so calling it raised
     * "Access to undeclared static property" on every PHP version since 7.0,
     * and its PaymentBatchJsonHelper is an empty class with no toJson().
     *
     * It now fails immediately with an explanatory exception. Guessing a wire
     * format for a batch of real payments would be far worse than refusing:
     * a malformed batch could debit the wrong amounts.
     *
     * @param  mixed  $request
     * @return never
     *
     * @throws UnsupportedOperationException
     */
    public function batch($request) {
        throw UnsupportedOperationException::notImplemented(Operation::$PAYMENT_BATCH);
    }

}
