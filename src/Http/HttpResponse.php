<?php

namespace createch\PaycorpSampathVault\Http;

/**
 * An immutable HTTP result: the status code plus the raw body.
 *
 * The legacy transport returned only the body, so a 500 carrying an HTML
 * error page was indistinguishable from a 200 carrying a declined payment.
 * Keeping the status code is what lets the caller tell "the gateway answered"
 * from "something in front of the gateway answered".
 */
final class HttpResponse
{
    /** @var int */
    private $statusCode;

    /** @var string */
    private $body;

    /**
     * @param  int     $statusCode
     * @param  string  $body
     */
    public function __construct($statusCode, $body)
    {
        $this->statusCode = (int) $statusCode;
        $this->body = (string) $body;
    }

    /** @return int */
    public function statusCode()
    {
        return $this->statusCode;
    }

    /** @return string */
    public function body()
    {
        return $this->body;
    }

    /** @return bool */
    public function isSuccessful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
