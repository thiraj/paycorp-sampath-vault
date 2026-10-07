<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientFacade;

use createch\PaycorpSampathVault\Exceptions\ConfigurationException;
use createch\PaycorpSampathVault\Exceptions\GatewayErrorException;
use createch\PaycorpSampathVault\Exceptions\MalformedResponseException;
use createch\PaycorpSampathVault\Exceptions\PaycorpException;
use createch\PaycorpSampathVault\Exceptions\TransportException;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\RequestHeader;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientRoot\PaycorpRequest;
use createch\PaycorpSampathVault\Support\Arr;
use createch\PaycorpSampathVault\Support\ClientRuntime;

/**
 * Shared request/response pipeline for the Payment and Vault facades.
 *
 * Single responsibility: turn a request object into a signed HTTP exchange
 * and hand the decoded envelope to the operation's own JSON helper. It knows
 * nothing about individual operations, and the helpers know nothing about
 * HTTP -- which is what makes both halves testable in isolation.
 *
 * WHAT CHANGED FROM THE LEGACY IMPLEMENTATION
 * -------------------------------------------
 * The old process() computed $isValidResponse = strpos($json, 'responseData')
 * and then threw the result away, so a body that was not a gateway envelope
 * at all -- an HTML 502 page, an empty string from a failed curl call -- was
 * passed to json_decode() and the resulting null was read by the helpers as a
 * complete set of empty fields. A network outage therefore produced what
 * looked like a successful transaction with a blank response code.
 *
 * Every failure mode now raises a distinct exception, so the caller can tell
 * "declined" (a real envelope) from "we do not know what happened"
 * (TransportException / MalformedResponseException). That distinction is the
 * difference between showing a customer a decline message and reconciling a
 * possibly-captured payment.
 *
 * It also no longer echoes request and response bodies to standard output;
 * those debug lines printed PANs and HMACs straight into the HTTP response.
 */
abstract class BaseFacade {

    /** @var ClientConfig */
    protected $config;

    /** @var ClientRuntime */
    protected $runtime;

    /**
     * @param  ClientConfig        $config
     * @param  ClientRuntime|null  $runtime  Defaults to the production collaborators.
     */
    protected function __construct($config, ?ClientRuntime $runtime = null) {
        $this->config = $config;

        if ($runtime !== null) {
            $this->runtime = $runtime;

            return;
        }

        // $config is untyped for backwards compatibility: 1.x call sites could
        // pass anything. An invalid value is reported by assertEndpointConfigured()
        // when a request is attempted, not here, so the container still builds.
        $this->runtime = $config instanceof ClientConfig
            ? ClientRuntime::forSecret($config->getHmacSecret(), $config->getTimezone())
            : ClientRuntime::forSecret('');
    }

    /**
     * @return ClientRuntime
     */
    public function getRuntime() {
        return $this->runtime;
    }

    /**
     * Execute one gateway operation.
     *
     * @param  object  $request    The operation's request object.
     * @param  string  $operation  An Operation constant.
     * @param  object  $jsonHelper An IJsonHelper implementation.
     * @return object              The operation's response object.
     *
     * @throws ConfigurationException     Credentials or endpoint unusable.
     * @throws TransportException         The exchange did not complete; outcome unknown.
     * @throws MalformedResponseException The gateway replied with something unreadable.
     * @throws GatewayErrorException      The gateway rejected the request.
     */
    protected function process($request, $operation, $jsonHelper) {
        $this->assertEndpointConfigured();

        $jsonRequest = $this->buildRequest($request, $operation, $jsonHelper);
        $headers = $this->buildHeaders($jsonRequest);

        $response = $this->runtime->transport()->post(
            $this->config->getServiceEndpoint(),
            $jsonRequest,
            $headers,
            $this->config->getTransportOptions()
        );

        if (! $response->isSuccessful()) {
            throw TransportException::unexpectedStatus(
                $response->statusCode(),
                $this->runtime->redactor()->redact($response->body())
            );
        }

        return $this->buildResponse($response->body(), $jsonHelper);
    }

