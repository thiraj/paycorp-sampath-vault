<?php

namespace createch\PaycorpSampathVault\Exceptions;

/**
 * Raised when the gateway returned a well-formed envelope carrying an
 * "error" node instead of "responseData" -- typically a rejected HMAC,
 * an unknown clientId or a validation failure.
 */
class GatewayErrorException extends PaycorpException
{
    /** @var string */
    private $errorCode = '';

    /**
     * @param  string  $code
     * @param  string  $description
     * @return self
     */
    public static function fromEnvelope($code, $description)
    {
        $code = (string) $code;
        $description = (string) $description;

        $exception = new self(
            'The Paycorp gateway rejected the request'
            . ($code !== '' ? ' [' . $code . ']' : '')
            . ($description !== '' ? ': ' . $description : '.')
        );
        $exception->errorCode = $code;

        return $exception;
    }

    /** @return string */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
