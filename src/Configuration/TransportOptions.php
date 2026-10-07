<?php

namespace createch\PaycorpSampathVault\Configuration;

use createch\PaycorpSampathVault\PaycorpSampathVault;

/**
 * Immutable HTTP transport settings.
 *
 * Immutable so that a request in flight cannot have its TLS settings changed
 * underneath it, and so the same options object can be shared safely between
 * the payment and vault facades.
 */
final class TransportOptions
{
    /**
     * Legacy default, preserved deliberately.
     *
     * 60s is far longer than a healthy gateway needs, but lowering it would
     * start failing requests for merchants on slow links who succeed today.
     * Configurable for anyone who wants to tighten it.
     */
    const DEFAULT_CONNECT_TIMEOUT = 60;

    /**
     * Total request ceiling the legacy client did not have at all.
     *
     * Without it a stalled gateway pins a PHP worker until the process is
     * killed. Set high enough that no request which succeeds today will begin
     * to fail, while still guaranteeing the worker is eventually released.
     */
    const DEFAULT_TIMEOUT = 120;

    /** @var int */
    private $connectTimeout;

    /** @var int */
    private $timeout;

    /** @var bool */
    private $verifyPeer;

    /** @var string|null */
    private $caBundle;

    /** @var string|null */
    private $proxyHost;

    /** @var int|null */
    private $proxyPort;

    /** @var string */
    private $userAgent;

    /**
     * @param  array<string,mixed>  $options
     */
    public function __construct(array $options = array())
    {
        $this->connectTimeout = $this->integer($options, 'connect_timeout', self::DEFAULT_CONNECT_TIMEOUT);
        $this->timeout = $this->integer($options, 'timeout', self::DEFAULT_TIMEOUT);
        $this->verifyPeer = $this->boolean($this->pick($options, 'verify_peer', true), true);
        $this->caBundle = $this->nullableString($this->pick($options, 'ca_bundle', null));
        $this->proxyHost = $this->nullableString($this->pick($options, 'proxy_host', null));

        $proxyPort = $this->pick($options, 'proxy_port', null);
        $this->proxyPort = ($proxyPort === null || $proxyPort === '' || ! is_scalar($proxyPort))
            ? null
            : (int) $proxyPort;

        $this->userAgent = $this->string(
            $options,
            'user_agent',
            'createch-paycorp-sampath-vault/' . PaycorpSampathVault::VERSION
                . ' (+https://github.com/thiraj/paycorp-sampath-vault)'
        );
    }

    /** @return int */
    public function connectTimeout()
    {
        return $this->connectTimeout;
    }

    /** @return int */
    public function timeout()
    {
        return $this->timeout;
    }

    /**
     * Whether the gateway's TLS certificate chain is verified.
     *
     * Always true unless explicitly disabled. The legacy client hard-coded
     * CURLOPT_SSL_VERIFYPEER to false, which meant every PAN and CVV it sent
     * travelled over a connection that accepted any certificate -- readable
     * and modifiable by an active man-in-the-middle.
     *
     * @return bool
     */
    public function verifiesPeer()
    {
        return $this->verifyPeer;
    }

    /** @return string|null */
    public function caBundle()
    {
        return $this->caBundle;
    }

    /** @return string|null */
    public function proxyHost()
    {
        return $this->proxyHost;
    }

    /** @return int|null */
    public function proxyPort()
    {
        return $this->proxyPort;
    }

    /** @return string */
    public function userAgent()
    {
        return $this->userAgent;
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
     * @return array<string,mixed>
     */
    public function toArray()
    {
        return array(
            'connect_timeout' => $this->connectTimeout,
            'timeout' => $this->timeout,
            'verify_peer' => $this->verifyPeer,
            'ca_bundle' => $this->caBundle,
            'proxy_host' => $this->proxyHost,
            'proxy_port' => $this->proxyPort,
            'user_agent' => $this->userAgent,
        );
    }

    /**
     * Read an integer option, ignoring anything that is not numeric.
     *
     * A non-numeric timeout silently becoming 0 would mean "no timeout" to
     * curl, reintroducing the hang this class exists to prevent.
     *
     * @param  array<string,mixed>  $options
     * @param  string               $key
     * @param  int                  $default
     * @return int
     */
    private function integer(array $options, $key, $default)
    {
        $value = $this->pick($options, $key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param  array<string,mixed>  $options
     * @param  string               $key
     * @param  string               $default
     * @return string
     */
    private function string(array $options, $key, $default)
    {
        $value = $this->pick($options, $key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param  array<string,mixed>  $options
     * @param  string               $key
     * @param  mixed                $default
     * @return mixed
     */
    private function pick(array $options, $key, $default)
    {
        return (array_key_exists($key, $options) && $options[$key] !== null)
            ? $options[$key]
            : $default;
    }

    /**
     * Interpret a boolean that may have arrived as an environment string.
     *
     * (bool) "false" is true in PHP, so a plain cast would silently ignore
     * SAMPATH_VERIFY_SSL=false. Only the recognised falsey spellings turn a
     * setting off; anything unrecognised keeps $default, so a typo in the
     * environment can never switch TLS verification off by accident.
     *
     * @param  mixed  $value
     * @param  bool   $default
     * @return bool
     */
    private function boolean($value, $default)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalised = strtolower(trim($value));

            if (in_array($normalised, array('false', '0', 'off', 'no', ''), true)) {
                return false;
            }

            if (in_array($normalised, array('true', '1', 'on', 'yes'), true)) {
                return true;
            }

            return $default;
        }

        if (is_int($value) || is_float($value)) {
            return $value != 0;
        }

        return $default;
    }

    /**
     * @param  mixed  $value
     * @return string|null
     */
    private function nullableString($value)
    {
        if ($value === null || $value === '' || ! is_scalar($value)) {
            return null;
        }

        return (string) $value;
    }
}