    /**
     * @return void
     *
     * @throws ConfigurationException
     */
    private function assertEndpointConfigured() {
        if (! $this->config instanceof ClientConfig) {
            throw new ConfigurationException(
                'A ClientConfig instance is required to talk to the Paycorp gateway.'
            );
        }

        $missing = array();

        if (trim((string) $this->config->getServiceEndpoint()) === '') {
            $missing[] = 'SAMPATH_SERVICE_ENDPOINT';
        }

        if (trim((string) $this->config->getAuthToken()) === '') {
            $missing[] = 'SAMPATH_AUTHTOKEN';
        }

        if (trim((string) $this->config->getHmacSecret()) === '') {
            $missing[] = 'SAMPATH_HMAC';
        }

        if ($missing !== array()) {
            throw ConfigurationException::missingValues($missing);
        }

        $this->assertEndpointIsSecure();
    }

    /**
     * Refuse to put cardholder data on a plain-HTTP connection.
     *
     * @return void
     *
     * @throws ConfigurationException
     */
    private function assertEndpointIsSecure() {
        $endpoint = (string) $this->config->getServiceEndpoint();
        $scheme = strtolower((string) parse_url($endpoint, PHP_URL_SCHEME));

        if ($scheme === 'https' || $this->config->allowsInsecureEndpoint()) {
            return;
        }

        throw ConfigurationException::insecureEndpoint($endpoint);
    }

    /**
     * @param  string  $request  The exact body that will be sent.
     * @return array<int,string>
     */
    private function buildHeaders($request) {
        $header = new RequestHeader();
        $header->setAuthToken($this->config->getAuthToken());
        $header->setHmac($this->runtime->signer()->sign($request));

        return array(
            'HMAC: ' . $header->getHmac(),
            'AUTHTOKEN: ' . $header->getAuthToken(),
            'Content-Type: application/json',
            'Accept: application/json',
        );
    }

    /**
     * @param  object  $requestData
     * @param  string  $operation
     * @param  object  $jsonHelper
     * @return string  JSON, exactly as it will be signed and sent.
     *
     * @throws PaycorpException
     */
    private function buildRequest($requestData, $operation, $jsonHelper) {
        $paycorpRequest = new PaycorpRequest();
        $paycorpRequest->setMsgId($this->runtime->messageIds()->generate());
        $paycorpRequest->setOperation($operation);
        $paycorpRequest->setRequestDate($this->runtime->clock()->requestTimestamp());
        $paycorpRequest->setValidateOnly($this->config->isValidateOnly());
        $paycorpRequest->setRequestData($requestData);

        $payload = $jsonHelper->toJson($paycorpRequest);
        $json = json_encode($payload);

        // json_encode() returns false on malformed UTF-8. Signing "false"
        // would produce a valid HMAC over an empty body and the gateway would
        // answer with a confusing validation error instead of the real cause.
        if ($json === false) {
            throw new PaycorpException(
                'The gateway request could not be encoded as JSON (' . json_last_error_msg() . '). '
                . 'This usually means one of the submitted values is not valid UTF-8.'
            );
        }

        return $json;
    }

    /**
     * @param  string  $response
     * @param  object  $jsonHelper
     * @return object
     *
     * @throws MalformedResponseException
     * @throws GatewayErrorException
     */
    private function buildResponse($response, $jsonHelper) {
        $redacted = $this->runtime->redactor()->redact($response);

        if (trim((string) $response) === '') {
            throw MalformedResponseException::because($redacted, 'the body was empty');
        }

        $decoded = json_decode($response, true);

        if (! is_array($decoded)) {
            throw MalformedResponseException::because(
                $redacted,
                'the body was not a JSON object (' . json_last_error_msg() . ')'
            );
        }

        $this->assertNoGatewayError($decoded);

        if (! Arr::has($decoded, 'responseData')) {
            throw MalformedResponseException::because(
                $redacted,
                'the envelope contained neither a responseData nor an error node'
            );
        }

        return $jsonHelper->fromJson($decoded);
    }

    /**
     * Surface a gateway-level rejection -- a bad HMAC, an unknown clientId, a
     * validation failure -- rather than letting it read as empty response fields.
     *
     * @param  array<string,mixed>  $decoded
     * @return void
     *
     * @throws GatewayErrorException
     */
    private function assertNoGatewayError(array $decoded) {
        $error = Arr::get($decoded, 'error');

        if ($error === null || $error === array() || $error === '') {
            return;
        }

        if (! is_array($error)) {
            throw GatewayErrorException::fromEnvelope('', (string) $error);
        }

        $code = Arr::getString($error, 'errorCode', Arr::getString($error, 'code'));
        $description = Arr::getString(
            $error,
            'errorDescription',
            Arr::getString($error, 'description', Arr::getString($error, 'message'))
        );

        throw GatewayErrorException::fromEnvelope($code, $description);
    }

}
