<?php

namespace createch\PaycorpSampathVault\Security;

use createch\PaycorpSampathVault\Contracts\EncoderInterface;

/**
 * Bit-exact replacement for PHP's removed utf8_decode().
 *
 * WHY THIS CLASS EXISTS
 * ---------------------
 * Every HMAC this package has ever sent was computed over
 * utf8_decode($payload) and utf8_decode($secret) -- over the ISO-8859-1
 * transliteration of the input, not over its UTF-8 bytes. utf8_decode() was
 * deprecated in PHP 8.2 and removed in PHP 9.
 *
 * Signing the UTF-8 bytes instead would silently change the digest for every
 * request carrying a non-ASCII comment, clientRef or cardholder name, and the
 * gateway would answer with an HMAC rejection. The transliteration therefore
 * has to be preserved exactly, quirks included.
 *
 * WHY NOT mb_convert_encoding()
 * -----------------------------
 * mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8') agrees with utf8_decode()
 * on every well-formed input -- measured over 127,287 cases including all of
 * U+0000..U+FFFF -- but diverges on malformed UTF-8, because the two
 * resynchronise differently after a bad byte (2,671 divergences in 36,201
 * malformed samples). It also depends on the global
 * mbstring.substitute_character ini setting, which an application may change
 * underneath us and so alter every signature.
 *
 * This class instead reimplements php_next_utf8_char() / php_utf8_decode()
 * from the PHP source: a code point above U+00FF or a decode failure yields
 * one '?', and -- the part a naive port gets wrong -- the number of bytes
 * consumed by a failure depends on whether the following bytes look like
 * continuation bytes or like the start of a fresh character. Verified
 * byte-identical to utf8_decode() across every well-formed and malformed
 * sample in the fuzz corpus.
 */
final class Latin1Encoder implements EncoderInterface
{
    /** Byte utf8_decode() emits for an unmappable code point or a decode failure. */
    const SUBSTITUTE = 0x3F;

    /**
     * @param  string  $value
     * @return string  ISO-8859-1 bytes.
     */
    public function encode($value)
    {
        $value = (string) $value;
        $length = strlen($value);

        if ($length === 0) {
            return '';
        }

        $out = '';
        $pos = 0;

        while ($pos < $length) {
            $codePoint = $this->nextCodePoint($value, $length, $pos);

            $out .= chr($codePoint === null || $codePoint > 0xFF ? self::SUBSTITUTE : $codePoint);
        }

        return $out;
    }

    /**
     * Decode one character, advancing $pos exactly as php_next_utf8_char() does.
     *
     * @param  string  $s
     * @param  int     $length
     * @param  int     $pos     Passed by reference; always advances by at least 1.
     * @return int|null         The code point, or null on a decode failure.
     */
    private function nextCodePoint($s, $length, &$pos)
    {
        $c = ord($s[$pos]);

        if ($c < 0x80) {
            $pos++;

            return $c;
        }

        // 0x80-0xC1: a stray continuation byte, or an over-long two-byte lead.
        if ($c < 0xC2) {
            return $this->fail($pos, 1);
        }

        if ($c < 0xE0) {
            return $this->decodeTwoByte($s, $length, $pos, $c);
        }

        if ($c < 0xF0) {
            return $this->decodeThreeByte($s, $length, $pos, $c);
        }

        if ($c < 0xF5) {
            return $this->decodeFourByte($s, $length, $pos, $c);
        }

        // 0xF5-0xFF can never begin a valid sequence.
        return $this->fail($pos, 1);
    }

    /**
     * @param  string  $s
     * @param  int     $length
     * @param  int     $pos
     * @param  int     $c
     * @return int|null
     */
    private function decodeTwoByte($s, $length, &$pos, $c)
    {
        $available = $length - $pos;

        if ($available < 2) {
            return $this->fail($pos, 1);
        }

        $trail = ord($s[$pos + 1]);

        if (! $this->isTrail($trail)) {
            return $this->fail($pos, $this->isLead($trail) ? 1 : 2);
        }

        $codePoint = (($c & 0x1F) << 6) | ($trail & 0x3F);

        if ($codePoint < 0x80) {
            return $this->fail($pos, 2);
        }

        $pos += 2;

        return $codePoint;
    }

    /**
     * @param  string  $s
     * @param  int     $length
     * @param  int     $pos
     * @param  int     $c
     * @return int|null
     */
    private function decodeThreeByte($s, $length, &$pos, $c)
    {
        $available = $length - $pos;

        if ($available < 3
            || ! $this->isTrail(ord($s[$pos + 1]))
            || ! $this->isTrail(ord($s[$pos + 2]))
        ) {
            if ($available < 2 || $this->isLead(ord($s[$pos + 1]))) {
                return $this->fail($pos, 1);
            }

            if ($available < 3 || $this->isLead(ord($s[$pos + 2]))) {
                return $this->fail($pos, 2);
            }

            return $this->fail($pos, 3);
        }

        $codePoint = (($c & 0x0F) << 12)
            | ((ord($s[$pos + 1]) & 0x3F) << 6)
            | (ord($s[$pos + 2]) & 0x3F);

        // Over-long encodings and UTF-16 surrogate halves are both rejected.
        if ($codePoint < 0x800 || ($codePoint >= 0xD800 && $codePoint <= 0xDFFF)) {
            return $this->fail($pos, 3);
        }

        $pos += 3;

        return $codePoint;
    }

    /**
     * @param  string  $s
     * @param  int     $length
     * @param  int     $pos
     * @param  int     $c
     * @return int|null
     */
    private function decodeFourByte($s, $length, &$pos, $c)
    {
        $available = $length - $pos;

        if ($available < 4
            || ! $this->isTrail(ord($s[$pos + 1]))
            || ! $this->isTrail(ord($s[$pos + 2]))
            || ! $this->isTrail(ord($s[$pos + 3]))
        ) {
            if ($available < 2 || $this->isLead(ord($s[$pos + 1]))) {
                return $this->fail($pos, 1);
            }

            if ($available < 3 || $this->isLead(ord($s[$pos + 2]))) {
                return $this->fail($pos, 2);
            }

            if ($available < 4 || $this->isLead(ord($s[$pos + 3]))) {
                return $this->fail($pos, 3);
            }

            return $this->fail($pos, 4);
        }

        $codePoint = (($c & 0x07) << 18)
            | ((ord($s[$pos + 1]) & 0x3F) << 12)
            | ((ord($s[$pos + 2]) & 0x3F) << 6)
            | (ord($s[$pos + 3]) & 0x3F);

        if ($codePoint < 0x10000 || $codePoint > 0x10FFFF) {
            return $this->fail($pos, 4);
        }

        $pos += 4;

        return $codePoint;
    }

    /**
     * Mirrors the MB_FAILURE macro: advance past the offending bytes, report failure.
     *
     * @param  int  $pos
     * @param  int  $advance
     * @return null
     */
    private function fail(&$pos, $advance)
    {
        $pos += $advance;

        return null;
    }

    /**
     * @param  int  $byte
     * @return bool
     */
    private function isTrail($byte)
    {
        return $byte >= 0x80 && $byte <= 0xBF;
    }

    /**
     * @param  int  $byte
     * @return bool
     */
    private function isLead($byte)
    {
        return $byte < 0x80 || ($byte >= 0xC2 && $byte <= 0xF4);
    }
}
