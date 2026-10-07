<?php

namespace createch\PaycorpSampathVault\Exceptions;

/**
 * Raised when the gateway replied but the body was not the JSON envelope
 * this client understands (an HTML error page, a truncated body, a JSON
 * document without a responseData node).
 *
 * Distinct from TransportException because the request definitely reached
 * the gateway; the transaction may well have been processed.
 */
class MalformedResponseException extends PaycorpException
{
    /** @var string */
    private $rawBody = '';

    /**
     * @param  string  $body
     * @param  string  $reason
     * @return self
     */
    public static function because($body, $reason)
    {
        $exception = new self('Unreadable response from the Paycorp gateway: ' . $reason);
        $exception->rawBody = (string) $body;

        return $exception;
    }

    /**
     * The raw body, for reconciliation logging.
     *
     * Already redacted by the transport layer before it reaches here.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->rawBody;
    }
}
