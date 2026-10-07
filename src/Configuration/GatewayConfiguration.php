<?php

namespace createch\PaycorpSampathVault\Configuration;

use createch\PaycorpSampathVault\Exceptions\ConfigurationException;

/**
 * Immutable, validated gateway credentials and endpoint.
 *
 * VALIDATION TIMING MATTERS
 * -------------------------
 * Nothing here throws on construction. A Laravel application must still boot
 * -- and `artisan` must still run -- when the SAMPATH_* values are absent, as
 * it did before. Validation happens in assertUsable(), called at the moment a
 * request is about to be sent, so a misconfiguration surfaces as a clear
 * error on the payment path instead of a fatal error during container build.
 */
final class GatewayConfiguration
{
    /** @var string */
    private $serviceEndpoint;

    /** @var string */
    private $authToken;

    /** @var string */
    private $hmacSecret;

    /** @var string */
    private $currency;

    /** @var string */
    private $returnUrl;

    /** @var string */
    private $cancelUrl;

    /** @var string */
    private $tokenizeClientId;

    /** @var string */
    private $purchaseClientId;

    /** @var bool */
    private $validateOnly;

    /** @var bool */
    private $allowInsecureEndpoint;

    /** @var bool */
    private $throwOnError;

    /** @var string|null */
    private $timezone;

    /** @var TransportOptions */
    private $transportOptions;

    /**
     * @param  array<string,mixed>  $values
     */
    public function __construct(array $values = array())
    {
        $this->serviceEndpoint = $this->str($values, 'service_endpoint');
        $this->authToken = $this->str($values, 'authtoken');
        $this->hmacSecret = $this->str($values, 'hmac_secret');
        $this->currency = $this->str($values, 'currency');
        $this->returnUrl = $this->str($values, 'return_url');
        $this->cancelUrl = $this->str($values, 'cancel_url');
        $this->tokenizeClientId = $this->str($values, 'tokenize_client_id');
        $this->purchaseClientId = $this->str($values, 'purchase_client_id');
        $this->validateOnly = $this->bool($values, 'validate_only', false);
        $this->allowInsecureEndpoint = $this->bool($values, 'allow_insecure_endpoint', false);
        $this->throwOnError = $this->bool($values, 'throw_on_error', false);

        $timezone = $this->str($values, 'timezone');
        $this->timezone = $timezone === '' ? null : $timezone;

        $transport = isset($values['transport']) && is_array($values['transport'])
            ? $values['transport']
            : array();
        $this->transportOptions = new TransportOptions($transport);
    }

    /**
     * Fail fast, with a message naming exactly what is missing.
     *
     * @param  string[]  $additionalRequired  Keys required by the specific
     *                                        operation, e.g. return_url.
     * @return void
     *
     * @throws ConfigurationException
     */
    public function assertUsable(array $additionalRequired = array())
    {
        $required = array_merge(
            array(
                'SAMPATH_SERVICE_ENDPOINT' => $this->serviceEndpoint,
                'SAMPATH_AUTHTOKEN' => $this->authToken,
                'SAMPATH_HMAC' => $this->hmacSecret,
            ),
            $additionalRequired
        );

        $missing = array();

        foreach ($required as $name => $value) {
            if (trim((string) $value) === '') {
                $missing[] = $name;
            }
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
    private function assertEndpointIsSecure()
    {
        $scheme = strtolower((string) parse_url($this->serviceEndpoint, PHP_URL_SCHEME));

        if ($scheme === 'https') {
            return;
        }

        if ($this->allowInsecureEndpoint) {
            return;
        }

        throw ConfigurationException::insecureEndpoint($this->serviceEndpoint);
    }

    /** @return string */
    public function serviceEndpoint()
    {
        return $this->serviceEndpoint;
    }

    /** @return string */
    public function authToken()
    {
        return $this->authToken;
    }

    /** @return string */
    public function hmacSecret()
    {
        return $this->hmacSecret;
    }

    /** @return string */
    public function currency()
    {
        return $this->currency;
    }

    /** @return string */
    public function returnUrl()
    {
        return $this->returnUrl;
    }

    /** @return string */
    public function cancelUrl()
    {
        return $this->cancelUrl;
    }

    /** @return string */
    public function tokenizeClientId()
    {
        return $this->tokenizeClientId;
    }

    /** @return string */
    public function purchaseClientId()
    {
        return $this->purchaseClientId;
    }

    /** @return bool */
    public function isValidateOnly()
    {
        return $this->validateOnly;
    }

    /**
     * When true, operations rethrow instead of returning ['status' => false].
     *
     * Opt-in so the historical array-returning contract stays the default.
     *
     * @return bool
     */
    public function throwsOnError()
    {
        return $this->throwOnError;
    }

    /** @return string|null */
    public function timezone()
    {
        return $this->timezone;
    }

    /** @return TransportOptions */
    public function transportOptions()
    {
        return $this->transportOptions;
    }

    /**
     * @param  array<string,mixed>  $overrides
     * @return self  A new instance; this one is unchanged.
     */
    public function with(array $overrides)
    {
        return new self(array_merge($this->toArray(), $overrides));
    }

    /**
     * Credentials are intentionally included: this feeds with() and the
     * container, never a log. Use __debugInfo() for anything human-facing.
     *
     * @return array<string,mixed>
     */
    public function toArray()
    {
        return array(
            'service_endpoint' => $this->serviceEndpoint,
            'authtoken' => $this->authToken,
            'hmac_secret' => $this->hmacSecret,
            'currency' => $this->currency,
            'return_url' => $this->returnUrl,
            'cancel_url' => $this->cancelUrl,
            'tokenize_client_id' => $this->tokenizeClientId,
            'purchase_client_id' => $this->purchaseClientId,
            'validate_only' => $this->validateOnly,
            'allow_insecure_endpoint' => $this->allowInsecureEndpoint,
            'throw_on_error' => $this->throwOnError,
            'timezone' => $this->timezone,
            'transport' => $this->transportOptions->toArray(),
        );
    }

    /**
     * Keep secrets out of var_dump(), dd() and stack traces.
     *
     * @return array<string,mixed>
     */
    public function __debugInfo(): array
    {
        $safe = $this->toArray();
        $safe['authtoken'] = $this->authToken === '' ? '' : '[REDACTED]';
        $safe['hmac_secret'] = $this->hmacSecret === '' ? '' : '[REDACTED]';

        return $safe;
    }

    /**
     * @param  array<string,mixed>  $values
     * @param  string               $key
     * @return string
     */
    private function str(array $values, $key)
    {
        if (! array_key_exists($key, $values) || $values[$key] === null) {
            return '';
        }

        return is_scalar($values[$key]) ? trim((string) $values[$key]) : '';
    }

    /**
     * @param  array<string,mixed>  $values
     * @param  string               $key
     * @param  bool                 $default
     * @return bool
     */
    private function bool(array $values, $key, $default)
    {
        if (! array_key_exists($key, $values) || $values[$key] === null) {
            return $default;
        }

        $value = $values[$key];

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), array('1', 'true', 'on', 'yes'), true);
        }

        return (bool) $value;
    }
}
