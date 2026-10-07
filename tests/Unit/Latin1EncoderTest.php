<?php

namespace createch\PaycorpSampathVault\Test\Unit;

use createch\PaycorpSampathVault\Security\Latin1Encoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Latin1Encoder must reproduce the removed utf8_decode() byte for byte.
 *
 * This is the highest-stakes test in the package: the HMAC is computed over
 * the encoder's output, so a single divergent byte makes the gateway reject
 * every request carrying the affected characters.
 */
class Latin1EncoderTest extends TestCase
{
    /** @var Latin1Encoder */
    private $encoder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->encoder = new Latin1Encoder();
    }

    /**
     * @dataProvider knownTransliterations
     */
    #[DataProvider('knownTransliterations')]
    public function testItReproducesTheDocumentedTransliteration($inputHex, $expectedHex, $why)
    {
        $this->assertSame(
            $expectedHex,
            bin2hex($this->encoder->encode(hex2bin($inputHex))),
            $why
        );
    }

    /**
     * Expectations captured from utf8_decode() itself, expressed as hex so the
     * test file stays readable in any editor encoding.
     *
     * @return array<string,array{0:string,1:string,2:string}>
     */
    public static function knownTransliterations()
    {
        return array(
            'empty string' => array('', '', 'an empty payload must stay empty'),
            'plain ascii' => array(
                bin2hex('plain ascii 123'), bin2hex('plain ascii 123'),
                'ASCII passes through unchanged',
            ),
            'latin-1 representable' => array(
                bin2hex("C\xC3\xB4te"), bin2hex("C\xF4te"),
                'U+00F4 maps to the single byte 0xF4',
            ),
            'above U+00FF becomes one question mark' => array(
                bin2hex("\xE6\x97\xA5"), bin2hex('?'),
                'a CJK code point is unmappable and collapses to one ?',
            ),
            'astral plane emoji' => array(
                bin2hex("\xF0\x9F\x8E\x89"), bin2hex('?'),
                'a 4-byte sequence yields exactly one ?',
            ),
            'truncated 3-byte sequence' => array(
                'e282', bin2hex('?'),
                'utf8_decode consumes the whole truncated sequence, emitting ONE ? not two',
            ),
            'truncated 4-byte sequence' => array(
                'f09f8e', bin2hex('?'),
                'three bytes consumed, one ? emitted',
            ),
            'surrogate half' => array(
                'eda080', bin2hex('?'),
                'UTF-16 surrogates are invalid in UTF-8 and collapse to one ?',
            ),
            'overlong encoding' => array(
                'c0af', '3f3f',
                '0xC0 is below the 0xC2 lead-byte floor so it fails on its own, '
                . 'then 0xAF is a stray continuation byte and fails too: TWO ? not one',
            ),
            'resynchronisation after a bad byte' => array(
                'd2fc96d060', '3f3f3f60',
                'the exact case that proves failure-advance width is byte-dependent: '
                . 'd2+fc consumes two bytes, 96 consumes one, d0 consumes one because '
                . '0x60 looks like a fresh lead byte, leaving 0x60 intact',
            ),
            'stray continuation byte' => array(
                '80', bin2hex('?'),
                'a lone continuation byte is one failure',
            ),
            'f5 and above' => array(
                'f5808080', '3f3f3f3f',
                '0xF5 can never start a sequence, and each following byte fails separately',
            ),
        );
    }

    public function testEveryCodePointBelow0100MapsToItsOwnByte()
    {
        for ($codePoint = 0; $codePoint <= 0xFF; $codePoint++) {
            $utf8 = $codePoint < 0x80
                ? chr($codePoint)
                : chr(0xC0 | ($codePoint >> 6)) . chr(0x80 | ($codePoint & 0x3F));

            $this->assertSame(
                chr($codePoint),
                $this->encoder->encode($utf8),
                sprintf('U+%04X must map to the single byte 0x%02X', $codePoint, $codePoint)
            );
        }
    }

    public function testItIsStableAcrossMbstringSubstituteCharacterChanges()
    {
        // An application is free to change this ini setting globally. If the
        // encoder depended on mb_convert_encoding() without pinning it, every
        // signature would silently change here.
        if (! function_exists('mb_substitute_character')) {
            $this->markTestSkipped('mbstring is not available');
        }

        $original = mb_substitute_character();

        try {
            mb_substitute_character(0x23); // '#'
            $this->assertSame('?', $this->encoder->encode("\xE6\x97\xA5"));
        } finally {
            mb_substitute_character($original);
        }
    }

    public function testItDoesNotMutateItsInput()
    {
        $input = "Ürün\xFF";
        $copy = $input;

        $this->encoder->encode($input);

        $this->assertSame($copy, $input);
    }
}
