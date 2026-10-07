<?php

namespace createch\PaycorpSampathVault\Contracts;

/**
 * Strips cardholder data and credentials out of text before it is logged,
 * returned to a caller, or embedded in an exception message.
 */
interface RedactorInterface
{
    /**
     * @param  string  $text
     * @return string
     */
    public function redact($text);
}
