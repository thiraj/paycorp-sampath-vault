# Releasing

## Tagging strategy: one tag, every Laravel version

**There is deliberately no tag per Laravel version.** A consumer requires
`^2.0` and Composer resolves it against whatever framework version they are on:

```json
"require": { "createch/paycorp-sampath-vault": "^2.0" }
```

| Tag | PHP | Laravel covered by that single tag |
|---|---|---|
| `v2.0.0` | 7.3 – 8.4 | 5.5 · 6 · 7 · 8 · 9 · 10 · 11 · 12 · 13 |

This works because the package depends on only three framework APIs —
`ServiceProvider`, `Facade`, and the config repository — none of which has
changed across that range. The breadth lives in one Composer constraint:

```json
"illuminate/support": "^5.5 || ^6.0 || ^7.0 || ^8.0 || ^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0"
```

### Why not a tag per framework version

Per-framework tags (`v2.0-laravel10`, `v2.0-laravel11`, …) are an anti-pattern
for a package this thin:

- Composer already solves it. A branch per framework version means N branches to
  backport every security fix into, and this is a payment package — a fix that
  lands on four of five branches is a liability.
- Packagist resolves by constraint, not by tag name. A consumer on Laravel 11
  gets the right code from `^2.0` automatically.
- The audit trail fragments. One `v2.0.1` is one CHANGELOG entry and one CVE
  reference; five parallel tags are five.

Split the line **only** when a framework version forces a genuinely
incompatible implementation. That has not happened in 5.5 → 13.

## When a new Laravel version ships

1. Add it to the `illuminate/support` constraint in `composer.json`.
2. Add a matrix row to `.github/workflows/tests.yml` (see the testbench mapping
   below).
3. If the suite passes, release a **patch** — it is a compatibility addition, not
   a feature.
4. Update the compatibility table in `README.md`.

### End-of-life framework versions in CI

Laravel 8 through 11 are end of life with unpatched advisories, so Composer will
not install them unless `policy.advisories.block` is set to false. The matrix
rows for those versions carry `eol-framework: true` and relax the policy for
themselves only; the `coverage` job keeps it enabled. Never relax it for a
release build.

### Laravel 5.5 – 5.8: the `legacy-bootstrap` job

Those versions cannot be reached through testbench: testbench 3.5 pins
`phpunit ^6.5`, while the suite needs assertions added in `phpunit` 9.1. That is
an incompatibility between two dev tools, not a limitation of the package — so
rather than carry `^5.5` in the constraint as an unverified claim, the
`legacy-bootstrap` job installs `laravel/framework` at 5.5 and 5.8 with the dev
block **removed** and runs `tests/Legacy/bootstrap-check.php`, which:

- registers the service provider against a real `Illuminate\Container\Container`
  and asserts `mergeConfigFrom()` landed the shipped defaults;
- resolves every container binding and the facade;
- re-signs all 90 frozen golden HMAC vectors and compares each digest.

The vector comparison is the assertion that matters. A signature that differs on
an old framework is a payment the gateway rejects, and no amount of green unit
tests elsewhere would catch it.

Two details in that job are load-bearing, and both cost an afternoon to
rediscover:

- **`composer remove --dev`, not `composer update --no-dev`.** `--no-dev` still
  *resolves* `require-dev`, and `orchestra/testbench` pulls `laravel/framework`,
  which `replaces` `illuminate/*`. Leaving the dev block in place fails with
  `laravel/framework replaces illuminate/view and thus cannot coexist with it`
  and nothing resolves at all.
- **`laravel/framework`, not the `illuminate/*` split packages.** The shipped
  config file calls `env()`, which lives in `illuminate/support`'s helpers but
  depends on `vlucas/phpdotenv` and `phpoption/phpoption` — brought in only by
  `laravel/framework`. Installing `illuminate/support` alone dies at
  `mergeConfigFrom()` with `Class 'PhpOption\Option' not found`. That is a
  harness artifact, not a package defect; a real application always has the
  framework.

