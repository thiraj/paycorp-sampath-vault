<?php

namespace createch\PaycorpSampathVault;

use createch\PaycorpSampathVault\Configuration\ConfigurationFactory;
use createch\PaycorpSampathVault\Configuration\GatewayConfiguration;
use createch\PaycorpSampathVault\Contracts\HostedPaymentGatewayInterface;
use createch\PaycorpSampathVault\Contracts\HttpTransportInterface;
use createch\PaycorpSampathVault\Contracts\RealTimePaymentGatewayInterface;
use createch\PaycorpSampathVault\Exceptions\PaycorpException;
use createch\PaycorpSampathVault\Paycorplib\GatewayClient\GatewayClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\CreditCard;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\Redirect;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\TransactionAmount;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums\TransactionType;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentCompleteRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentInitRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientPayment\PaymentRealTimeRequest;
use createch\PaycorpSampathVault\Support\ClientRuntime;
use Throwable;

/**
 * High-level Sampath Bank (Paycorp) gateway client.
 *
 * Every public method keeps the signature and the exact array shape it has
 * always returned, so existing integrations need no changes.
 *
 * WHAT CHANGED BEHIND THAT SURFACE
 * --------------------------------
 * 1. Configuration comes from the Laravel config repository, not from env()
 *    read at construction time. env() returns null under
 *    `php artisan config:cache`, so the old constructor produced an empty
 *    endpoint and an empty HMAC secret on exactly the deployments most likely
 *    to be production.
 *
 * 2. A failed HTTP exchange is reported as a failure. Previously curl_exec()
 *    returning false flowed through json_decode() as null, every field parsed
 *    as "", and realTimePayment() still returned 'status' => true -- a network
 *    outage was indistinguishable from a completed payment. A transport
 *    failure now yields 'status' => false with 'outcome' => 'unknown', because
 *    the money may or may not have moved and the caller must reconcile rather
 *    than assume.
 *
 * 3. completeRequest() returns an array on failure. It used to fall off the
 *    end of its catch block and return null.
 *
 * 4. Responses are built fresh per call. $this->response was an accumulating
 *    instance property on a container singleton, so keys from one transaction
 *    -- including card data -- leaked into the next caller's result.
 *
 * 5. Messages handed back to callers are redacted, so a PAN or an HMAC secret
 *    in an exception message cannot reach a log or an HTTP response.
 */
class PaycorpSampathVault implements HostedPaymentGatewayInterface, RealTimePaymentGatewayInterface
{
    /**
     * Frozen on purpose.
     *
     * An integration may gate on this exact string, so it keeps its historical
     * value forever. Use version() for the real package version.
     *
     * @deprecated Use version().
     */
    const LEGACY_IPG_VERSION = '1.0.0.1';

    /** Package version. */
    const VERSION = '2.0.0';

    /** @var GatewayConfiguration */
    private $configuration;

    /** @var ClientConfig */
    private $clientConfig;

    /** @var GatewayClient */
    private $client;

    /**
     * @param  GatewayConfiguration|array<string,mixed>|null  $configuration
     *         Explicit configuration. Null resolves from the Laravel config
     *         repository, falling back to environment variables.
     * @param  GatewayClient|null          $client     Mainly for tests.
     * @param  HttpTransportInterface|null $transport  Swap the network for a
     *                                                 FakeHttpTransport in tests.
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
     * Historical version probe, frozen at its original value.
     *
     * @return string
     *
     * @deprecated Use version().
     */
    public function IPGLoaded()
    {
        return self::LEGACY_IPG_VERSION;
    }

    /**
     * @return string
     */
    public function version()
    {
        return self::VERSION;
    }

    /**
     * The resolved configuration, with credentials redacted in dumps.
     *
     * @return GatewayConfiguration
     */
    public function configuration()
    {
        return $this->configuration;
    }

