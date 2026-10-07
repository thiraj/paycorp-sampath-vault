# Upgrading from 1.x to 2.0

**Nothing you call changes.** Every public class, method name, signature and
returned array key from 1.x still exists and behaves the same way on the success
path. In most applications the upgrade is:

```bash
composer require createch/paycorp-sampath-vault:^2.0
```

The rest of this document covers the four behaviour changes that are deliberate,
and the bugs that are fixed — some of which may surface failures that were
previously invisible.

---

## 0. Rotate your credentials first

Before upgrading, ask Sampath/Paycorp to rotate your `authtoken` and
`hmac_secret`. Releases up to `v1.4` shipped live production credentials inside
the package and those tags are public on Packagist. See the security notice in
[README.md](README.md).

---

## 1. TLS certificates are now verified

1.x set `CURLOPT_SSL_VERIFYPEER => false`, so every card number and CVV it sent
crossed a connection that accepted any certificate.

2.x verifies by default. **If your host has an incomplete CA store, requests that
used to succeed will now fail** with a `TransportException` mentioning certificate
verification.

The correct fix is to point at a CA bundle:

```dotenv
SAMPATH_CA_BUNDLE=/etc/ssl/certs/ca-certificates.crt
```

The escape hatch exists, but using it in production puts cardholder data back on
an interceptable connection:

```dotenv
SAMPATH_VERIFY_SSL=false
```

## 2. A failed HTTP exchange is now reported as a failure

This is the most consequential fix in the release, and the one most likely to
change what your application sees.

In 1.x, `curl_exec()` returning `false` was passed to `json_decode()`, which
produced `null`, which every response parser read as a complete set of empty
fields — and `realTimePayment()` still returned `'status' => true`. **A network
outage was indistinguishable from a completed payment.**

In 2.x that returns:

```php
['status' => false, 'msg' => '...', 'outcome' => 'unknown']
```

If your code checked only `$result['status']`, it will now correctly see these as
failures. Handle the three-way outcome:

```php
if ($result['status']) {
    // The gateway answered. '00' is approved; anything else is a decline.
} elseif ($result['outcome'] === 'unknown') {
    // The money MAY have moved. Reconcile against the settlement report.
    // Do not retry blindly: a retry can capture a second time.
} else {
    // 'failed': definitively rejected, no money moved.
}
```

If you are currently treating `status === false` as "declined", that is now wrong
for `outcome === 'unknown'`.

## 3. Non-HTTPS endpoints are refused

A `SAMPATH_SERVICE_ENDPOINT` without `https://` raises a `ConfigurationException`
before any card data is sent. For a local sandbox only:

```dotenv
SAMPATH_ALLOW_INSECURE_ENDPOINT=true
```

## 4. Requests now have a total timeout

1.x had only `CURLOPT_CONNECTTIMEOUT`, so a gateway that accepted the connection
and then stalled pinned a PHP worker until the process was killed. There is now a
120-second ceiling on the whole request. The 60-second connect timeout is
unchanged, deliberately, so nothing that succeeds today starts failing.

```dotenv
SAMPATH_CONNECT_TIMEOUT=60
SAMPATH_TIMEOUT=120
```

---

## Configuration moved out of `env()`

1.x read `env('SAMPATH_HMAC')` inside its constructors. Under
`php artisan config:cache` — the documented production setup — Laravel stops
populating `$_ENV`, so `env()` returns `null` and every one of those reads became
`''`. The result was an empty endpoint and an HMAC computed with an empty secret:
**every payment failed, and only in production.**

Your `.env` keys are unchanged. If you read `SAMPATH_*` with `env()` anywhere in
your own code, switch to `config('paycorp-sampath-vault.*')`.

If you published the 1.x config, delete the stale directory — Laravel never loaded
it, because 1.x published into `config/paycorp-sampath-vault/` (a directory)
rather than `config/paycorp-sampath-vault.php`:

```bash
rm -rf config/paycorp-sampath-vault
php artisan vendor:publish --tag=paycorp-sampath-vault-config
php artisan config:clear
```

## Package discovery

The provider and the `PaycorpSampathVault` alias are now auto-discovered. Existing
manual entries in `config/app.php` can stay — registering a provider twice is a
no-op in Laravel.

---

## Bugs fixed (no action needed)

| Was | Now |
|---|---|
| `Vault::deleteToken()`, `updateCard()`, `verifyToken()` threw `Error: Undefined constant "responseData"` — **fatal on all of PHP 8** | Work correctly |
| `CreditTransaction::getComment()` returned the bare word `comment` — fatal on PHP 8 | Returns the comment |
| `CreditTransaction::setTransactionType()` was typehinted `TransactionType` but every caller passes a string — guaranteed `TypeError` | Accepts the string |
| `Payment::batch()` threw `Access to undeclared static property Operation::$PAYMENT_BATCH` | Throws `UnsupportedOperationException` with an explanation |
| `completeRequest()` returned `null` on failure — the catch block fell off the end of the method | Always returns an array |
| `$this->response` accumulated across calls on a container singleton, leaking keys (including card data) into later callers' results | Built fresh per call |
| `utf8_decode()` in the HMAC path — removed in PHP 9 | `Latin1Encoder`, verified bit-exact |
| `mt_rand()` generated the `msgId` the gateway uses for duplicate detection | CSPRNG |
| Request and response bodies, including PANs and HMACs, were echoed to output in HTML `<div>`s | Nothing is printed |
| Raw `$e->getMessage()` returned to callers and logs | Passed through the redactor |
| `$data['clientRef'] ? ... : ''` warned on absent keys and read them twice | `isset()`-guarded |
| `PaymentCompleteJsonHelper` did not declare `IJsonHelper` | Declared |
| `config/` shipped live production credentials | Reads `env()` only; no secrets committed |
| `src/Paycorplib/GatewayIT/*` shipped credentials, one set pointing at production | Removed from the package |

`PaymentCompleteJsonHelper`'s odd legacy fallbacks are **preserved exactly** — a
missing `clientRef`, `feeReference`, `token` or `withholdingAmount` still becomes
the integer `0`, and a missing `comment` or `tokenResponseText` still becomes `""`
— because callers compare against those values. Only the PHP warning is gone.

## Things that deliberately did not change

- `IPGLoaded()` still returns the string `"1.0.0.1"`, frozen forever, in case an
  integration gates on it. Use `version()` for the real package version.
- `HmacUtils::genarateHmac()` keeps its original spelling. `generateHmac()` is
  available as an alias.
- `RestClient::sendRequest()` keeps its signature, now delegating to
  `CurlTransport`. It throws instead of returning `false`.
- The static pseudo-enums (`TransactionType::$PURCHASE`) stay as static
  properties. Converting them to real PHP enums is a 3.x change.
- Outbound JSON key order and value coercion are byte-identical, because the HMAC
  is computed over that exact serialisation.

## Optional: exceptions instead of status arrays

```dotenv
SAMPATH_THROW_ON_ERROR=true
```

Off by default, so the 1.x array contract is what you get unless you opt in. See
[README.md](README.md) for the exception hierarchy.