Laravel 5.5 also needs `allow-plugins.kylekatarnls/update-helper false`, because
`nesbot/carbon` 1.x ships a composer plugin that modern composer blocks.

If you ever drop `^5.5` from the constraint, delete those two matrix rows in the
same commit — and remember that dropping a version is a **major** release.

### Testbench to Laravel mapping

| Laravel | Testbench | PHP floor |
|---|---|---|
| 5.5 – 5.8 | none — see below | 7.3 |
| 6 | `^4.0` | 7.3 |
| 7 | `^5.0` | 7.3 |
| 8 | `^6.0` | 7.3 |
| 9 | `^7.0` | 8.0 |
| 10 | `^8.0` | 8.1 |
| 11 | `^9.0` | 8.2 |
| 12 | `^10.0` | 8.2 |
| 13 | `^11.0` | 8.3 |

## Version policy

- **Patch** (`2.0.x`) — bug fixes, security fixes, support for a newly released
  Laravel or PHP version.
- **Minor** (`2.x.0`) — new methods or config keys, all additive.
- **Major** (`3.0.0`) — anything that changes the signed request bytes, removes a
  deprecated entry point, raises the PHP floor, or converts the static
  pseudo-enums to real PHP enums.

Dropping a Laravel version from the constraint is a **major** release. There is no
reason to drop any yet: supporting 5.5 costs one constraint entry.

## Release checklist

```bash
# 1. Every gate green
vendor/bin/phpunit
phpstan analyse -c phpstan.neon.dist
phpstan analyse -c phpstan-strict.neon.dist
composer validate --strict

# 2. No credential may ever reappear in the tree.
#    The structural canary is part of the suite -- it fails if any config value
#    grows a literal, a host name, or an env() default:
vendor/bin/phpunit --filter testThePublishedConfigFileContainsNoHardCodedCredentials

#    Plus a generic scan for secret-shaped literals anywhere in the shipped code.
#    No leaked value is written down here: printing it in this file would
#    re-disclose it on every clone.
git grep -nIE "'[A-Za-z0-9+/]{16,}'|[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}" \
  -- src config

# 3. Confirm the distributed archive is minimal
composer archive --format=tar --dir=/tmp
tar -tf /tmp/paycorp-sampath-vault-*.tar | grep -E 'tests/|\.github/|examples/' \
  && echo 'export-ignore is not working' || echo 'archive is clean'

# 4. The legacy half of the support range, without testbench
php tests/Legacy/bootstrap-check.php

# 5. Byte parity with v1.4 -- this stands in for a gateway sandbox, see below
./tests/Differential/wire-parity.sh

# 6. Set the version constant and the changelog date
#    src/PaycorpSampathVault.php  -> const VERSION
#    CHANGELOG.md                 -> replace "unreleased" with the date
#    (the User-Agent derives from the constant; do not hardcode it)

# 7. Tag and push
git tag -a v2.0.0 -m 'v2.0.0 — Laravel 5.5-13, security fixes, test suite'
git push origin 2.x
git push origin v2.0.0
```

Packagist picks the tag up via its GitHub hook. Verify afterwards that the new
version is listed and that its `require` block shows the full Laravel range.

## Proving a major release without a gateway sandbox

2.x rewrote the signing and transport path: `CurlTransport`, `Sha256HmacSigner`
and `Latin1Encoder` are the code that produces the bytes the bank verifies. The
usual way to gain confidence in that is a sandbox merchant account. **There is
no Paycorp sandbox available for this package**, so the release relies instead
on differential proof against 1.x, which is known to have been accepted in
production for years.

```bash
./tests/Differential/wire-parity.sh
```

That harness runs v1.4 and the working tree as two separate processes — they
share a namespace and cannot be autoloaded together — each posting through real
curl to a local capture server. It then compares, for all twelve scenarios
(the nine signed operations plus three edge inputs), the actual byte stream:

