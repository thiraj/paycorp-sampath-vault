<?php

namespace createch\PaycorpSampathVault\Exceptions;

/**
 * Raised when the gateway credentials or endpoint are missing or unusable.
 *
 * Thrown only when a request is about to be sent, never while the container
 * is being built, so that a misconfigured application still boots.
 */
class ConfigurationException extends PaycorpException
{
    /**
     * @param  string[]  $missing
     * @return self
     */
    public static function missingValues(array $missing)
    {
        return new self(
            'Paycorp gateway is not configured: missing ' . implode(', ', $missing)
            . '. Set the matching SAMPATH_* environment variables or publish'
            . ' the paycorp-sampath-vault config file.'
        );
    }

    /**
     * @param  string  $endpoint
     * @return self
     */
    public static function insecureEndpoint($endpoint)
    {
        return new self(
            'Refusing to send cardholder data to the non-HTTPS endpoint "' . $endpoint . '". '
            . 'Use an https:// service endpoint, or set SAMPATH_ALLOW_INSECURE_ENDPOINT=true '
            . 'to permit plain HTTP against a local sandbox only.'
        );
    }
}
