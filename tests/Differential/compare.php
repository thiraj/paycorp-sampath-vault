<?php

/**
 * Compares the two capture sets and reports any divergence.
 *
 * Exit code 0 means 2.x puts the same bytes on the wire as v1.4 for every
 * operation. Non-zero means it does not, and the diff says where.
 *
 * What is compared, per operation:
 *   - the raw request body, byte for byte
 *   - the HMAC header (derived from the body, so this also re-proves the
 *     signing chain end to end)
 *   - the AUTHTOKEN header
 *   - the Content-Type header
 *   - the HTTP method
 *
 * What is deliberately NOT compared:
 *   - User-Agent. 1.x impersonated "Mozilla/4.0 (compatible; MSIE 8.0;
 *     Windows NT 6.1)". 2.x identifies itself honestly. The gateway does not
 *     sign or gate on it.
 *   - Headers curl adds on its own (Host, Accept, Content-Length).
 */

$legacyDir = $argv[1] ?? '';
$currentDir = $argv[2] ?? '';

if (! is_dir($legacyDir) || ! is_dir($currentDir)) {
    fwrite(STDERR, "usage: compare.php <legacy-captures-dir> <current-captures-dir>\n");
    exit(2);
}

$names = array_keys(require __DIR__ . '/operations.php');

/**
 * @param  string  $dir
 * @return array<int,array<string,mixed>>
 */
function load($dir)
{
    $files = glob($dir . '/*.json');
    sort($files);

    return array_map(function ($file) {
        return json_decode((string) file_get_contents($file), true);
    }, $files === false ? array() : $files);
}

$legacy = load($legacyDir);
$current = load($currentDir);

$failures = array();

if (count($legacy) !== count($current)) {
    $failures[] = sprintf(
        'capture count differs: v1.4 made %d request(s), 2.x made %d',
        count($legacy),
        count($current)
    );
}

if (count($legacy) !== count($names)) {
    $failures[] = sprintf(
        'v1.4 made %d request(s) but %d operations are defined -- an operation did not reach the wire',
        count($legacy),
        count($names)
    );
}

$compared = 0;

foreach ($legacy as $i => $old) {
    if (! isset($current[$i])) {
        continue;
    }

    $new = $current[$i];
    $label = isset($names[$i]) ? $names[$i] : "operation #" . ($i + 1);

    $oldHeaders = isset($old['headers']) ? $old['headers'] : array();
    $newHeaders = isset($new['headers']) ? $new['headers'] : array();

    $checks = array(
        'method' => array(
            isset($old['method']) ? $old['method'] : null,
            isset($new['method']) ? $new['method'] : null,
        ),
        'body' => array(
            isset($old['body']) ? $old['body'] : null,
            isset($new['body']) ? $new['body'] : null,
        ),
        'HMAC header' => array(
            isset($oldHeaders['HMAC']) ? $oldHeaders['HMAC'] : null,
            isset($newHeaders['HMAC']) ? $newHeaders['HMAC'] : null,
        ),
        'AUTHTOKEN header' => array(
            isset($oldHeaders['AUTHTOKEN']) ? $oldHeaders['AUTHTOKEN'] : null,
            isset($newHeaders['AUTHTOKEN']) ? $newHeaders['AUTHTOKEN'] : null,
        ),
        'Content-Type header' => array(
            isset($oldHeaders['CONTENT_TYPE']) ? $oldHeaders['CONTENT_TYPE'] : null,
            isset($newHeaders['CONTENT_TYPE']) ? $newHeaders['CONTENT_TYPE'] : null,
        ),
    );

    $operationOk = true;

    foreach ($checks as $what => $pair) {
        list($expected, $actual) = $pair;
        $compared++;

        if ($expected === $actual) {
            continue;
        }

        $operationOk = false;
        $failures[] = "{$label}: {$what} differs";

        if ($what === 'body') {
            $oldJson = json_decode((string) $expected, true);
            $newJson = json_decode((string) $actual, true);

            if (is_array($oldJson) && is_array($newJson)) {
                foreach (fieldDiff($oldJson, $newJson) as $line) {
                    $failures[] = "    {$line}";
                }
            } else {
                $failures[] = '    v1.4: ' . var_export($expected, true);
                $failures[] = '    2.x : ' . var_export($actual, true);
            }
        } else {
            $failures[] = '    v1.4: ' . var_export($expected, true);
            $failures[] = '    2.x : ' . var_export($actual, true);
        }
    }

    printf("  %-18s %s\n", $label, $operationOk ? 'identical' : 'DIFFERS');
}

/**
 * Flatten both payloads and report every differing path, including key order.
 *
 * @param  array<mixed>  $old
 * @param  array<mixed>  $new
 * @return string[]
 */
function fieldDiff(array $old, array $new)
{
    $flatOld = flatten($old);
    $flatNew = flatten($new);
    $lines = array();

    foreach ($flatOld as $path => $value) {
        if (! array_key_exists($path, $flatNew)) {
            $lines[] = "{$path}: present in v1.4, MISSING in 2.x (v1.4 = " . var_export($value, true) . ')';
            continue;
        }

        if ($flatNew[$path] !== $value) {
            $lines[] = "{$path}: v1.4 = " . var_export($value, true)
                . ', 2.x = ' . var_export($flatNew[$path], true);
        }
    }

    foreach ($flatNew as $path => $value) {
        if (! array_key_exists($path, $flatOld)) {
            $lines[] = "{$path}: ADDED by 2.x (" . var_export($value, true) . ')';
        }
    }

    if (array_keys($flatOld) !== array_keys($flatNew)) {
        $lines[] = 'key ORDER differs -- the HMAC covers the serialised body, so order is part of the signature';
    }

    return $lines;
}

/**
 * @param  array<mixed>  $data
 * @param  string        $prefix
 * @return array<string,mixed>
 */
function flatten(array $data, $prefix = '')
{
    $flat = array();

    foreach ($data as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

        if (is_array($value) && $value !== array()) {
            $flat = array_merge($flat, flatten($value, $path));
            continue;
        }

        $flat[$path] = $value;
    }

    return $flat;
}

echo "\n";

if ($failures !== array()) {
    echo "WIRE PARITY FAILED\n\n";

    foreach ($failures as $failure) {
        echo "  {$failure}\n";
    }

    echo "\n";
    exit(1);
}

printf(
    "WIRE PARITY CONFIRMED: %d operations, %d field comparisons, zero divergence.\n",
    count($legacy),
    $compared
);
printf("2.x puts byte-identical requests on the wire for every operation v1.4 supports.\n");
exit(0);
