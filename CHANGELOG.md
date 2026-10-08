# Changelog

All notable changes to this project are documented here. This project adheres to
[Semantic Versioning](https://semver.org/).

## [2.0.0] — 2026-10-07

One release line supporting **Laravel 5.5 through 13** on PHP 7.3 through 8.4.
Every 1.x public method, signature and response key is preserved. See
[UPGRADE.md](UPGRADE.md).

### Security

- **Credentials removed from the package.** 1.x committed live production
  credentials in `src/config/PaycorpSampathVault.php` and in
  `src/Paycorplib/GatewayIT/*`. Those files are gone; all secrets are read from
  the environment. **The leaked credentials must be rotated** — see
  [SECURITY.md](SECURITY.md).
- **TLS verification enabled.** 1.x hard-coded `CURLOPT_SSL_VERIFYPEER => false`,
  exposing every PAN and CVV to an active man-in-the-middle. Verification and
  hostname checking are now on by default, TLS 1.2+ is pinned, and redirects are
  not followed while carrying the auth token.
- **Sensitive data redacted** from every message returned to a caller or logged:
  PANs (last four retained), CVVs, vault tokens, HMAC digests, auth tokens.
- **Nothing is echoed to output.** `BaseFacade` printed request and response
  bodies, PANs included, in HTML `<div>`s.
- **CSPRNG message ids.** The `msgId` used by the gateway for duplicate detection
  was generated with `mt_rand()`.
- **Constant-time HMAC verification** via `hash_equals()`.
- Secrets are hidden from `var_dump()`, `dd()` and stack traces via `__debugInfo()`.

### Fixed

- **Silent payment failure.** `curl_exec()` returning `false` flowed into
  `json_decode()` as `null`, was read as a full set of empty fields, and
  `realTimePayment()` still returned `'status' => true`. A network outage was
  indistinguishable from a completed payment. Failures are now reported, and an
  indeterminate outcome is labelled `'outcome' => 'unknown'` so it is reconciled
  rather than assumed.
- **`Vault::deleteToken()`, `updateCard()` and `verifyToken()` were fatal on
  PHP 8** — their helpers indexed responses with unquoted array keys, which PHP 8
  treats as undefined constants.
- **`CreditTransaction::getComment()` was fatal on PHP 8** (`return comment;`).
- **`CreditTransaction::setTransactionType()`** was typehinted `TransactionType`
  while every caller passes a string, guaranteeing a `TypeError`.
- **`Payment::batch()`** referenced the undeclared `Operation::$PAYMENT_BATCH` and
  raised `Access to undeclared static property`. It now throws
  `UnsupportedOperationException`; a batch wire format is not guessed.
- **`completeRequest()` returned `null`** on failure, its catch block falling off
  the end of the method.
- **Response leakage between calls.** `$this->response` accumulated on a container
  singleton, so keys from one transaction — including card data — appeared in the
  next caller's result.
- **`utf8_decode()` removed from the HMAC path** ahead of its removal in PHP 9,
  replaced by a bit-exact reimplementation.
- **`env()` at runtime.** Constructors read `env()`, which returns `null` under
  `php artisan config:cache`, producing an empty endpoint and an empty HMAC
  secret in production only.
- **Config publishing.** 1.x published to `config_path('paycorp-sampath-vault')`,
  a directory Laravel never autoloads, and registered no publish tag.
- **No total request timeout.** A stalled gateway pinned a PHP worker
  indefinitely. There is now a 120s ceiling; the 60s connect timeout is unchanged.
- Undefined-key warnings from `$data['clientRef'] ? ... : ''`.
- `PaymentCompleteJsonHelper` did not declare `IJsonHelper`.
- Non-2xx HTTP responses and non-JSON bodies are detected instead of being parsed
  as empty payments.
- Gateway `error` envelopes are surfaced with their code instead of reading as
  empty response fields.

### Added

- Interface-driven architecture, every collaborator swappable through the
  container: `HttpTransportInterface`, `SignerInterface`, `EncoderInterface`,
  `ClockInterface`, `MessageIdGeneratorInterface`, `RedactorInterface`, plus the
  narrow `HostedPaymentGatewayInterface` and `RealTimePaymentGatewayInterface`.
- `Testing\FakeHttpTransport`, shipped so consuming applications can test their
  own payment flows without a live gateway.
- Typed exception hierarchy under `PaycorpException`, distinguishing "did not
  happen" from "outcome unknown".
- `'outcome' => 'failed'|'unknown'` on every failure result.
- Opt-in `SAMPATH_THROW_ON_ERROR` for exception-based error handling.
- Configurable timeouts, CA bundle, proxy, and request timezone.
- Laravel package auto-discovery, and a `paycorp-sampath-vault-config` publish tag.
- `version()`, returning the real package version.
- 459 tests across Unit, Contract and Integration suites, including 90 frozen HMAC
  golden vectors captured from the real `utf8_decode()`, a `config:cache`
  regression gate, and one test per defect listed above.
- A standing guard against a repeat of the 1.x credential leak: every file under
  `src/` and `config/` is scanned for secret *shapes* — an opaque 16+ character
  literal carrying both a digit and a letter, a UUID, or a host name — rather
  than for the known leaked strings, so a credential reintroduced under a new
  key is caught too. The scanner's own patterns are tested in both directions.
- CI matrix over PHP 7.4–8.4 × Laravel 6–13, with `--prefer-lowest` and
  `--prefer-stable` runs; PHPStan level 5 package-wide and level 9 on new code.
- A wire-parity harness (`tests/Differential/wire-parity.sh`) proving 2.x sends
  byte-identical requests to v1.4 across twelve scenarios: the nine signed
  operations plus non-ASCII, empty-optional and zero-fee edge inputs. v1.4 and the
  current tree run as separate processes, each posting through real curl to a
  local capture server; the comparison covers the request body (with a
  field-level and key-order diff), the `HMAC` header, the `AUTHTOKEN` header,
  `Content-Type` and the HTTP method. Only `msgId` and `requestDate` are
  replayed from the v1.4 run, being the two fields 1.x generated
  nondeterministically. It runs in CI on PHP 7.4 and 8.4, and stands in for the
  gateway sandbox this package has no access to.
- A `legacy-bootstrap` CI job covering Laravel 5.5 and 5.8, which
  `orchestra/testbench` cannot reach (testbench 3.5 pins `phpunit ^6.5` while
  the suite needs `phpunit` 9.1 assertions). It installs `illuminate/support`
  with the dev block removed, boots the provider against a real Illuminate
  container, resolves every binding and the facade, and re-signs all 90 golden
  vectors — so the lower half of the declared Composer constraint is a verified
  claim rather than an assumed one.

### Changed

- `illuminate/support` is now a declared dependency (`^5.5` … `^13.0`); 1.x used
  it without declaring it.
- `minimum-stability: dev` removed — it leaked dev stability into consumers.
- `composer.lock` and `.idea/` untracked; tests, CI and examples `export-ignore`d
  from the distributed package. `CHANGELOG.md` and `SECURITY.md` are shipped on
  purpose, so this disclosure is readable from an installed copy in `vendor/`.
- The HTTP `User-Agent` derives from `PaycorpSampathVault::VERSION` instead of a
  hardcoded string, which would have drifted at the next version bump.
- PHP requirement is `^7.3 || ^8.0`. 1.x claimed PHP 5.6 but was fatal on PHP 8.

### Deprecated

- `IPGLoaded()` — frozen at `"1.0.0.1"` forever for compatibility; use `version()`.
- `HmacUtils`, `CommonUtils`, `RestClient` static helpers — inject the matching
  interface instead. They still work and are wire-compatible.

## [1.4] — earlier

Legacy releases. End of life; see the security disclosures in
[SECURITY.md](SECURITY.md).
