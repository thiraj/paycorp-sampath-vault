<?php

namespace createch\PaycorpSampathVault\Configuration;

/**
 * Builds a GatewayConfiguration from whichever source is available.
 *
 * Resolution order: an explicit array, then the Laravel config repository,
 * then the process environment. The environment path exists because this
 * client is usable outside Laravel; inside Laravel the config repository is
 * always preferred.
 *
 * WHY NOT CALL env() FROM THE GATEWAY CLASSES
 * -------------------------------------------
 * The legacy classes read env() in their constructors. Under
 * `php artisan config:cache` -- the documented production setup -- Laravel
 * stops populating $_ENV, so env() returns null and every one of those reads
 * silently produced an empty string. The result was an empty endpoint and an
 * HMAC computed with an empty secret: every payment failed, and only on
 * production. env() now appears solely inside the published config file,
 * where it is evaluated while the cache is being built.
 */
final class ConfigurationFactory
{
    /** Laravel config key, also the published file name. */
    const CONFIG_KEY = 'paycorp-sampath-vault';

    /**
     * Environment variable to config key. Nested keys use dot notation.
     *
     * @var array<string,string>
     */
    private static $environmentMap = array(
        'SAMPATH_SERVICE_ENDPOINT' => 'service_endpoint',
        'SAMPATH_AUTHTOKEN' => 'authtoken',
        'SAMPATH_HMAC' => 'hmac_secret',
        'SAMPATH_CURRENCY' => 'currency',
        'SAMPATH_RETURN_URL' => 'return_url',
        'SAMPATH_CANCEL_URL' => 'cancel_url',
        'SAMPATH_TOKENIZE_CLIENT_ID' => 'tokenize_client_id',
        'SAMPATH_PURCHASE_CLIENT_ID' => 'purchase_client_id',
        'SAMPATH_TIMEZONE' => 'timezone',
        'SAMPATH_VALIDATE_ONLY' => 'validate_only',
        'SAMPATH_THROW_ON_ERROR' => 'throw_on_error',
        'SAMPATH_ALLOW_INSECURE_ENDPOINT' => 'allow_insecure_endpoint',
        'SAMPATH_CONNECT_TIMEOUT' => 'transport.connect_timeout',
        'SAMPATH_TIMEOUT' => 'transport.timeout',
        'SAMPATH_VERIFY_SSL' => 'transport.verify_peer',
        'SAMPATH_CA_BUNDLE' => 'transport.ca_bundle',
        'SAMPATH_PROXY_HOST' => 'transport.proxy_host',
        'SAMPATH_PROXY_PORT' => 'transport.proxy_port',
    );

    private function __construct()
    {
    }

    /**
     * @param  array<string,mixed>|GatewayConfiguration|null  $explicit
     * @return GatewayConfiguration
     */
    public static function resolve($explicit = null)
    {
        if ($explicit instanceof GatewayConfiguration) {
            return $explicit;
        }

        if (is_array($explicit)) {
            return new GatewayConfiguration($explicit);
        }

        $fromLaravel = self::fromLaravelConfig();

        return $fromLaravel !== null ? $fromLaravel : self::fromEnvironment();
    }

    /**
     * @param  array<string,mixed>  $values
     * @return GatewayConfiguration
     */
    public static function fromArray(array $values)
    {
        return new GatewayConfiguration($values);
    }

    /**
     * @return GatewayConfiguration|null  Null when not running inside Laravel,
     *                                    or when the package config is absent.
     */
    public static function fromLaravelConfig()
    {
        if (! function_exists('config')) {
            return null;
        }

        /** @var mixed $values */
        $values = config(self::CONFIG_KEY);

        if (! is_array($values) || $values === array()) {
            return null;
        }

        /** @var array<string,mixed> $values */
        return new GatewayConfiguration($values);
    }

    /**
     * @return GatewayConfiguration
     */
    public static function fromEnvironment()
    {
        /** @var array<string,string> $transport */
        $transport = array();
        /** @var array<string,mixed> $values */
        $values = array();

        foreach (self::$environmentMap as $variable => $key) {
            $value = self::readEnvironment($variable);

            if ($value === null) {
                continue;
            }

            if (strpos($key, 'transport.') === 0) {
                $transport[substr($key, strlen('transport.'))] = $value;
                continue;
            }

            $values[$key] = $value;
        }

        $values['transport'] = $transport;

        return new GatewayConfiguration($values);
    }

    /**
     * @param  string  $variable
     * @return string|null
     */
    private static function readEnvironment($variable)
    {
        // Laravel's Dotenv populates both superglobals; plain PHP may have
        // only getenv(). A non-scalar value is unusable either way.
        foreach (array($_ENV, $_SERVER) as $source) {
            if (! array_key_exists($variable, $source)) {
                continue;
            }

            $value = $source[$variable];

            if ($value !== false && is_scalar($value)) {
                return (string) $value;
            }
        }

        $value = getenv($variable);

        return is_string($value) ? $value : null;
    }
}