- the request body, byte for byte, with a field-level diff and an explicit
  key-order check (the HMAC covers the serialised body, so order is signature)
- the `HMAC` header, which re-proves the whole signing chain
- the `AUTHTOKEN` header, `Content-Type`, and the HTTP method

Only `msgId` and `requestDate` are replayed from the v1.4 run, because those are
the two fields 1.x generates nondeterministically (`mt_rand()` and
`date('Y-m-d H:i:s')`). Replaying them is also what makes the signatures
comparable at all: the digest covers both, so without replay every HMAC would
differ for an uninteresting reason. Nothing else is replayed.

`User-Agent` is deliberately excluded: 1.x impersonated
`Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)` and 2.x identifies itself
honestly. The gateway neither signs nor gates on it.

Three of the twelve scenarios are deliberately awkward, because a happy path
per operation would leave the interesting cases unproven:

- **non-ASCII in a signed field.** 1.x ran the payload through `utf8_decode()`;
  2.x reimplements that bit-exactly in `Latin1Encoder`, since `utf8_decode()` is
  removed in PHP 9. The golden vectors pin the encoder in isolation — this pins
  it through the whole stack in a real signed request. The body carries
  `\u00f4`, `\u2014`, `\u2615` and `\u65e5\u672c\u8a9e`, and both sides produce
  the same 594 bytes and the same digest.
- **empty optional strings**, which 1.x wrote as `$data['x'] ? $data['x'] : ''`.
- **a zero service fee**, with total equal to payment amount.

The harness runs in CI on PHP 7.4 and 8.4. 7.4 matters because that is where 1.x
was actually functional — several of its helpers index responses with unquoted
array keys, a warning on PHP 7 and a fatal `Error` on PHP 8. On 7.4 the legacy
run completes with no response-handling failures at all; on 8.4 three operations
report one, and the harness notes it and carries on, because the request has
already been signed and captured by then.

`KEEP_CAPTURES=1` leaves both capture sets on disk for inspection.
`LEGACY_SRC_DIR=<dir>` skips the `git archive` step when the repo is not
reachable — a container that mounts only the working tree, or a git worktree,
whose `.git` is a file pointing outside the mount.

### What this does and does not establish

It establishes that a merchant upgrading from 1.4 to 2.0 sends the gateway
exactly what they sent before. For a package whose entire job is to sign a
payload the bank verifies, that is the property that matters, and it is checked
more precisely than a sandbox transaction would check it — a sandbox proves one
path once, this proves all nine every time CI runs.

It does not establish that the gateway accepts any *new* capability 2.x adds
beyond the 1.x surface. Keep that in mind before widening the API: anything that
changes or extends the signed bytes is outside what parity covers, and
`Payment::batch()` is the live example — 1.x referenced an undeclared
`Operation::$PAYMENT_BATCH` and could never have worked, so 2.x throws
`UnsupportedOperationException` rather than guessing a wire format. Guessing is
what parity exists to prevent.

### Still ship a prerelease

```bash
git tag -a v2.0.0-beta.1 -m 'v2.0.0-beta.1'
git push origin v2.0.0-beta.1
```

Composer treats `-beta.N` as unstable, so no consumer requiring `^2.0` picks it
up by accident; a tester opts in with `"^2.0@beta"`. Install it in one real
application and drive the flows that application actually uses against
production, starting with the smallest-value transaction that is meaningful.
Parity means the request bytes are right; a prerelease is what confirms the
*response* handling, config wiring and error paths behave in a real deployment.

## Do not retag

Never move or delete a published tag. Composer and Packagist cache resolved
tags, and for a payment package a silently changed `v2.0.0` is indefensible.
Release `v2.0.1` instead.

## Keep `v1.4` in place

Leave the 1.x tags published. Deleting them would break `composer install` for
every application with a 1.x lock file, and it would not remediate the leaked
credentials — those are already mirrored. Rotation is the remediation; the
disclosure in `SECURITY.md` is the record.
