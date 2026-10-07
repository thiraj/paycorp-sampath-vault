# Contributing

## Setup

```bash
composer install
vendor/bin/phpunit
```

## The rules that matter in this repository

This is a payment gateway client. Two constraints override ordinary convenience.

### 1. Never change the signed bytes

The HMAC is computed over the serialised request body. Renaming a JSON key,
reordering one, or changing a value from `"100"` to `100` changes the signature,
and the gateway rejects the request. Live merchants break.

`tests/Contract/OutboundRequestTest.php` pins the exact outbound shape. If a
change there fails, the change is wrong — not the test.

The same applies to `Latin1Encoder`. It reproduces PHP's removed `utf8_decode()`
bit for bit, and `tests/Fixtures/hmac/golden-vectors.json` holds 90 digests
captured from the real function. **Never regenerate that fixture** on a PHP where
`utf8_decode()` is absent: it is the only remaining record of the correct answer.

### 2. Never let an unknown outcome look like a known one

If the package cannot tell whether a payment was processed, it must say so
(`'outcome' => 'unknown'`). It must never report success, and it must never report
a definite failure. The entire `tests/Contract/FailureModeTest.php` suite exists
because 1.x got this wrong and a network outage read as a completed payment.

Do not add retries. A retried authorisation can capture funds twice.

## Backwards compatibility

2.x supports Laravel 5.5 through 13 in one release line. That constrains what you
may write:

- **PHP 7.3 syntax only.** No typed properties, constructor promotion, `match`,
  `?->`, union types, enums, or trailing commas in parameter lists. The
  `syntax-floor` CI job lints every file on PHP 7.3 and 7.4.
- **Only `ServiceProvider` and `Facade` APIs stable across 5.5–13.**
- **Do not add native parameter types to `src/Paycorplib`.** Those untyped setters
  are public API; typing them rejects input 1.x accepted. That work is 3.x.
- **Deprecate, do not delete.** Add a `@deprecated` tag and keep the old entry
  point working.

## Testing

| Suite | What it covers |
|---|---|
| `Unit` | One class, no framework, no network |
| `Contract` | The full request/response pipeline through `FakeHttpTransport` |
| `Integration` | A real Laravel application via Orchestra Testbench |

No test may contact the live gateway. Record a fixture, scrub the PAN, token and
credentials, and commit the scrubbed JSON.

Every bug fix needs a regression test in `tests/Contract/RegressionTest.php` whose
docblock names the original symptom.

## Static analysis

```bash
phpstan analyse -c phpstan.neon.dist          # whole package, level 5
phpstan analyse -c phpstan-strict.neon.dist   # 2.x code, level 9
```

New code goes in the strict paths and must pass level 9. Do not add baseline
entries or `@phpstan-ignore` comments — fix the type.

## Secrets

Never commit a credential, an endpoint with an embedded token, a real PAN, or a
CVV. `tests/Integration/ServiceProviderTest.php` asserts that the shipped config
file contains none of the values leaked by 1.x; add to that assertion rather than
relying on review.
