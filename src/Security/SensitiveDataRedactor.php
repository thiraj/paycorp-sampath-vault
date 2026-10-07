<?php

namespace createch\PaycorpSampathVault\Security;

use createch\PaycorpSampathVault\Contracts\RedactorInterface;

/**
 * Removes cardholder data and gateway credentials from text before it is
 * logged, returned to a caller, or embedded in an exception message.
 *
 * The legacy client handed raw $e->getMessage() back to the application and
 * echoed whole request bodies, so PANs, CVVs, vault tokens and the HMAC
 * secret could all reach a log file. PCI DSS forbids storing a CVV at all,
 * and permits a PAN only when masked.
 *
 * Deliberately conservative: it is better to over-redact a diagnostic string
 * than to leak one card number.
 */
final class SensitiveDataRedactor implements RedactorInterface
{
    /**
     * JSON/field names whose values must never survive into a log.
     *
     * @var string[]
     */
    private static $sensitiveKeys = array(
        'number', 'cardNumber', 'card_number', 'pan',
        'secureId', 'secure_id', 'cvv', 'cvc', 'cvcResponse',
        'track1', 'track2', 'track3', 'cardChipData',
        'token', 'tokenReference',
        'hmac', 'hmacSecret', 'hmac_secret', 'authToken', 'authtoken', 'auth_token',
    );

    /**
     * @param  string  $text
     * @return string
     */
    public function redact($text)
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        $text = $this->redactJsonValues($text);
        $text = $this->redactHeaderValues($text);

        return $this->maskBarePans($text);
    }

    /**
     * Mask "number":"4564456445644564" style JSON pairs, keeping the last 4
     * digits of anything that looks like a PAN so transactions stay traceable.
     *
     * @param  string  $text
     * @return string
     */
    private function redactJsonValues($text)
    {
        $keys = implode('|', array_map('preg_quote', self::$sensitiveKeys));

        $result = preg_replace_callback(
            '/(["\']?(?:' . $keys . ')["\']?\s*[:=]\s*["\']?)([^"\',}\]\s]+)/i',
            function (array $matches) {
                return $matches[1] . $this->mask($matches[2]);
            },
            $text
        );

        // preg_* returns null on backtrack-limit failure. Returning the
        // UNREDACTED original there would leak exactly what this class exists
        // to hide, so fail closed instead.
        return $result === null ? '[REDACTION FAILED]' : $result;
    }

    /**
     * Mask the HMAC and AUTHTOKEN request headers.
     *
     * @param  string  $text
     * @return string
     */
    private function redactHeaderValues($text)
    {
        $result = preg_replace(
            '/\b(HMAC|AUTHTOKEN)\s*:\s*\S+/i',
            '$1: [REDACTED]',
            $text
        );

        return $result === null ? '[REDACTION FAILED]' : $result;
    }

    /**
     * Mask any bare 13-19 digit run, which is the shape of a payment card number.
     *
     * @param  string  $text
     * @return string
     */
    private function maskBarePans($text)
    {
        $result = preg_replace_callback(
            '/\b\d{13,19}\b/',
            function (array $matches) {
                return $this->mask($matches[0]);
            },
            $text
        );

        return $result === null ? '[REDACTION FAILED]' : $result;
    }

    /**
     * @param  string  $value
     * @return string
     */
    private function mask($value)
    {
        $value = (string) $value;
        $digits = preg_replace('/\D/', '', $value);

        if ($digits === null) {
            return '[REDACTED]';
        }

        // Looks like a PAN: keep the last 4 so a payment stays identifiable.
        if (strlen($digits) >= 13 && strlen($digits) === strlen($value)) {
            return str_repeat('*', strlen($value) - 4) . substr($value, -4);
        }

        return '[REDACTED]';
    }
}
