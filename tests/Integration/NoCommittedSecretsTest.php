<?php

namespace createch\PaycorpSampathVault\Test\Integration;

use createch\PaycorpSampathVault\Test\TestCase;

/**
 * 1.x committed live gateway credentials to the repository and published them
 * as Packagist tags. This test is the standing guard against a repeat: it scans
 * every shipped file for secret-shaped literals rather than comparing against
 * the leaked values, because writing those values down here would re-disclose
 * them on every clone.
 */
class NoCommittedSecretsTest extends TestCase
{
    /**
     * An opaque credential: 16+ chars of base64/hex alphabet carrying BOTH a
     * digit and a letter. That combination is what separates a generated secret
     * from an identifier like `allowInsecureEndpoint` or `errorDescription`.
     */
    const OPAQUE_SECRET = '/\'(?=[A-Za-z0-9+\/=]*[0-9])(?=[A-Za-z0-9+\/=]*[A-Za-z])[A-Za-z0-9+\/=]{16,}\'/';

    /** A UUID, the shape of the leaked `authtoken`. */
    const UUID = '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i';

    /** A host name baked into shipped code: the endpoint is deployment data. */
    const HOST = '/[a-z0-9][a-z0-9.-]*\.(?:com|net|lk|au|org|io)\b/i';

    /**
     * Hosts that are not gateway endpoints and may legitimately appear in
     * shipped code. Keep this list to things that can never carry a credential.
     */
    const ALLOWED_HOSTS = [
        'github.com',
    ];

    /**
     * @return array<string, array{0: string}>
     */
    public static function shippedFiles()
    {
        $root = dirname(__DIR__, 2);
        $cases = [];

        foreach (['src', 'config'] as $dir) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root . '/' . $dir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                $cases[$relative] = [$file->getPathname()];
            }
        }

        ksort($cases);

        return $cases;
    }

    /**
     * @dataProvider shippedFiles
     *
     * @param  string  $path
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('shippedFiles')]
    public function testNoShippedFileContainsASecretShapedLiteral($path)
    {
        $code = $this->stripComments((string) file_get_contents($path));

        $this->assertDoesNotMatchRegularExpression(
            self::OPAQUE_SECRET,
            $code,
            'opaque credential-shaped literal; secrets belong in the environment'
        );

        $this->assertDoesNotMatchRegularExpression(
            self::UUID,
            $code,
            'UUID literal; the gateway authtoken is a UUID and must come from the environment'
        );

        preg_match_all(self::HOST, $code, $hosts);

        $this->assertSame(
            [],
            array_values(array_diff(array_unique($hosts[0]), self::ALLOWED_HOSTS)),
            'host name literal; the gateway endpoint is deployment data'
        );
    }

    public function testTheScannerSeesEveryShippedFile()
    {
        // A provider that silently returns nothing would make this suite green
        // while scanning no code at all.
        $this->assertGreaterThan(50, count(self::shippedFiles()));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function secretShapedSamples()
    {
        return [
            'hmac secret'  => ["\$x = 'Ab3dEf6hIj9lMn2p';"],
            'base64 key'   => ["\$x = 'c2VjcmV0LWtlee5hbWU0';"],
            'uuid token'   => ["\$x = 'a62cdd4d-0000-4000-8000-000000000000';"],
            'gateway host' => ["\$x = 'https://gateway.example.com/rest';"],
        ];
    }

    /**
     * The scanner is only worth having if it actually rejects these shapes.
     *
     * @dataProvider secretShapedSamples
     *
     * @param  string  $sample
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('secretShapedSamples')]
    public function testTheScannerRejectsSecretShapedCode($sample)
    {
        $matched = preg_match(self::OPAQUE_SECRET, $sample)
            || preg_match(self::UUID, $sample)
            || preg_match(self::HOST, $sample);

        $this->assertSame(1, (int) $matched, "the scanner must reject: {$sample}");
    }

    /**
     * ...and only if it leaves ordinary code alone.
     *
     * @return array<string, array{0: string}>
     */
    public static function innocentSamples()
    {
        return [
            'long identifier' => ["\$a = ['allowInsecureEndpoint' => true];"],
            'config key'      => ["\$a = ['errorDescription' => null];"],
            'env call'        => ["\$a = env('SAMPATH_HMAC', '');"],
            'short literal'   => ["\$a = 'LKR';"],
        ];
    }

    /**
     * @dataProvider innocentSamples
     *
     * @param  string  $sample
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('innocentSamples')]
    public function testTheScannerAcceptsOrdinaryCode($sample)
    {
        $matched = preg_match(self::OPAQUE_SECRET, $sample)
            || preg_match(self::UUID, $sample)
            || preg_match(self::HOST, $sample);

        $this->assertSame(0, (int) $matched, "the scanner must accept: {$sample}");
    }

    /**
     * @param  string  $source
     * @return string
     */
    private function stripComments($source)
    {
        $out = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $out .= is_array($token) ? $token[1] : $token;
        }

        return $out;
    }
}