    /**
     * The underlying low-level client, for operations not wrapped here
     * (vault store/retrieve/update/verify/delete).
     *
     * @return GatewayClient
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * Open a hosted payment page session.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function initRequest($data)
    {
        $data = is_array($data) ? $data : array();

        try {
            $this->configuration->assertUsable(array(
                'SAMPATH_TOKENIZE_CLIENT_ID' => $this->configuration->tokenizeClientId(),
                'SAMPATH_RETURN_URL' => $this->configuration->returnUrl(),
            ));

            $initRequest = new PaymentInitRequest();
            $initRequest->setClientId($this->configuration->tokenizeClientId());
            $initRequest->setTransactionType(TransactionType::$PURCHASE);
            // ?? rather than a truthiness ternary: the original read
            // $data['clientRef'] twice and warned when the key was absent.
            $initRequest->setClientRef($this->value($data, 'clientRef'));
            $initRequest->setComment($this->value($data, 'comment'));
            $initRequest->setTokenize(true);

            $transactionAmount = new TransactionAmount($this->value($data, 'total_amount'));
            $transactionAmount->setTotalAmount($this->value($data, 'total_amount'));
            $transactionAmount->setServiceFeeAmount($this->value($data, 'service_fee_amount'));
            $transactionAmount->setPaymentAmount($this->value($data, 'payment_amount'));
            $transactionAmount->setCurrency($this->configuration->currency());
            $initRequest->setTransactionAmount($transactionAmount);

            $redirect = new Redirect($this->configuration->returnUrl());
            $redirect->setReturnMethod('GET');

            if ($this->configuration->cancelUrl() !== '') {
                $redirect->setCancelUrl($this->configuration->cancelUrl());
            }

            $initRequest->setRedirect($redirect);

            $initResponse = $this->client->getPayment()->init($initRequest);

            if ($initResponse->getReqid() === null || $initResponse->getReqid() === '') {
                return array(
                    'status' => false,
                    'msg' => 'Payment init request failed',
                );
            }

            return array(
                'reqid' => $initResponse->getReqid(),
                'payment_page_url' => $initResponse->getPaymentPageUrl(),
                'expire_at' => $initResponse->getExpireAt(),
                'status' => true,
            );
        } catch (Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * Authorise a payment against a stored vault token.
     *
     * @param  array<string,mixed>  $data
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
            $creditCard->setNumber($this->value($data, 'token'));
            $creditCard->setExpiry($this->value($data, 'expire_at'));

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
                'TxnReference' => $realTimeResponse->getTxnReference() ?: '',
                'ResponseCode' => $realTimeResponse->getResponseCode() ?: '',
                'ResponseText' => $realTimeResponse->getResponseText() ?: '',
                'SettlementDate' => $realTimeResponse->getSettlementDate() ?: '',
                'AuthCode' => $realTimeResponse->getAuthCode() ?: '',
                'status' => true,
            );
        } catch (Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * Settle a hosted payment page session after the cardholder returns.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function completeRequest($data)
    {
        $data = is_array($data) ? $data : array();

        try {
            $this->configuration->assertUsable(array(
                'SAMPATH_TOKENIZE_CLIENT_ID' => $this->configuration->tokenizeClientId(),
            ));

            $completeRequest = new PaymentCompleteRequest();
            $completeRequest->setClientId($this->configuration->tokenizeClientId());
            $completeRequest->setReqid($this->value($data, 'reqid'));

            $completeResponse = $this->client->getPayment()->complete($completeRequest);
            $creditCard = $completeResponse->getCreditCard();

            return array(
                'ResponseCode' => $completeResponse->getResponseCode(),
                'ClientID' => $completeResponse->getClientId(),
                'TransactionType' => $completeResponse->getTransactionType(),
                'CardNumber' => $creditCard === null ? null : $creditCard->getNumber(),
                'ExpireAt' => $creditCard === null ? null : $creditCard->getExpiry(),
                'ClientRef' => $completeResponse->getClientRef(),
                'Comment' => $completeResponse->getComment(),
                'TxnReference' => $completeResponse->getTxnReference(),
                'ResponseText' => $completeResponse->getResponseText(),
                'AuthCode' => $completeResponse->getAuthCode(),
                'ExtraData' => $completeResponse->getExtraData(),
                'Token' => $completeResponse->getToken(),
                'status' => true,
            );
        } catch (Throwable $e) {
            // The original catch block populated its result and then fell off
            // the end of the method, returning null to the caller.
            return $this->completionFailure($e);
        }
    }

    /**
     * Read an optional input key without warning when it is absent.
     *
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
     * Build the historical failure array, with the message redacted.
     *
     * @param  Throwable  $e
     * @return array<string,mixed>
     */
    private function failure($e)
    {
        $this->rethrowIfConfigured($e);

        return array(
            'status' => false,
            'msg' => $this->safeMessage($e),
            // 'unknown' means the request may have been processed and must be
            // reconciled; 'failed' means it demonstrably was not.
            'outcome' => $this->outcomeOf($e),
        );
    }

    /**
     * @param  Throwable  $e
     * @return array<string,mixed>
     */
    private function completionFailure($e)
    {
        $this->rethrowIfConfigured($e);

        return array(
            'status' => false,
            'msg' => 'Payment not completed',
            'ResponseText' => $this->safeMessage($e),
            'outcome' => $this->outcomeOf($e),
        );
    }

    /**
     * @param  Throwable  $e
     * @return void
     */
    private function rethrowIfConfigured($e)
    {
        if (! $this->configuration->throwsOnError()) {
            return;
        }

        if ($e instanceof PaycorpException) {
            throw $e;
        }

        throw new PaycorpException($this->safeMessage($e), (int) $e->getCode(), $e);
    }

    /**
     * Whether the transaction outcome is known to have failed, or simply unknown.
     *
     * @param  Throwable  $e
     * @return string  'failed' or 'unknown'
     */
    private function outcomeOf($e)
    {
        $unknown = array(
            'createch\PaycorpSampathVault\Exceptions\TransportException',
            'createch\PaycorpSampathVault\Exceptions\MalformedResponseException',
        );

        foreach ($unknown as $class) {
            if ($e instanceof $class) {
                return 'unknown';
            }
        }

        return 'failed';
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
