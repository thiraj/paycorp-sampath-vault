<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig;

use createch\PaycorpSampathVault\Configuration\GatewayConfiguration;
use createch\PaycorpSampathVault\Configuration\TransportOptions;

/**
 * Low-level gateway connection settings.
 *
 * Every getter and setter the original class exposed still behaves exactly as
 * it did, so existing integrations that build a ClientConfig by hand keep
 * working untouched. The additions are the transport and safety settings the
 * original had no way to express: TLS verification, timeouts, a CA bundle and
 * the request timezone.
 */
class ClientConfig {

    private $serviceEndpoint;
    private $proxyHost;
    private $proxyPort;
    private $authToken;
    private $hmacSecret;
    private $validateOnly;

    /** @var TransportOptions|null Built lazily so proxy setters applied later are honoured. */
    private $transportOptions;

    /** @var bool */
    private $allowInsecureEndpoint = false;

    /** @var string|null */
    private $timezone;

    public function __construct() {
        $this->validateOnly = FALSE;
    }

    /**
     * Build a ClientConfig from the package's configuration value object.
     *
     * @param  GatewayConfiguration  $configuration
     * @return self
     */
    public static function fromGatewayConfiguration(GatewayConfiguration $configuration) {
        $config = new self();
        $config->setServiceEndpoint($configuration->serviceEndpoint());
        $config->setAuthToken($configuration->authToken());
        $config->setHmacSecret($configuration->hmacSecret());
        $config->setValidateOnly($configuration->isValidateOnly());
        $config->setTransportOptions($configuration->transportOptions());
        $config->setTimezone($configuration->timezone());

        $options = $configuration->transportOptions();

        if ($options->proxyHost() !== null) {
            $config->setProxyHost($options->proxyHost());
            $config->setProxyPort($options->proxyPort());
        }

        // Mirror the endpoint policy so BaseFacade enforces the same rule
        // whether it was handed a GatewayConfiguration or a hand-built ClientConfig.
        $raw = $configuration->toArray();
        $config->setAllowInsecureEndpoint(! empty($raw['allow_insecure_endpoint']));

        return $config;
    }

    public function getServiceEndpoint() {
        return $this->serviceEndpoint;
    }

    public function setServiceEndpoint($serviceEndpoint) {
        $this->serviceEndpoint = $serviceEndpoint;
    }

    public function getProxyHost() {
        return $this->proxyHost;
    }

    public function setProxyHost($proxyHost) {
        $this->proxyHost = $proxyHost;
        $this->transportOptions = null;
    }

    public function getProxyPort() {
        return $this->proxyPort;
    }

    public function setProxyPort($proxyPort) {
        $this->proxyPort = $proxyPort;
        $this->transportOptions = null;
    }

    public function getAuthToken() {
        return $this->authToken;
    }

    public function setAuthToken($authToken) {
        $this->authToken = $authToken;
    }

    public function getHmacSecret() {
        return $this->hmacSecret;
    }

    public function setHmacSecret($hmacSecret) {
        $this->hmacSecret = $hmacSecret;
    }

    public function isValidateOnly() {
        return $this->validateOnly;
    }

    public function setValidateOnly($validateOnly) {
        $this->validateOnly = $validateOnly;
    }

    /**
     * Transport settings for this connection.
     *
     * Defaults verify TLS and cap the total request duration; the legacy
     * client did neither.
     *
     * @return TransportOptions
     */
    public function getTransportOptions() {
        if ($this->transportOptions === null) {
            $this->transportOptions = new TransportOptions(array(
                'proxy_host' => $this->proxyHost,
                'proxy_port' => $this->proxyPort,
            ));
        }

        return $this->transportOptions;
    }

    /**
     * @param  TransportOptions  $options
     * @return void
     */
    public function setTransportOptions(TransportOptions $options) {
        $this->transportOptions = $options;
    }

    /**
     * Whether a non-HTTPS service endpoint is permitted.
     *
     * Intended only for a local sandbox. Leaving this false is what stops
     * cardholder data being posted over plain HTTP.
     *
     * @return bool
     */
    public function allowsInsecureEndpoint() {
        return $this->allowInsecureEndpoint;
    }

    /**
     * @param  bool  $allow
     * @return void
     */
    public function setAllowInsecureEndpoint($allow) {
        $this->allowInsecureEndpoint = (bool) $allow;
    }

    /**
     * Timezone used to stamp requestDate, or null for the host default.
     *
     * @return string|null
     */
    public function getTimezone() {
        return $this->timezone;
    }

    /**
     * @param  string|null  $timezone
     * @return void
     */
    public function setTimezone($timezone) {
        $this->timezone = ($timezone === '' ? null : $timezone);
    }

    /**
     * Keep the auth token and HMAC secret out of var_dump() and stack traces.
     *
     * @return array<string,mixed>
     */
    public function __debugInfo(): array {
        return array(
            'serviceEndpoint' => $this->serviceEndpoint,
            'proxyHost' => $this->proxyHost,
            'proxyPort' => $this->proxyPort,
            'authToken' => $this->authToken === null || $this->authToken === '' ? $this->authToken : '[REDACTED]',
            'hmacSecret' => $this->hmacSecret === null || $this->hmacSecret === '' ? $this->hmacSecret : '[REDACTED]',
            'validateOnly' => $this->validateOnly,
            'allowInsecureEndpoint' => $this->allowInsecureEndpoint,
            'timezone' => $this->timezone,
        );
    }

}
