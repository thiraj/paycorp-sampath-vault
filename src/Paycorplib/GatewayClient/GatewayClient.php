<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClient;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientFacade\Payment;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientFacade\Vault;
use createch\PaycorpSampathVault\Support\ClientRuntime;

/**
 * Entry point to the two gateway facades.
 *
 * The optional ClientRuntime is the injection point that makes every request
 * path testable: pass a FakeHttpTransport-backed runtime and the whole client
 * runs without a network.
 */
class GatewayClient {

    public $payment;
    public $vault;

    /** @var ClientRuntime */
    private $runtime;

    /**
     * @param  ClientConfig        $config
     * @param  ClientRuntime|null  $runtime  Defaults to the production collaborators.
     */
    public function __construct(ClientConfig $config, ?ClientRuntime $runtime = null) {
        // Built once and shared, so both facades sign with the same key and
        // stamp requests from the same clock.
        $this->runtime = $runtime ?: ClientRuntime::forSecret(
            $config->getHmacSecret(),
            $config->getTimezone()
        );

        $this->payment = new Payment($config, $this->runtime);
        $this->vault = new Vault($config, $this->runtime);
    }

    /**
     * @return ClientRuntime
     */
    public function getRuntime() {
        return $this->runtime;
    }

    public function getPayment() {
        return $this->payment;
    }

    public function setPayment($payment) {
        $this->payment = $payment;
    }

    public function getVault() {
        return $this->vault;
    }

    public function setVault($vault) {
        $this->vault = $vault;
    }

}
