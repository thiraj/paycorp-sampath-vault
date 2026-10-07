<?php

namespace createch\PaycorpSampathVault\Support;

/**
 * Minimal nested-array reader.
 *
 * Deliberately not Illuminate\Support\Arr: the gateway client has to keep
 * working outside Laravel, and every response-parsing path depends on this.
 *
 * Exists because the legacy JSON helpers indexed gateway responses directly
 * -- $response['responseData']['responseCode'] -- which raised warnings on
 * PHP 7 and throws under a strict error handler, turning an ordinary declined
 * payment into an unhandled error.
 */
final class Arr
{
    /** Marker distinguishing "key absent" from "key present but null". */
    const MISSING = "\0paycorp\0missing\0";

    private function __construct()
    {
    }

    /**
     * Read a nested value, treating a present-but-null value as absent.
     *
     * Gateway responses use null and key-omission interchangeably for
     * "not applicable", so both collapse to $default.
     *
     * @param  mixed   $array
     * @param  string  $path     Dot-delimited, e.g. "responseData.creditCard.number".
     * @param  mixed   $default
     * @return mixed
     */
    public static function get($array, $path, $default = null)
    {
        $value = self::lookup($array, $path);

        return ($value === self::MISSING || $value === null) ? $default : $value;
    }

    /**
     * Read a value that must be a string, coercing scalars and rejecting the rest.
     *
     * @param  mixed   $array
     * @param  string  $path
     * @param  string  $default
     * @return string
     */
    public static function getString($array, $path, $default = '')
    {
        $value = self::get($array, $path, self::MISSING);

        if ($value === self::MISSING) {
            return $default;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * Whether the key exists at all, regardless of its value.
     *
     * @param  mixed   $array
     * @param  string  $path
     * @return bool
     */
    public static function has($array, $path)
    {
        return self::lookup($array, $path) !== self::MISSING;
    }

    /**
     * @param  mixed   $array
     * @param  string  $path
     * @return mixed   The raw value, or self::MISSING when any segment is absent.
     */
    private static function lookup($array, $path)
    {
        if (! is_array($array)) {
            return self::MISSING;
        }

        $current = $array;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return self::MISSING;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}
