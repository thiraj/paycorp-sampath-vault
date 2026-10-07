# Security Policy

## Reporting a vulnerability

Please report security issues privately rather than opening a public issue:
**thirajpriyadharshana@gmail.com**. Include reproduction steps and the affected
version. Expect an acknowledgement within 7 days.

Do not include real card numbers, CVVs, vault tokens, or live gateway credentials
in a report.

## Supported versions

| Version | Supported |
|---|---|
| `2.x` | Yes |
| `1.x` | No — see the disclosure below |

## Disclosure: credentials committed in 1.x (all releases up to `v1.4`)

Every 1.x release committed live gateway credentials to the repository, and those
tags are published on Packagist:

- `src/config/PaycorpSampathVault.php` held an `authtoken`, an `hmac_secret`, and
  two merchant client ids, against the production endpoint.
- `src/Paycorplib/GatewayIT/pcw_payment-complete_UT.php` held an `authtoken` and
  `hmac_secret` pointing at the **production** endpoint.
- Four further `GatewayIT/*` scripts held test-environment credentials.

**Anyone who installed this package at 1.x has these values on disk.** Git history
rewriting does not remediate this — the tags are mirrored by Packagist and by every
`composer install`. If you operate a merchant account that used these credentials,
have Sampath/Paycorp rotate them.

2.x removes the files, reads every secret from the environment, and keeps secrets
out of `var_dump()`, `dd()` and stack traces via `__debugInfo()`.

## Disclosure: TLS verification disabled in 1.x

`RestClient` hard-coded `CURLOPT_SSL_VERIFYPEER => false`. Every card number, CVV
and vault token 1.x transmitted crossed a connection that accepted any
certificate, and was therefore readable and modifiable by an active
man-in-the-middle on the network path.

2.x verifies certificates and hostnames by default, pins TLS 1.2 or better, and
will not follow redirects while carrying the auth token. Verification can only be
disabled by explicit configuration.

## Hardening in 2.x

- **No silent success.** A failed exchange cannot be mistaken for a completed
  payment, and an unknown outcome is labelled as such so it is reconciled rather
  than assumed.
- **Redaction.** PANs, CVVs, vault tokens, HMAC digests and auth tokens are
  stripped from every message returned to a caller or written to a log.
- **CSPRNG message ids.** The `msgId` the gateway uses for duplicate detection is
  no longer `mt_rand()`.
- **Constant-time HMAC comparison.** `verify()` uses `hash_equals()`, so a digest
  cannot be recovered byte by byte from response timing.
- **No retries.** The transport never repeats a request; a retried authorisation
  can capture funds twice.
- **No output.** 1.x echoed request and response bodies, PANs included, into the
  HTTP response.

## PCI DSS scope

`PaycorpSampathRealTimePayment` accepts a PAN and CVV directly, which brings the
consuming application into PCI DSS scope. Prefer the hosted page plus vault token
flow, which keeps the PAN inside Paycorp's page. A CVV must never be stored in any
form, masked or otherwise.
