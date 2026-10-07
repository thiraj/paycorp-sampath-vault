<?php

namespace createch\PaycorpSampathVault\Exceptions;

/**
 * Raised when the HTTP exchange with the gateway did not complete.
 *
 * This is the failure mode that previously surfaced as an apparently
 * successful response with empty fields: curl_exec() returned false and the
 * empty result was parsed as if it were a payment outcome. A transport
 * failure means the transaction outcome is UNKNOWN, never "declined", so
 * callers must reconcile rather than assume the payment did not happen.
 */
class TransportException extends PaycorpException
{
    /** @var int */
    private $curlErrorNumber = 0;

    /** @var int|null */
    private $statusCode;

    /**
     * @param  string  $message
     * @param  int     $curlErrorNumber
     * @return self
     */
    public static function connectionFailed($message, $curlErrorNumber = 0)
    {
        $exception = new self('Could not reach the Paycorp gateway: ' . $message);
        $exception->curlErrorNumber = (int) $curlErrorNumber;

        return $exception;
    }

    /**
     * @param  int     $statusCode
     * @param  string  $body
     * @return self
     */
    public static function unexpectedStatus($statusCode, $body)
    {
        $exception = new self(
            'The Paycorp gateway returned HTTP ' . $statusCode . '. Body: ' . self::summarise($body)
        );
        $exception->statusCode = (int) $statusCode;

        return $exception;
    }

    /** @return int */
    public function getCurlErrorNumber()
    {
        return $this->curlErrorNumber;
    }

    /** @return int|null */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param  string  $body
     * @return string
     */
    private static function summarise($body)
    {
        $body = trim((string) $body);

        if ($body === '') {
            return '<empty>';
        }

        return strlen($body) > 500 ? substr($body, 0, 500) . '...' : $body;
    }
}
