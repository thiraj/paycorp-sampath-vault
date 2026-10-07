<?php

namespace createch\PaycorpSampathVault;

use createch\PaycorpSampathVault\Configuration\ConfigurationFactory;
use createch\PaycorpSampathVault\Configuration\GatewayConfiguration;
use createch\PaycorpSampathVault\Contracts\HttpTransportInterface;
use createch\PaycorpSampathVault\Contracts\RealTimePaymentGatewayInterface;
use createch\PaycorpSampathVault\Exceptions\PaycorpException;
use createch\PaycorpSampathVault\Paycorplib\GatewayClient\GatewayClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\CreditCard;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\TransactionAmount;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums\TransactionType;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentRealTimeRequest;
use createch\PaycorpSampathVault\Support\ClientRuntime;
use Throwable;

/**
 * Real-time authorisation against raw card details rather than a vault token.
 *
 * PCI DSS WARNING
 * ---------------
 * This class accepts a PAN and a CVV directly, which brings the whole
 * application into PCI DSS scope: card data must never be logged, cached,
 * written to a session, or persisted anywhere. Prefer the tokenised flow --
 * PaycorpSampathVault::initRequest() followed by realTimePayment() against the
 * returned token -- which keeps the PAN inside the gateway's hosted page and
 * out of this application entirely.
 *
 * The method signature and the returned array shape are unchanged. The
 * corrections are the same as in PaycorpSampathVault: configuration is read
 * from the config repository rather than env() at construction time, a failed
 * HTTP exchange is reported as a failure instead of a success with blank
 * fields, and the returned message is redacted so a PAN cannot reach a log.
 */
class PaycorpSampathRealTimePayment implements RealTimePaymentGatewayInterface
{
    /** @var GatewayConfiguration */
    private $configuration;

    /** @var ClientConfig */
    private $clientConfig;

    /** @var GatewayClient */
    private $client;

    /**
     * @param  GatewayConfiguration|array<string,mixed>|null  $configuration
     * @param  GatewayClient|null           $client
     * @param  HttpTransportInterface|null  $transport
     */
    public function __construct(
        $configuration = null,
        ?GatewayClient $client = null,
        ?HttpTransportInterface $transport = null
    ) {
        $this->configuration = ConfigurationFactory::resolve($configuration);
        $this->clientConfig = ClientConfig::fromGatewayConfiguration($this->configuration);

        if ($client !== null) {
            $this->client = $client;

            return;
        }

        $runtime = ClientRuntime::forSecret(
            $this->configuration->hmacSecret(),
            $this->configuration->timezone(),
            $transport
        );

        $this->client = new GatewayClient($this->clientConfig, $runtime);
    }

    /**
     * @return GatewayConfiguration
     */
    public function configuration()
    {
        return $this->configuration;
    }

    /**
     * @return GatewayClient
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * Authorise a payment using raw card details.
     *
     * @param  array<string,mixed>  $data  Keys: card_type, card_holder_name,
     *                                     expire_at, card_number, secure_id,
     *                                     amount, clientRef, comment.
     * @return array<string,mixed>
     */
    public function realTimePayment($data)
    {
        $data = is_array($data) ? $data : array();

        try {
            $this->configuration->assertUsable(array(
                'SAMPATH_PURCHASE_CLIENT_ID' => $this->configuration->purchaseClientId(),
            ));

            $creditCard = new CreditCard();
            $creditCard->setType($this->value($data, 'card_type'));
            $creditCard->setHolderName($this->value($data, 'card_holder_name'));
            $creditCard->setExpiry($this->value($data, 'expire_at'));
            $creditCard->setNumber($this->value($data, 'card_number'));
            $creditCard->setSecureId($this->value($data, 'secure_id'));
            $creditCard->setSecureIdSupplied(true);

            $realTimeRequest = new PaymentRealTimeRequest();
            $realTimeRequest->setClientId($this->configuration->purchaseClientId());
            $realTimeRequest->setTransactionType(TransactionType::$PURCHASE);
            $realTimeRequest->setCreditCard($creditCard);

            $transactionAmount = new TransactionAmount($this->value($data, 'amount'));
            $transactionAmount->setCurrency($this->configuration->currency());
            $realTimeRequest->setTransactionAmount($transactionAmount);
            $realTimeRequest->setClientRef($this->value($data, 'clientRef'));
            $realTimeRequest->setComment($this->value($data, 'comment'));

            if (isset($data['extraData']) && is_array($data['extraData'])) {
                $realTimeRequest->setExtraData($data['extraData']);
            }

            $realTimeResponse = $this->client->getPayment()->realTime($realTimeRequest);

            return array(
                'TxnReference' => $realTimeResponse->getTxnReference(),
                'ResponseCode' => $realTimeResponse->getResponseCode(),
                'ResponseText' => $realTimeResponse->getResponseText(),
                'SettlementDate' => $realTimeResponse->getSettlementDate(),
                'AuthCode' => $realTimeResponse->getAuthCode(),
                'status' => true,
            );
        } catch (Throwable $e) {
            if ($this->configuration->throwsOnError()) {
                throw $e instanceof PaycorpException
                    ? $e
                    : new PaycorpException($this->safeMessage($e), (int) $e->getCode(), $e);
            }

            return array(
                'status' => false,
                'msg' => $this->safeMessage($e),
                // 'unknown' means the money may have moved: reconcile, do not retry blindly.
                'outcome' => $this->isUnknownOutcome($e) ? 'unknown' : 'failed',
            );
        }
    }

    /**
     * @param  array<string,mixed>  $data
     * @param  string               $key
     * @param  string               $default
     * @return mixed
     */
    private function value(array $data, $key, $default = '')
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }

    /**
     * @param  Throwable  $e
     * @return bool
     */
    private function isUnknownOutcome($e)
    {
        return $e instanceof \createch\PaycorpSampathVault\Exceptions\TransportException
            || $e instanceof \createch\PaycorpSampathVault\Exceptions\MalformedResponseException;
    }

    /**
     * @param  Throwable  $e
     * @return string
     */
    private function safeMessage($e)
    {
        return $this->client->getRuntime()->redactor()->redact($e->getMessage());
    }
}
